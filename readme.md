# Gmail2SMS Monitor

A service that monitors a Gmail account and forwards messages to SMS (via Twilio) and/or Slack. This system runs as a systemd service on Ubuntu servers.

## Overview

This system consists of three main components:
- A Python script that monitors Gmail and sends notifications
- A PHP web interface for configuration
- A configuration file system

## Prerequisites

- Ubuntu Server (tested on 20.04 LTS or higher)
- Python 3.8+
- PHP 7.4+
- Apache2 web server
- Gmail account with App Password configured
- Twilio account (optional)
- Slack workspace with Bot token (optional)

## Installation

### 1. System Dependencies

```bash
# Update system packages
sudo apt update
sudo apt upgrade -y

# Install required packages
sudo apt install -y python3 python3-pip python3-venv
sudo apt install -y apache2 php php-fpm

# Install Python dependencies
python3 -m venv /opt/gmail2sms/venv
source /opt/gmail2sms/venv/bin/activate
pip install imaplib3 configparser twilio slack_sdk
```

### 2. Project Setup

```bash
# Create project directory
sudo mkdir -p /opt/gmail2sms
sudo mkdir -p /var/www/html/gmail2sms

# Copy files to appropriate locations
sudo cp script.py /opt/gmail2sms/
sudo cp config.ini /etc/gmail2sms/
sudo cp index.php /var/www/html/gmail2sms/

# Set proper permissions
sudo chown -R www-data:www-data /var/www/html/gmail2sms
sudo chmod 755 /opt/gmail2sms
sudo chmod 640 /etc/gmail2sms/config.ini
sudo chown root:www-data /etc/gmail2sms/config.ini
```

### 3. Configure Systemd Service

Create a systemd service file:

```bash
sudo nano /etc/systemd/system/gmail2sms.service
```

Add the following content:

```ini
[Unit]
Description=Gmail to SMS/Slack Monitor
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/opt/gmail2sms
Environment=PATH=/opt/gmail2sms/venv/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin
ExecStart=/opt/gmail2sms/venv/bin/python3 /opt/gmail2sms/script.py
Restart=always
RestartSec=30

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl enable gmail2sms
sudo systemctl start gmail2sms
```

## Configuration

### Web Interface Setup

1. Configure Apache:

```bash
sudo nano /etc/apache2/sites-available/gmail2sms.conf
```

Add:

```apache
<Directory /var/www/html/gmail2sms>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

2. Enable the site:

```bash
sudo a2ensite gmail2sms
sudo systemctl restart apache2
```

### Initial Configuration

1. Access the web interface at `http://your-server/gmail2sms`
2. Default login credentials:
   - Username: `mike`
   - Password: `dragon13`
   
   **IMPORTANT:** Change these credentials immediately after installation!

### Required Settings

#### Gmail Setup
1. Enable 2-factor authentication in your Gmail account
2. Generate an App Password for this application
3. Use these credentials in the configuration

#### Twilio Setup (Optional)
1. Create a Twilio account
2. Get your Account SID and Auth Token
3. Purchase or use an existing Twilio phone number
4. Configure in the web interface

#### Slack Setup (Optional)
1. Create a Slack App in your workspace
2. Add Bot Token Scopes:
   - chat:write
   - chat:write.customize
3. Install the app to your workspace
4. Copy the Bot User OAuth Token
5. Configure in the web interface

## Monitoring

### Service Status

Check service status:
```bash
sudo systemctl status gmail2sms
```

View logs:
```bash
sudo journalctl -u gmail2sms -f
```

### Testing

Send a test email to your configured Gmail account. You should receive notifications via your configured methods (SMS and/or Slack) within 30 seconds.

## Troubleshooting

### Common Issues

1. Service won't start:
   - Check Python virtual environment
   - Verify file permissions
   - Check logs with `journalctl -u gmail2sms`

2. No notifications:
   - Verify Gmail credentials
   - Check Twilio/Slack credentials
   - Ensure services are enabled in config
   - Check spam folder

3. Web interface issues:
   - Verify Apache configuration
   - Check PHP logs
   - Ensure proper file permissions

### Support

For issues:
1. Check system logs
2. Verify configuration
3. Test connectivity to services
4. Check firewall settings


## Maintenance

Regular maintenance tasks:
1. Update system packages
2. Check logs for errors
3. Monitor disk space
4. Verify service status
5. Test notification delivery
6. Update credentials periodically

Remember to maintain backups of your configuration and implement monitoring for the service's health.
