#!/usr/bin/env python3

import imaplib
import email
from email.header import decode_header
import configparser
from twilio.rest import Client
import time
from slack_sdk import WebClient
from slack_sdk.errors import SlackApiError

def load_config(config_file='/var/www/html/config.ini'):
    """
    Load configuration variables from the specified INI file.

    Args:
        config_file (str): Path to the configuration INI file.

    Returns:
        ConfigParser: A ConfigParser object containing the loaded configuration.

    Raises:
        Exception: If the configuration file cannot be read.
    """
    print(f"Attempting to load config from: {config_file}")
    config = configparser.ConfigParser()
    read_files = config.read(config_file)
    if not read_files:
        print(f"Failed to read config file: {config_file}")
        raise Exception(f"Could not read config file: {config_file}")
    print(f"Successfully loaded config with sections: {config.sections()}")
    return config

def connect_to_gmail(username, password):
    """
    Connect to Gmail's IMAP server and authenticate using provided credentials.

    Args:
        username (str): Gmail username.
        password (str): Gmail password.

    Returns:
        imaplib.IMAP4_SSL: Authenticated IMAP connection object.

    Raises:
        Exception: If the connection or login fails.
    """
    try:
        imap = imaplib.IMAP4_SSL("imap.gmail.com")
        imap.login(username, password)
        return imap
    except Exception as e:
        print(f"Failed to connect to Gmail: {e}")
        return None

def fetch_unread_emails(imap):
    """
    Fetch unread emails from the Gmail inbox.

    Args:
        imap (IMAP4_SSL): Authenticated IMAP connection object.

    Returns:
        list: List of tuples containing email IDs and message objects.
    """
    try:
        imap.select("inbox")  # Select the inbox folder
        status, messages = imap.search(None, 'UNSEEN')  # Search for unread messages
        if status != "OK":
            print("No unread emails found.")
            return []

        email_ids = messages[0].split()  # Get a list of email IDs
        emails = []
        for email_id in email_ids:
            res, msg = imap.fetch(email_id, "(RFC822)")
            for response in msg:
                if isinstance(response, tuple):
                    # Parse the email
                    msg = email.message_from_bytes(response[1])
                    emails.append((email_id, msg))  # Return both ID and message
        return emails
    except Exception as e:
        print(f"Failed to fetch emails: {e}")
        return []

def extract_email_body(msg):
    """
    Extract the plain text body from an email message.

    Args:
        msg (EmailMessage): The email message object.

    Returns:
        str: The plain text body of the email.
    """
    try:
        if msg.is_multipart():
            for part in msg.walk():
                if part.get_content_type() == "text/plain" and not part.get("Content-Disposition"):
                    return part.get_payload(decode=True).decode("utf-8")
        else:
            return msg.get_payload(decode=True).decode("utf-8")
    except Exception as e:
        print(f"Failed to extract email body: {e}")
        return ""

def send_sms_via_twilio(account_sid, auth_token, from_number, to_number, body):
    """
    Send an SMS message using the Twilio API.

    Args:
        account_sid (str): Twilio Account SID.
        auth_token (str): Twilio authentication token.
        from_number (str): Twilio phone number.
        to_number (str): Destination phone number.
        body (str): SMS message body.

    Returns:
        str: The message SID if sent successfully, otherwise None.
    """
    try:
        client = Client(account_sid, auth_token)
        message = client.messages.create(
            body=body,
            from_=from_number,
            to=to_number
        )
        return message.sid
    except Exception as e:
        print(f"Failed to send SMS: {e}")
        return None

def send_slack_message(token, channel, body):
    """
    Send a message to Slack using the Slack SDK.

    Args:
        token (str): Slack API token.
        channel (str): Slack channel to send the message to.
        body (str): The message body to send.

    Returns:
        str: The message timestamp if successful, otherwise None.
    """
    if not token or not channel:
        print("Slack token or channel not configured properly")
        return None

    if not channel.startswith('#'):
        channel = f'#{channel}'

    try:
        client = WebClient(token=token)
        formatted_body = f"*E-MAIL ALERT:*\n{body}"

        response = client.chat_postMessage(
            channel=channel,
            text=formatted_body,
            parse='full'  # Enable parsing of markup
        )
        return response["ts"]
    except SlackApiError as e:
        print(f"Failed to send Slack message: {e}")
        return None
    except Exception as e:
        print(f"Unexpected error sending Slack message: {e}")
        return None

def mark_as_read(imap, email_id):
    """
    Mark an email as read using the IMAP STORE command.

    Args:
        imap (IMAP4_SSL): Authenticated IMAP connection object.
        email_id (str): ID of the email to mark as read.

    Returns:
        bool: True if marked successfully, False otherwise.
    """
    try:
        imap.store(email_id, '+FLAGS', '\\Seen')
        return True
    except Exception as e:
        print(f"Failed to mark email as read: {e}")
        return False

def main():
    """
    Main function to monitor Gmail for unread emails and send notifications via SMS or Slack.
    """
    print("Starting email monitoring service...")

    while True:
        imap = None
        try:
            # Load configuration variables
            config = load_config()

            # Gmail credentials
            GMAIL_USERNAME = config.get('Gmail', 'username')
            GMAIL_PASSWORD = config.get('Gmail', 'password')

            # Twilio configuration
            TWILIO_ENABLED = config.getboolean('Twilio', 'enabled', fallback=True)
            if TWILIO_ENABLED:
                print("SMS notifications are enabled")
                TWILIO_ACCOUNT_SID = config.get('Twilio', 'account_sid')
                TWILIO_AUTH_TOKEN = config.get('Twilio', 'auth_token')
                TWILIO_FROM_NUMBER = config.get('Twilio', 'from_number')
                DESTINATION_NUMBER = config.get('Twilio', 'destination_number')
                MAX_SMS_LENGTH = config.getint('Settings', 'max_sms_length', fallback=1600)
            else:
                print("SMS notifications are disabled")

            # Slack configuration
            SLACK_ENABLED = config.getboolean('Slack', 'enabled', fallback=False)
            if SLACK_ENABLED:
                print("Slack notifications are enabled")
                SLACK_TOKEN = config.get('Slack', 'token')
                SLACK_CHANNEL = config.get('Slack', 'channel')
            else:
                print("Slack notifications are disabled")

            if not TWILIO_ENABLED and not SLACK_ENABLED:
                print("No notification methods enabled. Retrying in 30 seconds...")
                time.sleep(30)
                continue

            # Connect to Gmail
            imap = connect_to_gmail(GMAIL_USERNAME, GMAIL_PASSWORD)
            if not imap:
                print("Failed to connect to Gmail, retrying in 30 seconds...")
                time.sleep(30)
                continue

            # Fetch unread emails
            unread_emails = fetch_unread_emails(imap)
            print(f"Found {len(unread_emails)} unread emails.")

            for email_id, msg in unread_emails:
                subject = str(msg.get('subject', ''))
                body = extract_email_body(msg)
                success = False

                print(f"Processingemail with subject: {subject}")

                # Send SMS via Twilio if enabled
                if TWILIO_ENABLED:
                    sms_body = body[:MAX_SMS_LENGTH] if len(body) > MAX_SMS_LENGTH else body
                    sms_sid = send_sms_via_twilio(
                        account_sid=TWILIO_ACCOUNT_SID,
                        auth_token=TWILIO_AUTH_TOKEN,
                        from_number=TWILIO_FROM_NUMBER,
                        to_number=DESTINATION_NUMBER,
                        body=sms_body
                    )
                    if sms_sid:
                        print(f"Sent SMS with SID: {sms_sid}")
                        success = True
                    else:
                        print("Failed to send SMS")

                # Send to Slack if enabled
                if SLACK_ENABLED:
                    slack_ts = send_slack_message(
                        token=SLACK_TOKEN,
                        channel=SLACK_CHANNEL,
                        body=body  # Full email body is sent to Slack
                    )
                    if slack_ts:
                        print(f"Sent Slack message with timestamp: {slack_ts}")
                        success = True
                    else:
                        print("Failed to send Slack message")

                # Mark as read if either SMS or Slack message was sent successfully
                if success:
                    if mark_as_read(imap, email_id):
                        print(f"Marked email {email_id} as read")
                    else:
                        print(f"Failed to mark email {email_id} as read")
                else:
                    print(f"No successful notifications sent for email {email_id}")

        except Exception as e:
            print(f"An error occurred in the main loop: {e}")
        finally:
            # Always try to properly close the IMAP connection
            if imap:
                try:
                    imap.logout()
                except Exception as e:
                    print(f"Error while logging out from IMAP: {e}")
            print("Waiting 30 seconds before next check...")
            time.sleep(30)

if __name__ == '__main__':
    main()


