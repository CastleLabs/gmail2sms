# gmail2sms Documentaion
This document provides an in-depth analysis and explanation of the Python script designed to monitor a Gmail inbox for unread emails and send notifications via Twilio SMS and/or Slack messages. 

---

## Table of Contents

1. [Introduction](#introduction)
2. [Prerequisites](#prerequisites)
3. [Configuration File Structure](#configuration-file-structure)
4. [Function Breakdown](#function-breakdown)
   - [1. `load_config()`](#1-load_config)
   - [2. `connect_to_gmail()`](#2-connect_to_gmail)
   - [3. `fetch_unread_emails()`](#3-fetch_unread_emails)
   - [4. `extract_email_body()`](#4-extract_email_body)
   - [5. `send_sms_via_twilio()`](#5-send_sms_via_twilio)
   - [6. `send_slack_message()`](#6-send_slack_message)
   - [7. `mark_as_read()`](#7-mark_as_read)
   - [8. `main()`](#8-main)
5. [Script Workflow](#script-workflow)
6. [Error Handling and Logging](#error-handling-and-logging)
7. [Security Considerations](#security-considerations)
8. [Potential Improvements](#potential-improvements)
9. [Conclusion](#conclusion)

---

## Introduction

The script automates the process of monitoring a Gmail inbox for unread emails and sending notifications about them via SMS using Twilio and/or messages to a Slack channel. It's designed to run continuously, checking for new emails at regular intervals, and ensures that notifications are sent promptly for any new messages.

---

## Prerequisites

- **Python 3.x**: The script is written in Python 3, so ensure that Python 3.x is installed on your system.
- **Required Libraries**:
  - Standard libraries: `imaplib`, `email`, `email.header`, `configparser`, `time`
  - Third-party libraries: `twilio`, `slack_sdk`
- **Third-Party Accounts**:
  - **Twilio Account**: For sending SMS messages. You'll need your Account SID, Auth Token, and a Twilio phone number.
  - **Slack Workspace and App**: To send messages to Slack channels. You'll need an API token with appropriate permissions.

**Installation of Third-Party Libraries:**

```bash
pip install twilio slack_sdk
```

---

## Configuration File Structure

The script relies on a configuration file (`config.ini`) to store credentials and settings. The default path is `/var/www/html/config.ini`, but this can be modified.

### Sample `config.ini`:

```ini
[Gmail]
username = your_email@gmail.com
password = your_gmail_password_or_app_specific_password

[Twilio]
enabled = True
account_sid = your_twilio_account_sid
auth_token = your_twilio_auth_token
from_number = your_twilio_phone_number
destination_number = recipient_phone_number

[Slack]
enabled = True
token = your_slack_api_token
channel = your_slack_channel_name

[Settings]
max_sms_length = 1600
```

**Important Notes:**

- **Gmail Password**: If you have two-factor authentication enabled on your Gmail account, you must use an app-specific password.
- **Twilio and Slack**: Ensure that you have valid credentials and that your account is set up to send messages.

---

## Function Breakdown

### 1. `load_config(config_file='/var/www/html/config.ini')`

#### **Purpose:**

Loads configuration variables from the specified INI file.

#### **Detailed Explanation:**

- **Imports and Initialization:**

  ```python
  import configparser
  ```

  - Uses the `configparser` module to handle INI files.

- **Function Logic:**

  ```python
  def load_config(config_file='/var/www/html/config.ini'):
      print(f"Attempting to load config from: {config_file}")
      config = configparser.ConfigParser()
      read_files = config.read(config_file)
      if not read_files:
          print(f"Failed to read config file: {config_file}")
          raise Exception(f"Could not read config file: {config_file}")
      print(f"Successfully loaded config with sections: {config.sections()}")
      return config
  ```

  - Attempts to read the configuration file.
  - Checks if the file was successfully read; if not, raises an exception.
  - Prints out the sections loaded for confirmation.

#### **Key Points:**

- **Error Handling:** If the configuration file cannot be read (e.g., due to incorrect path or permissions), the function raises an exception, which should be caught and handled in the main script.
- **Extensibility:** The function can be modified to accept different configuration file paths or formats if needed.

---

### 2. `connect_to_gmail(username, password)`

#### **Purpose:**

Connects to Gmail's IMAP server using the provided credentials.

#### **Detailed Explanation:**

- **Imports and Initialization:**

  ```python
  import imaplib
  ```

  - Uses the `imaplib` library to interact with the IMAP server.

- **Function Logic:**

  ```python
  def connect_to_gmail(username, password):
      try:
          imap = imaplib.IMAP4_SSL("imap.gmail.com")
          imap.login(username, password)
          return imap
      except Exception as e:
          print(f"Failed to connect to Gmail: {e}")
          return None
  ```

  - Establishes a secure connection to Gmail's IMAP server using SSL.
  - Attempts to log in with the provided username and password.
  - Returns an `imaplib.IMAP4_SSL` object upon successful connection.

#### **Key Points:**

- **Security:** Uses SSL to ensure that the connection to the email server is encrypted.
- **Error Handling:** If the login fails (e.g., incorrect credentials, network issues), the function prints an error message and returns `None`.

#### **Gmail App Passwords:**

- If two-factor authentication is enabled on the Gmail account, an app-specific password is required.
- App passwords can be generated in the Google account settings under "Security".

---

### 3. `fetch_unread_emails(imap)`

#### **Purpose:**

Fetches unread emails from the Gmail inbox.

#### **Detailed Explanation:**

- **Imports and Initialization:**

  ```python
  import email
  ```

  - Uses the `email` library to parse email messages.

- **Function Logic:**

  ```python
  def fetch_unread_emails(imap):
      try:
          imap.select("inbox")
          status, messages = imap.search(None, 'UNSEEN')
          if status != "OK":
              print("No unread emails found.")
              return []

          email_ids = messages[0].split()
          emails = []
          for email_id in email_ids:
              res, msg = imap.fetch(email_id, "(RFC822)")
              for response in msg:
                  if isinstance(response, tuple):
                      msg = email.message_from_bytes(response[1])
                      emails.append((email_id, msg))
          return emails
      except Exception as e:
          print(f"Failed to fetch emails: {e}")
          return []
  ```

  - Selects the "inbox" mailbox for operation.
  - Searches for emails with the `UNSEEN` flag.
  - Fetches each email's full content using the email ID.
  - Parses the raw email data into a structured `email.message.Message` object.

#### **Key Points:**

- **IMAP Commands:**
  - `select("inbox")`: Specifies the mailbox to operate on.
  - `search(None, 'UNSEEN')`: Searches for emails that are unread.
  - `fetch(email_id, "(RFC822)")`: Retrieves the full email message.

- **Email Parsing:**
  - Converts the raw bytes into an email message object that can be easily manipulated.

- **Error Handling:**
  - If any step fails, the function logs the error and returns an empty list.

---

### 4. `extract_email_body(msg)`

#### **Purpose:**

Extracts the plain text body from an email message.

#### **Detailed Explanation:**

- **Function Logic:**

  ```python
  def extract_email_body(msg):
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
  ```

  - Checks if the email is multipart (contains multiple parts like attachments, HTML content, etc.).
  - If multipart, iterates through its parts to find the plain text version.
  - Decodes the email body from bytes to a UTF-8 string.

#### **Key Points:**

- **Multipart Emails:**
  - Emails can have multiple content types (plain text, HTML, attachments).
  - The function searches for the "text/plain" part, which is the plain text body.

- **Payload Decoding:**
  - Uses `get_payload(decode=True)` to decode the base64 or quoted-printable encoded content.
  - Ensures that the returned string is in UTF-8 format.

- **Error Handling:**
  - If extraction fails, the function logs the error and returns an empty string.

---

### 5. `send_sms_via_twilio(account_sid, auth_token, from_number, to_number, body)`

#### **Purpose:**

Sends an SMS message using Twilio's API.

#### **Detailed Explanation:**

- **Imports and Initialization:**

  ```python
  from twilio.rest import Client
  ```

  - Uses Twilio's REST API client for sending SMS messages.

- **Function Logic:**

  ```python
  def send_sms_via_twilio(account_sid, auth_token, from_number, to_number, body):
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
  ```

  - Initializes the Twilio client with the provided credentials.
  - Creates and sends the message using `client.messages.create()`.
  - Returns the message SID upon success.

#### **Key Points:**

- **Phone Numbers:**
  - `from_number`: Must be a valid Twilio phone number.
  - `to_number`: The recipient's phone number, should include the country code.

- **Message Length:**
  - SMS messages have a character limit (160 characters for a single message).
  - Messages longer than the limit may be split into multiple messages.

- **Error Handling:**
  - Catches exceptions such as authentication errors, invalid numbers, or network issues.

---

### 6. `send_slack_message(token, channel, body)`

#### **Purpose:**

Sends a message to a Slack channel using the Slack API.

#### **Detailed Explanation:**

- **Imports and Initialization:**

  ```python
  from slack_sdk import WebClient
  from slack_sdk.errors import SlackApiError
  ```

  - Uses Slack's SDK for Python to interact with the Slack API.

- **Function Logic:**

  ```python
  def send_slack_message(token, channel, body):
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
              parse='full'
          )
          return response["ts"]
      except SlackApiError as e:
          print(f"Failed to send Slack message: {e}")
          return None
      except Exception as e:
          print(f"Unexpected error sending Slack message: {e}")
          return None
  ```

  - Validates the token and channel parameters.
  - Ensures the channel name starts with a `#`.
  - Formats the message using Slack's markdown (e.g., `*bold text*`).
  - Sends the message using `client.chat_postMessage()`.

#### **Key Points:**

- **Channel Formatting:**
  - The channel must include the `#` prefix.
  - Ensure that the bot has been invited to the channel or has the appropriate permissions.

- **Message Formatting:**
  - Slack uses a variant of Markdown for message formatting.
  - `parse='full'` allows for formatting and link parsing in the message.

- **Error Handling:**
  - Differentiates between Slack API errors and general exceptions.
  - Logs errors for troubleshooting.

---

### 7. `mark_as_read(imap, email_id)`

#### **Purpose:**

Marks an email as read in the Gmail inbox.

#### **Detailed Explanation:**

- **Function Logic:**

  ```python
  def mark_as_read(imap, email_id):
      try:
          imap.store(email_id, '+FLAGS', '\\Seen')
          return True
      except Exception as e:
          print(f"Failed to mark email as read: {e}")
          return False
  ```

  - Uses the `store` method to modify the flags of the email.
  - The `+FLAGS` parameter adds the specified flag (`\\Seen`), marking the email as read.

#### **Key Points:**

- **IMAP Flags:**
  - `\\Seen`: Indicates that the email has been read.
  - Modifying flags helps in managing email states to prevent reprocessing.

- **Error Handling:**
  - Catches exceptions during the flag modification process.

---

### 8. `main()`

#### **Purpose:**

The central function that orchestrates the email monitoring and notification process.

#### **Detailed Explanation:**

- **Function Logic Overview:**

  ```python
  def main():
      print("Starting email monitoring service...")

      while True:
          imap = None
          try:
              # Configuration Loading
              config = load_config()

              # Extract Credentials and Settings
              GMAIL_USERNAME = config.get('Gmail', 'username')
              GMAIL_PASSWORD = config.get('Gmail', 'password')
              # Twilio and Slack settings are similarly extracted

              # Connect to Gmail
              imap = connect_to_gmail(GMAIL_USERNAME, GMAIL_PASSWORD)
              if not imap:
                  # Handle failed connection
                  continue

              # Fetch Unread Emails
              unread_emails = fetch_unread_emails(imap)

              # Process Each Email
              for email_id, msg in unread_emails:
                  subject = str(msg.get('subject', ''))
                  body = extract_email_body(msg)
                  success = False

                  # Send Notifications
                  if TWILIO_ENABLED:
                      # Send SMS via Twilio
                      pass

                  if SLACK_ENABLED:
                      # Send Message via Slack
                      pass

                  # Mark as Read if Successful
                  if success:
                      mark_as_read(imap, email_id)

          except Exception as e:
              print(f"An error occurred in the main loop: {e}")
          finally:
              if imap:
                  try:
                      imap.logout()
                  except Exception as e:
                      print(f"Error while logging out from IMAP: {e}")
              print("Waiting 30 seconds before next check...")
              time.sleep(30)
  ```

#### **Key Points:**

- **Infinite Loop:**
  - The script runs continuously, checking for new emails every 30 seconds.
  - The delay can be adjusted as needed.

- **Configuration Reloading:**
  - Reloads the configuration in each iteration to capture any changes made to the `config.ini` file without restarting the script.

- **Notification Logic:**
  - Determines which notification methods are enabled.
  - Sends notifications accordingly and updates the `success` flag.

- **Error Handling:**
  - Catches exceptions in the main loop to prevent the script from crashing.
  - Ensures resources are cleaned up in the `finally` block.

- **Resource Management:**
  - Properly logs out of the IMAP session to free up resources and maintain server compliance.

---

## Script Workflow

1. **Initialization:**
   - Prints a startup message.
   - Enters an infinite loop to continuously monitor the inbox.

2. **Configuration Loading:**
   - Reads settings from the `config.ini` file.
   - Extracts credentials and settings for Gmail, Twilio, and Slack.

3. **Gmail Connection:**
   - Connects to Gmail's IMAP server using the provided credentials.
   - Handles failed connections by retrying after a delay.

4. **Email Retrieval:**
   - Fetches unread emails from the inbox.
   - Logs the number of unread emails found.

5. **Email Processing:**
   - Iterates over each unread email.
   - Extracts the subject and body.
   - Initializes a `success` flag to track notification status.

6. **Notification Sending:**
   - **Twilio SMS Notification:**
     - Checks if Twilio notifications are enabled.
     - Sends an SMS message containing the email body or a truncated version if it exceeds `MAX_SMS_LENGTH`.
     - Updates the `success` flag based on the result.
   - **Slack Notification:**
     - Checks if Slack notifications are enabled.
     - Sends a formatted message to the specified Slack channel.
     - Updates the `success` flag based on the result.

7. **Email Management:**
   - If at least one notification was successful, marks the email as read to prevent reprocessing.

8. **Error Handling:**
   - Catches and logs any exceptions that occur during processing.
   - Ensures that resources like the IMAP connection are properly closed.

9. **Delay Between Checks:**
   - Waits for 30 seconds before the next iteration of the loop.

---

## Error Handling and Logging

- **Verbose Output:**
  - The script prints messages to the console at each significant step, aiding in monitoring and debugging.

- **Exception Handling:**
  - Uses try-except blocks to handle exceptions without crashing the script.
  - Specific exceptions are caught where appropriate (e.g., `SlackApiError`).

- **Resource Cleanup:**
  - The `finally` block in the main loop ensures that the IMAP connection is logged out, even if an error occurs.

---

## Security Considerations

- **Credential Management:**
  - Sensitive information is stored in the `config.ini` file.
  - Ensure that the configuration file has appropriate permissions (e.g., only readable by the user running the script).

- **Secure Connections:**
  - Uses SSL/TLS for IMAP connections and HTTPS for API requests to Twilio and Slack.

- **Error Messages:**
  - Avoid logging sensitive information in error messages or console output.

- **App Passwords for Gmail:**
  - Use app-specific passwords if two-factor authentication is enabled for added security.

---

## Potential Improvements

- **Logging Framework:**
  - Implement a logging framework (e.g., Python's `logging` module) to manage log levels and outputs more effectively.

- **Email Content Filtering:**
  - Add functionality to filter emails based on sender, subject, or content keywords.

- **HTML Email Support:**
  - Extend the `extract_email_body()` function to handle HTML content if necessary.

- **Asynchronous Execution:**
  - Use asynchronous programming (e.g., `asyncio`) to handle multiple emails and network requests concurrently.

- **Notification Customization:**
  - Allow users to customize the notification message format, including additional email metadata (e.g., sender, timestamp).

- **Persistent State:**
  - Implement a mechanism to track processed emails across restarts (e.g., using a database or a local file) to prevent duplicate notifications.

- **Unit Testing:**
  - Add unit tests for each function to ensure reliability and facilitate future development.

- **Dockerization:**
  - Containerize the application using Docker for easier deployment and isolation.

---

## Conclusion

This script provides a robust solution for monitoring a Gmail inbox and sending notifications via SMS and Slack. By modularizing the code into functions, it enhances readability and maintainability. The use of configuration files allows for easy adjustments without modifying the codebase. With proper error handling and security considerations, the script is suitable for deployment in various environments.

---

**Disclaimer:** Ensure compliance with Gmail's Terms of Service and privacy policies when accessing and processing email data. Similarly, adhere to Twilio's and Slack's acceptable use policies when sending messages.

