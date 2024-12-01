<?php
session_start();

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Script started");

$username = 'yourUserName';
$password = 'yourPassword';

if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        if ($_POST['username'] === $username && $_POST['password'] === $password) {
            $_SESSION['logged_in'] = true;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        // Login form HTML
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Gmail2sms Configuration</title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                /* Login form styles */
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f5f5f5;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                }
                .card {
                    background-color: #fff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    max-width: 400px;
                    width: 100%;
                }
                h1 {
                    margin-top: 0;
                    font-size: 24px;
                    text-align: center;
                }
                .form-group {
                    margin-bottom: 15px;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                }
                input[type="text"],
                input[type="password"],
                input[type="email"] {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    box-sizing: border-box;
                }
                input[type="submit"] {
                    width: 100%;
                    padding: 10px;
                    background-color: #007BFF;
                    border: none;
                    border-radius: 4px;
                    color: #fff;
                    font-size: 16px;
                    cursor: pointer;
                }
                input[type="submit"]:hover {
                    background-color: #0056b3;
                }
                .error {
                    color: red;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="card">
                    <h1>Login</h1>
                    <?php if (isset($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>
                    <form method="post">
                        <input type="hidden" name="login" value="1">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <input type="submit" value="Log In">
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

// Configuration handling
$config_file = '/var/www/html/config.ini';

if (!file_exists($config_file)) {
    error_log("Config file not found: " . $config_file);
    die('Configuration file not found.');
}

// Load existing configuration
$config = parse_ini_file($config_file, true);
error_log("Loaded config: " . print_r($config, true));

// Set default values for toggles
$twilio_enabled = false;
if (isset($config['Twilio']['enabled'])) {
    $twilio_enabled = filter_var($config['Twilio']['enabled'], FILTER_VALIDATE_BOOLEAN);
    error_log("Read Twilio enabled from config: " . var_export($twilio_enabled, true));
}

$slack_enabled = false;
if (isset($config['Slack']['enabled'])) {
    $slack_enabled = filter_var($config['Slack']['enabled'], FILTER_VALIDATE_BOOLEAN);
    error_log("Read Slack enabled from config: " . var_export($slack_enabled, true));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    error_log("Form submitted. POST data: " . print_r($_POST, true));

    $gmail_username = trim($_POST['gmail_username']);
    $gmail_password = trim($_POST['gmail_password']);
    $twilio_enabled = isset($_POST['twilio_enabled']) ? 'true' : 'false';
    $twilio_account_sid = trim($_POST['account_sid']);
    $twilio_auth_token = trim($_POST['auth_token']);
    $twilio_from_number = trim($_POST['from_number']);
    $destination_number = trim($_POST['destination_number']);
    $slack_enabled = isset($_POST['slack_enabled']) ? 'true' : 'false';
    $slack_token = trim($_POST['slack_token']);
    $slack_channel = trim($_POST['slack_channel']);
    $max_sms_length = intval($_POST['max_sms_length']);

    error_log("Processing form - Twilio enabled: $twilio_enabled, Slack enabled: $slack_enabled");

    // Prepare new configuration
    $new_config = "[Gmail]\n";
    $new_config .= "username = $gmail_username\n";
    $new_config .= "password = $gmail_password\n\n";
    $new_config .= "[Twilio]\n";
    $new_config .= "enabled = $twilio_enabled\n";
    $new_config .= "account_sid = $twilio_account_sid\n";
    $new_config .= "auth_token = $twilio_auth_token\n";
    $new_config .= "from_number = $twilio_from_number\n";
    $new_config .= "destination_number = $destination_number\n\n";
    $new_config .= "[Slack]\n";
    $new_config .= "enabled = $slack_enabled\n";
    $new_config .= "token = $slack_token\n";
    $new_config .= "channel = $slack_channel\n\n";
    $new_config .= "[Settings]\n";
    $new_config .= "max_sms_length = $max_sms_length\n";

    error_log("New config to write: " . $new_config);

    if (file_put_contents($config_file, $new_config)) {
        $message = 'Configuration saved successfully.';
        $message_type = 'success';
        // Reload configuration after saving
        $config = parse_ini_file($config_file, true);
        // Update toggle states after save
        $twilio_enabled = filter_var($config['Twilio']['enabled'], FILTER_VALIDATE_BOOLEAN);
        $slack_enabled = filter_var($config['Slack']['enabled'], FILTER_VALIDATE_BOOLEAN);
        error_log("After save - Twilio enabled: " . var_export($twilio_enabled, true) . ", Slack enabled: " . var_export($slack_enabled, true));
    } else {
        $message = 'Failed to save configuration. Please check file permissions.';
        $message_type = 'error';
        error_log("Failed to save config file");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email-to-SMS Configuration</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Configuration form styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 30px;
        }
        .card {
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            font-size: 28px;
            text-align: center;
        }
        h2 {
            font-size: 22px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="number"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .description {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
        .actions {
            text-align: center;
        }
        input[type="submit"] {
            padding: 12px 20px;
            background-color: #007BFF;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            max-width: 300px;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .toggle-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input {
            display: none;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 24px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #007BFF;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .message.success {
            background-color: #D4EDDA;
            color: #155724;
        }
        .message.error {
            background-color: #F8D7DA;
            color: #721C24;
        }
        /* Hide fields initially */
        .twilio-field, .slack-field {
            display: none;
        }
        @media (max-width: 600px) {
            .form-group {
                flex-direction: column;
            }
            .toggle-container {
                flex-direction: column;
                align-items: flex-start;
            }
            .actions input[type="submit"] {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php error_log("Starting to render form - Twilio: " . var_export($twilio_enabled, true) . ", Slack: " . var_export($slack_enabled, true)); ?>
    <div class="container">
        <div class="card">
            <h1>Gmail2sms Configuration</h1>
            <?php if (isset($message)) : ?>
                <p class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            
            <form method="post">
                <!-- Gmail Section -->
                <div class="form-section">
                    <h2>Gmail Settings</h2>
                    <div class="form-group">
                        <label for="gmail_username">Gmail Username</label>
                        <input type="email" id="gmail_username" name="gmail_username" 
                               value="<?php echo htmlspecialchars($config['Gmail']['username']); ?>" required>
                        <p class="description">Your Gmail email address</p>
                    </div>

                    <div class="form-group">
                        <label for="gmail_password">Gmail App Password</label>
                        <input type="password" id="gmail_password" name="gmail_password" 
                               value="<?php echo htmlspecialchars($config['Gmail']['password']); ?>" required>
                        <p class="description">Your Gmail App Password (not your regular Gmail password)</p>
                    </div>
                </div>

                <!-- Twilio Section -->
                <div class="form-section">
                    <h2>Twilio Settings</h2>
                    <div class="form-group">
                        <div class="toggle-container">
                            <label for="twilio_enabled">Enable SMS Integration</label>
                            <label class="toggle-switch">
                                <?php error_log("Rendering Twilio toggle. Current value: " . var_export($twilio_enabled, true)); ?>
                                <input type="checkbox" id="twilio_enabled" name="twilio_enabled" 
                                       <?php if ($twilio_enabled) echo 'checked'; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p class="description">Enable or disable SMS notifications</p>
                    </div>

                    <div id="twilio-fields">
                        <div class="form-group twilio-field">
                            <label for="account_sid">Account SID</label>
                            <input type="text" id="account_sid" name="account_sid" 
                                   value="<?php echo htmlspecialchars($config['Twilio']['account_sid']); ?>">
                            <p class="description">Your Twilio Account SID</p>
                        </div>

                        <div class="form-group twilio-field">
                            <label for="auth_token">Auth Token</label>
                            <input type="password" id="auth_token" name="auth_token" 
                                   value="<?php echo htmlspecialchars($config['Twilio']['auth_token']); ?>">
                            <p class="description">Your Twilio Auth Token</p>
                        </div>

                        <div class="form-group twilio-field">
                            <label for="from_number">From Number</label>
                            <input type="text" id="from_number" name="from_number" 
                                   value="<?php echo htmlspecialchars($config['Twilio']['from_number']); ?>">
                            <p class="description">Your Twilio phone number (format: +1XXXXXXXXXX)</p>
                        </div>

                        <div class="form-group twilio-field">
                            <label for="destination_number">Destination Number</label>
                            <input type="text" id="destination_number" name="destination_number" 
                                   value="<?php echo htmlspecialchars($config['Twilio']['destination_number']); ?>">
                            <p class="description">The phone number to send SMS to (format: +1XXXXXXXXXX)</p>
                        </div>
                    </div>
                </div>
                <!-- Slack Section -->
                <div class="form-section">
                    <h2>Slack Settings</h2>
                    <div class="form-group">
                        <div class="toggle-container">
                            <label for="slack_enabled">Enable Slack Integration</label>
                            <label class="toggle-switch">
                                <?php error_log("Rendering Slack toggle. Current value: " . var_export($slack_enabled, true)); ?>
                                <input type="checkbox" id="slack_enabled" name="slack_enabled" 
                                       <?php if ($slack_enabled) echo 'checked'; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p class="description">Enable or disable Slack integration</p>
                    </div>

                    <div id="slack-fields">
                        <div class="form-group slack-field">
                            <label for="slack_token">Slack Bot Token</label>
                            <input type="password" id="slack_token" name="slack_token" 
                                   value="<?php echo htmlspecialchars($config['Slack']['token'] ?? ''); ?>">
                            <p class="description">Your Slack Bot User OAuth Token (starts with xoxb-)</p>
                        </div>

                        <div class="form-group slack-field">
                            <label for="slack_channel">Slack Channel</label>
                            <input type="text" id="slack_channel" name="slack_channel" 
                                   value="<?php echo htmlspecialchars($config['Slack']['channel'] ?? '#alerts'); ?>">
                            <p class="description">The Slack channel to send messages to (e.g., #alerts)</p>
                        </div>
                    </div>
                </div>

                <!-- Other Settings -->
                <div class="form-section">
                    <h2>Other Settings</h2>
                    <div class="form-group">
                        <label for="max_sms_length">Max SMS Length</label>
                        <input type="number" id="max_sms_length" name="max_sms_length" 
                               value="<?php echo htmlspecialchars($config['Settings']['max_sms_length']); ?>" required>
                        <p class="description">Maximum length of SMS messages before truncation</p>
                    </div>
                </div>

                <div class="actions">
                    <input type="submit" name="save" value="Save Configuration">
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const twilioToggle = document.getElementById('twilio_enabled');
        const slackToggle = document.getElementById('slack_enabled');
        const twilioFields = document.querySelectorAll('.twilio-field');
        const slackFields = document.querySelectorAll('.slack-field');

        console.log('Initial states - Twilio:', twilioToggle.checked, 'Slack:', slackToggle.checked);

        function updateTwilioFields() {
            const isEnabled = twilioToggle.checked;
            console.log('Updating Twilio fields, enabled:', isEnabled);
            twilioFields.forEach(field => {
                field.style.display = isEnabled ? 'block' : 'none';
                const input = field.querySelector('input');
                if (input) {
                    input.required = isEnabled;
                }
            });
        }

        function updateSlackFields() {
            const isEnabled = slackToggle.checked;
            console.log('Updating Slack fields, enabled:', isEnabled);
            slackFields.forEach(field => {
                field.style.display = isEnabled ? 'block' : 'none';
                const input = field.querySelector('input');
                if (input) {
                    input.required = isEnabled;
                }
            });
        }

        twilioToggle.addEventListener('change', function() {
            console.log('Twilio toggle changed:', this.checked);
            updateTwilioFields();
        });

        slackToggle.addEventListener('change', function() {
            console.log('Slack toggle changed:', this.checked);
            updateSlackFields();
        });

        // Initialize fields on page load
        updateTwilioFields();
        updateSlackFields();
    });
    </script>
</body>
</html>
