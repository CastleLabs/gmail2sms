#!/usr/bin/env python3

"""
Email-to-SMS Script Using IMAP and Twilio

This script runs continuously to monitor a Gmail inbox for new emails.
When new (unread) emails are found, it:
1. Extracts the email body
2. Sends the content via SMS using Twilio
3. Marks the email as read to prevent duplicate processing

The script runs in an infinite loop with a 30-second pause between checks.
All configuration settings (Gmail credentials, Twilio settings) are loaded
from an external config.ini file.

Required Python packages:
- twilio: For sending SMS messages
- configparser: For reading the config file (standard library)
- imaplib: For Gmail IMAP connection (standard library)
- email: For email parsing (standard library)

Author: Seth Morrow
Last Updated: 2024-11-29
"""

import imaplib  # For Gmail IMAP connection
import email    # For parsing email messages
from email.header import decode_header  # For decoding email headers
import configparser  # For reading config.ini
from twilio.rest import Client  # For sending SMS
import time  # For sleep functionality between checks

def load_config(config_file='/var/www/html/plcalerts/config.ini'):
    """
    Loads and parses the configuration file containing credentials and settings.
    
    Args:
        config_file (str): Path to the configuration file (INI format)
    
    Returns:
        configparser.ConfigParser: Parsed configuration object
    
    Raises:
        Exception: If the config file cannot be read or doesn't exist
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
    Establishes an IMAP connection to Gmail and authenticates.
    
    Args:
        username (str): Gmail email address
        password (str): Gmail password or App-specific password
    
    Returns:
        imaplib.IMAP4_SSL: Connected IMAP client object, or None if connection fails
    """
    try:
        imap = imaplib.IMAP4_SSL("imap.gmail.com")  # Create SSL connection
        imap.login(username, password)               # Authenticate
        return imap
    except Exception as e:
        print(f"Failed to connect to Gmail: {e}")
        return None

def fetch_unread_emails(imap):
    """
    Retrieves all unread emails from the Gmail inbox.
    
    Args:
        imap (imaplib.IMAP4_SSL): Connected IMAP client
    
    Returns:
        list: Tuples of (email_id, email_message) for each unread email
    """
    try:
        imap.select("inbox")  # Select the inbox folder
        status, messages = imap.search(None, 'UNSEEN')  # Find unread messages
        if status != "OK":
            print("No unread emails found.")
            return []
        
        # Process each unread email
        email_ids = messages[0].split()  # Get list of message IDs
        emails = []
        for email_id in email_ids:
            # Fetch the email message by ID
            res, msg = imap.fetch(email_id, "(RFC822)")
            for response in msg:
                if isinstance(response, tuple):
                    # Parse the email message and store with its ID
                    msg = email.message_from_bytes(response[1])
                    emails.append((email_id, msg))
        return emails
    except Exception as e:
        print(f"Failed to fetch emails: {e}")
        return []

def extract_email_body(msg):
    """
    Extracts the plain text content from an email message.
    
    Args:
        msg (email.message.Message): Email message object
    
    Returns:
        str: Plain text content of the email, or empty string if extraction fails
    
    Note:
        Handles both multipart and simple text emails
    """
    try:
        if msg.is_multipart():
            # For multipart messages, find the plain text part
            for part in msg.walk():
                if part.get_content_type() == "text/plain" and not part.get("Content-Disposition"):
                    return part.get_payload(decode=True).decode("utf-8")
        else:
            # For simple messages, just get the body
            return msg.get_payload(decode=True).decode("utf-8")
    except Exception as e:
        print(f"Failed to extract email body: {e}")
        return ""

def send_sms_via_twilio(account_sid, auth_token, from_number, to_number, body):
    """
    Sends an SMS message using the Twilio API.
    
    Args:
        account_sid (str): Twilio Account SID
        auth_token (str): Twilio Auth Token
        from_number (str): Twilio phone number to send from
        to_number (str): Recipient's phone number
        body (str): Message content
    
    Returns:
        str: Message SID if successful, None if sending fails
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

def mark_as_read(imap, email_id):
    """
    Marks an email as read in Gmail.
    
    Args:
        imap (imaplib.IMAP4_SSL): Connected IMAP client
        email_id (bytes): ID of the email to mark as read
    
    Returns:
        bool: True if successful, False if marking as read fails
    """
    try:
        imap.store(email_id, '+FLAGS', '\\Seen')
        return True
    except Exception as e:
        print(f"Failed to mark email as read: {e}")
        return False

def main():
    """
    Main execution function that runs continuously.
    
    The function:
    1. Loads configuration from config.ini
    2. Connects to Gmail via IMAP
    3. Checks for unread emails
    4. For each unread email:
       - Extracts the body
       - Sends it via SMS
       - Marks the email as read if SMS is sent successfully
    5. Waits 30 seconds before next check
    
    The function runs in an infinite loop and includes error handling
    to prevent crashes and ensure proper resource cleanup.
    """
    print("Starting email-to-SMS service...")
    
    while True:  # Infinite loop to keep service running
        imap = None
        try:
            # Load and parse configuration
            config = load_config()
            
            # Extract all necessary credentials and settings
            GMAIL_USERNAME = config.get('Gmail', 'username')
            GMAIL_PASSWORD = config.get('Gmail', 'password')
            TWILIO_ACCOUNT_SID = config.get('Twilio', 'account_sid')
            TWILIO_AUTH_TOKEN = config.get('Twilio', 'auth_token')
            TWILIO_FROM_NUMBER = config.get('Twilio', 'from_number')
            DESTINATION_NUMBER = config.get('Twilio', 'destination_number')
            MAX_SMS_LENGTH = config.getint('Settings', 'max_sms_length', fallback=1600)

            # Establish Gmail connection
            imap = connect_to_gmail(GMAIL_USERNAME, GMAIL_PASSWORD)
            if not imap:
                print("Failed to connect to Gmail, retrying in 30 seconds...")
                time.sleep(30)
                continue

            # Process unread emails
            unread_emails = fetch_unread_emails(imap)
            print(f"Found {len(unread_emails)} unread emails.")

            # Handle each unread email
            for email_id, msg in unread_emails:
                body = extract_email_body(msg)
                
                # Truncate long messages to match SMS limitations
                if len(body) > MAX_SMS_LENGTH:
                    body = body[:MAX_SMS_LENGTH]

                # Send via SMS and handle the response
                sms_sid = send_sms_via_twilio(
                    account_sid=TWILIO_ACCOUNT_SID,
                    auth_token=TWILIO_AUTH_TOKEN,
                    from_number=TWILIO_FROM_NUMBER,
                    to_number=DESTINATION_NUMBER,
                    body=body
                )
                
                # Mark email as read only if SMS was sent successfully
                if sms_sid:
                    print(f"Sent SMS with SID: {sms_sid}")
                    if mark_as_read(imap, email_id):
                        print(f"Marked email {email_id} as read")
                    else:
                        print(f"Failed to mark email {email_id} as read")

        except Exception as e:
            print(f"An error occurred in the main loop: {e}")
        finally:
            # Cleanup: Always close IMAP connection properly
            if imap:
                try:
                    imap.logout()
                except:
                    pass
            print("Waiting 30 seconds before next check...")
            time.sleep(30)

# Script entry point
if __name__ == '__main__':
    main()
