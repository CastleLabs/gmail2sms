# Email-to-SMS Alert System

A Python-based service that monitors a Gmail inbox for new emails and forwards them as SMS messages using Twilio. Includes a secure web interface for configuration management.

## Overview

This system consists of:
- Python script for email monitoring and SMS sending
- PHP web interface for configuration management
- Configuration file for credentials and settings

## Requirements

### System Requirements
- Ubuntu Server (tested on 20.04 LTS and later)
- Python 3.x
- PHP 7.x or higher
- Apache2 web server
- Git

### Python Dependencies
- twilio
- configparser (standard library)
- imaplib (standard library)
- email (standard library)

### External Services
- Gmail account with App Password enabled
- Twilio account with SMS capabilities

## Installation

### 1. System Setup

```bash
# Update system
sudo apt update
sudo apt upgrade -y

# Install required packages
sudo apt install -y python3 python3-pip apache2 php libapache2-mod-php git
```

### 2. Project Setup

```bash
# Navigate to web directory
cd /var/www/html

# Create project directory
sudo mkdir plcalerts
cd plcalerts

# Set ownership
sudo chown -R www-data:www-data /var/www/html/plcalerts
sudo chmod -R 755 /var/www/html/plcalerts

# Clone repository
sudo -u www-data git clone https://github.com/yourusername/email-sms-alert.git .

# Install Python dependencies
sudo pip3 install twilio
```

### 3. Configuration

1. Copy the example configuration file:
```bash
sudo -u www-data cp config.ini.example config.ini
sudo chmod 640 config.ini
```

2. Edit the configuration file with your credentials:
```bash
sudo nano config.ini
```

Fill in the following details:
```ini
[Gmail]
username = your_gmail@gmail.com
password = your_gmail_app_password

[Twilio]
account_sid = your_twilio_account_sid
auth_token = your_twilio_auth_token
from_number = +1234567890
destination_number = +1234567890

[Settings]
max_sms_length = 1600
```

### 4. Web Interface Setup

1. Configure directory permissions in Apache:
```bash
sudo nano /etc/apache2/conf-available/plcalerts.conf
```

Add the following configuration:
```apache
<Directory /var/www/html/plcalerts>
    Options -Indexes +FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>
```

2. Enable the configuration and restart Apache:
```bash
sudo a2enconf plcalerts
sudo systemctl restart apache2
```

3. Update web interface credentials:
```bash
sudo nano index.php
```
Change the default login credentials:
```php
$username = 'your_chosen_username';
$password = 'your_chosen_password';
```

### 5. Service Setup

1. Create a systemd service file:
```bash
sudo nano /etc/systemd/system/email-sms.service
```

Add the following content:
```ini
[Unit]
Description=Email to SMS Alert Service
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/html/plcalerts
ExecStart=/usr/bin/python3 /var/www/html/plcalerts/script.py
Restart=always
RestartSec=30

[Install]
WantedBy=multi-user.target
```

2. Enable and start the service:
```bash
sudo systemctl enable email-sms.service
sudo systemctl start email-sms.service
```

## Usage

### Web Interface

Access the configuration interface at:
```
http://your-server-ip/plcalerts/index.php
```

### Service Management

```bash
# Check service status
sudo systemctl status email-sms.service

# Stop service
sudo systemctl stop email-sms.service

# Start service
sudo systemctl start email-sms.service

# Restart service
sudo systemctl restart email-sms.service

# View logs
sudo journalctl -u email-sms.service -f
```


## Troubleshooting

### Common Issues

1. **Service won't start**
   - Check logs: `sudo journalctl -u email-sms.service -f`
   - Verify Python dependencies
   - Check file permissions

2. **Web interface not accessible**
   - Check Apache logs
   - Verify permissions
   - Confirm Apache configuration

3. **SMS not being sent**
   - Verify Twilio credentials
   - Check network connectivity
   - Ensure proper Gmail authentication

### Log Locations
- Service logs: `journalctl -u email-sms.service`
- Apache error log: `/var/log/apache2/plcalerts_error.log`
- Apache access log: `/var/log/apache2/plcalerts_access.log`

## Maintenance

1. Regular Updates
```bash
# Update system
sudo apt update
sudo apt upgrade -y

# Update Python dependencies
sudo pip3 install --upgrade twilio
```

2. Backup Configuration
```bash
sudo cp /var/www/html/plcalerts/config.ini /backup/location/
```

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Support

For support, please open an issue in the GitHub repository or contact the maintainers.

## Acknowledgments

- Twilio for SMS API
- Gmail for email services

## Version History

- 1.0.0
    - Initial Release
    - Basic email to SMS functionality
    - Web configuration interface
