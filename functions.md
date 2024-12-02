### **1. `load_config(config_file='/var/www/html/config.ini')`**
**Purpose:**  
Loads configuration variables from a specified INI file to use across the script.

**Key Points:**
- Reads the configuration file located at `config_file` (default path is `/var/www/html/config.ini`).
- Uses the `ConfigParser` module to parse the INI file.
- If the file cannot be read, raises an exception.

**Returns:**  
A `ConfigParser` object containing the parsed configuration sections and options.

**Raises:**  
An exception if the file is missing or unreadable.

---

### **2. `connect_to_gmail(username, password)`**
**Purpose:**  
Connects to Gmail's IMAP server and authenticates using provided credentials.

**Key Points:**
- Establishes a secure connection to Gmail's IMAP server (`imap.gmail.com`).
- Logs in with the provided username and password.
- Returns an `IMAP4_SSL` object representing the authenticated connection.

**Returns:**  
An authenticated `imaplib.IMAP4_SSL` object or `None` if the connection fails.

**Raises:**  
Exception if the login process encounters an error.

---

### **3. `fetch_unread_emails(imap)`**
**Purpose:**  
Fetches unread emails from the Gmail inbox.

**Key Points:**
- Selects the inbox folder using IMAP commands.
- Searches for unread messages (`UNSEEN` flag).
- Retrieves email messages and their IDs as tuples for further processing.

**Returns:**  
A list of tuples containing email IDs and their corresponding email message objects.

**Raises:**  
Exception if any error occurs during fetching or parsing emails.

---

### **4. `extract_email_body(msg)`**
**Purpose:**  
Extracts the plain text content from an email message.

**Key Points:**
- Checks if the email is multipart; if yes, iterates through its parts to find plain text.
- Decodes the payload (email body) to a UTF-8 string.
- Handles single-part emails directly.

**Returns:**  
The plain text body of the email as a string or an empty string if extraction fails.

**Raises:**  
Exception if any issue occurs while decoding the email body.

---

### **5. `send_sms_via_twilio(account_sid, auth_token, from_number, to_number, body)`**
**Purpose:**  
Sends an SMS message using the Twilio API.

**Key Points:**
- Authenticates using Twilio’s `account_sid` and `auth_token`.
- Sends a message from `from_number` to `to_number` with the specified `body`.
- Leverages Twilio’s Python SDK for message creation and transmission.

**Returns:**  
The message SID if sent successfully, or `None` if an error occurs.

**Raises:**  
Exception if there is an issue during the SMS sending process.

---

### **6. `send_slack_message(token, channel, body)`**
**Purpose:**  
Sends a notification message to a Slack channel using the Slack API.

**Key Points:**
- Uses Slack’s `WebClient` to post messages.
- Prepends the channel name with `#` if not already provided.
- Formats the message body with markup for Slack readability.
- Handles errors specifically from Slack API and general exceptions.

**Returns:**  
The message timestamp (`ts`) if sent successfully, or `None` if an error occurs.

**Raises:**  
- `SlackApiError` for Slack-specific errors.
- General exceptions for other issues.

---

### **7. `mark_as_read(imap, email_id)`**
**Purpose:**  
Marks an email as read in the Gmail inbox.

**Key Points:**
- Uses IMAP’s `STORE` command to add the `\\Seen` flag to the specified email.
- Requires the email ID and an authenticated IMAP connection.

**Returns:**  
`True` if the operation succeeds, `False` otherwise.

**Raises:**  
Exception if the IMAP operation encounters an error.

---

### **8. `main()`**
**Purpose:**  
Monitors Gmail for unread emails and sends notifications via Twilio SMS and/or Slack.

**Key Points:**
- Runs an infinite loop to continuously check for new unread emails.
- Loads configuration settings for Gmail, Twilio, and Slack.
- Connects to Gmail and retrieves unread emails.
- Processes each email:
  - Extracts the body and sends notifications via Twilio and/or Slack if enabled.
  - Marks the email as read if at least one notification method succeeds.
- Handles errors gracefully and retries after a delay.

**Returns:**  
None (entry point of the script).

**Raises:**  
Handles exceptions internally and logs errors.

---

### Script Workflow:
1. **Configuration Loading:**
   - Reads credentials and settings for Gmail, Twilio, and Slack.
2. **Email Monitoring:**
   - Connects to Gmail and fetches unread emails.
3. **Notification Sending:**
   - Sends SMS (via Twilio) and/or Slack notifications for each email.
4. **Email Management:**
   - Marks emails as read after successful notification.
5. **Error Handling:**
   - Logs and retries after a delay if any issues arise.

This script ensures automated email monitoring and notification with robust error handling and extensible configuration management.
