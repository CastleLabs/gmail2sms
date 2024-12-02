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
    Load configuration variables from a specified INI configuration file.

    This function reads the configuration parameters required for the email monitoring service
    from an INI file using the built-in `configparser` module. It is essential for setting up
    the necessary credentials and settings for Gmail, Twilio, and Slack integrations.

    Args:
        config_file (str): The path to the configuration INI file. Defaults to '/var/www/html/config.ini'.

    Returns:
        configparser.ConfigParser: An instance containing the loaded configuration data.

    Raises:
        Exception: If the configuration file cannot be read or is missing, an exception is raised
        indicating the failure to read the config file.

    Example:
        config = load_config('/path/to/config.ini')
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
    Establish a secure connection to Gmail's IMAP server and authenticate using provided credentials.

    This function uses the `imaplib` module to connect to Gmail's IMAP server over SSL and logs in
    with the provided username and password. This connection is necessary to access and fetch emails
    from the Gmail inbox.

    Args:
        username (str): The Gmail email address (username) to authenticate with.
        password (str): The password or app-specific password for the Gmail account.

    Returns:
        imaplib.IMAP4_SSL: An authenticated IMAP connection object if the login is successful.
        None: If the connection or authentication fails.

    Raises:
        Exception: If the connection to Gmail fails or authentication is unsuccessful,
        an exception is caught, and None is returned.

    Example:
        imap = connect_to_gmail('user@gmail.com', 'password123')
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
    Retrieve all unread emails from the Gmail inbox.

    This function selects the 'inbox' folder and searches for all messages that are marked as 'UNSEEN' (unread).
    It then fetches each unread email's full message data in RFC822 format and parses it into an email message object
    using the `email` module.

    Args:
        imap (imaplib.IMAP4_SSL): An authenticated IMAP connection object.

    Returns:
        list: A list of tuples, where each tuple contains the email ID and the corresponding email.message.Message object.
              If no unread emails are found or an error occurs, an empty list is returned.

    Raises:
        Exception: If an error occurs during the selection of the inbox or fetching of emails, an exception is caught,
        and an empty list is returned.

    Example:
        unread_emails = fetch_unread_emails(imap)
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
                    # Parse the email message from bytes
                    msg = email.message_from_bytes(response[1])
                    emails.append((email_id, msg))  # Append the email ID and message object to the list
        return emails
    except Exception as e:
        print(f"Failed to fetch emails: {e}")
        return []

def extract_email_body(msg):
    """
    Extract the plain text body from an email message object.

    This function handles both multipart and non-multipart email messages.
    For multipart messages, it walks through the message parts and looks for a 'text/plain' part without
    a 'Content-Disposition' header (which usually indicates attachments). For non-multipart messages,
    it directly decodes the payload.

    Args:
        msg (email.message.Message): The email message object from which to extract the body.

    Returns:
        str: The decoded plain text body of the email message. If the body cannot be extracted,
             an empty string is returned.

    Raises:
        Exception: If an error occurs during the extraction and decoding of the email body,
        an exception is caught, and an empty string is returned.

    Example:
        body = extract_email_body(email_message)
    """
    try:
        if msg.is_multipart():
            # Iterate over email parts
            for part in msg.walk():
                # Look for 'text/plain' content type without 'Content-Disposition' (e.g., not attachments)
                if part.get_content_type() == "text/plain" and not part.get("Content-Disposition"):
                    # Decode the email content from bytes to string
                    return part.get_payload(decode=True).decode("utf-8")
        else:
            # For non-multipart messages, decode the payload directly
            return msg.get_payload(decode=True).decode("utf-8")
    except Exception as e:
        print(f"Failed to extract email body: {e}")
        return ""

def send_sms_via_twilio(account_sid, auth_token, from_number, to_number, body):
    """
    Send an SMS message using the Twilio API.

    This function initializes the Twilio REST client with the provided Account SID and Auth Token.
    It then creates and sends an SMS message from the specified Twilio phone number to the destination number
    with the given message body.

    Args:
        account_sid (str): The Account SID from your Twilio account dashboard.
        auth_token (str): The Auth Token from your Twilio account dashboard.
        from_number (str): The Twilio phone number (in E.164 format) to send the SMS from.
        to_number (str): The destination phone number (in E.164 format) to send the SMS to.
        body (str): The text content of the SMS message.

    Returns:
        str: The unique SID identifier of the sent message if successful.
        None: If the message fails to send.

    Raises:
        Exception: If an error occurs during the message creation and sending process, an exception is caught,
        and None is returned.

    Example:
        message_sid = send_sms_via_twilio(account_sid, auth_token, '+1234567890', '+0987654321', 'Hello, World!')
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

def send_slack_message(token, channel, subject, body):
    """
    Send a message to a Slack channel using the Slack SDK, with the subject in bold.

    This function initializes the Slack WebClient with the provided API token.
    It then sends a message to the specified Slack channel with the given subject and body,
    formatting the subject in bold.

    Args:
        token (str): The Slack API token for authentication. This token should have the necessary permissions to post messages.
        channel (str): The Slack channel name or ID to send the message to. If a channel name is provided, it should include the '#' prefix.
        subject (str): The subject of the email to be formatted in bold.
        body (str): The text content of the email body to be sent to the Slack channel.

    Returns:
        str: The timestamp ('ts') of the message in Slack if the message was sent successfully.
        None: If the message fails to send.

    Raises:
        SlackApiError: If the Slack API returns an error during the message posting process.
        Exception: If any other unexpected error occurs.

    Example:
        slack_ts = send_slack_message('xoxb-your-token', '#general', 'Subject Line', 'Email body content.')
    """
    if not token or not channel:
        print("Slack token or channel not configured properly")
        return None

    if not channel.startswith('#'):
        channel = f'#{channel}'

    try:
        client = WebClient(token=token)
        # Format the subject in bold by wrapping it with asterisks
        formatted_body = f"*{subject}*: {body}"

        response = client.chat_postMessage(
            channel=channel,
            text=formatted_body,
            parse='full'  # Enable parsing of markup (e.g., bold, italics)
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
    Mark an email as read on the IMAP server using the email ID.

    This function uses the IMAP STORE command to add the '\\Seen' flag to the specified email,
    effectively marking it as read in the mailbox. This prevents the email from being processed again
    in subsequent iterations.

    Args:
        imap (imaplib.IMAP4_SSL): An authenticated IMAP connection object.
        email_id (bytes): The unique identifier of the email to mark as read.

    Returns:
        bool: True if the email was successfully marked as read, False otherwise.

    Raises:
        Exception: If an error occurs during the execution of the STORE command, an exception is caught,
        and False is returned.

    Example:
        success = mark_as_read(imap, b'123')
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

    This function runs an infinite loop that performs the following steps:
    - Loads configuration settings from an INI file.
    - Checks if SMS (Twilio) and/or Slack notifications are enabled.
    - Connects to Gmail using IMAP with the provided credentials.
    - Fetches unread emails from the inbox.
    - Processes each unread email by extracting the subject and body.
    - Constructs messages for SMS and Slack, formatting the Slack subject in bold.
    - Sends the constructed messages via SMS and/or Slack based on the configuration.
    - Marks the email as read if the notification was sent successfully.
    - Waits for a specified interval before repeating the process.

    The function ensures that resources like the IMAP connection are properly closed even if an error occurs.

    Raises:
        Exception: Any uncaught exceptions will be printed to the console.

    Example:
        if __name__ == '__main__':
            main()
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

                print(f"Processing email with subject: {subject}")

                # Construct the message as "SUBJECT:BODY" for SMS
                sms_message_body = f"{subject}:{body}"

                # Send SMS via Twilio if enabled
                if TWILIO_ENABLED:
                    # Truncate the message body if it exceeds the maximum SMS length
                    sms_body = sms_message_body[:MAX_SMS_LENGTH] if len(sms_message_body) > MAX_SMS_LENGTH else sms_message_body
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
                        subject=subject,  # Pass the subject separately to format it in bold
                        body=body         # Pass the body as is
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



