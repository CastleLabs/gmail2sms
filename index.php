<?php
session_start();

$username = 'admin';
$password = 'yourpassword';

if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        if ($_POST['username'] === $username && $_POST['password'] === $password) {
            $_SESSION['logged_in'] = true;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Gmail2sms Configuration</title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                :root {
                    --primary-color: #2563eb;
                    --secondary-color: #1e40af;
                    --background-color: #f8fafc;
                    --card-background: #ffffff;
                    --text-color: #1f2937;
                    --border-color: #e2e8f0;
                    --error-color: #ef4444;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                    margin: 0;
                    padding: 0;
                    background-color: var(--background-color);
                    color: var(--text-color);
                    line-height: 1.6;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .container {
                    width: 100%;
                    max-width: 400px;
                    padding: 0 1rem;
                }
                .card {
                    background: var(--card-background);
                    border-radius: 8px;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                    padding: 2rem;
                }
                h1 {
                    color: var(--text-color);
                    font-size: 1.875rem;
                    font-weight: 600;
                    margin-bottom: 1.5rem;
                    text-align: center;
                }
                .error {
                    color: var(--error-color);
                    text-align: center;
                    margin-bottom: 1rem;
                    padding: 0.75rem;
                    background-color: rgba(239, 68, 68, 0.1);
                    border-radius: 6px;
                }
                form {
                    display: flex;
                    flex-direction: column;
                    gap: 1.5rem;
                }
                .form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 0.5rem;
                }
                label {
                    font-weight: 500;
                    color: var(--text-color);
                }
                input[type="text"],
                input[type="password"] {
                    width: 100%;
                    padding: 0.75rem;
                    border: 1px solid var(--border-color);
                    border-radius: 6px;
                    font-size: 1rem;
                    transition: border-color 0.15s ease;
                }
                input[type="text"]:focus,
                input[type="password"]:focus {
                    outline: none;
                    border-color: var(--primary-color);
                    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
                }
                input[type="submit"] {
                    background-color: var(--primary-color);
                    color: white;
                    padding: 0.75rem 1.5rem;
                    border: none;
                    border-radius: 6px;
                    font-size: 1rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background-color 0.15s ease;
                    width: 100%;
                }
                input[type="submit"]:hover {
                    background-color: var(--secondary-color);
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

$config_file = '/var/www/html/plcalerts/config.ini';

if (!file_exists($config_file)) {
    die('Configuration file not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $gmail_username = trim($_POST['gmail_username']);
    $gmail_password = trim($_POST['gmail_password']);
    $twilio_account_sid = trim($_POST['account_sid']);
    $twilio_auth_token = trim($_POST['auth_token']);
    $twilio_from_number = trim($_POST['from_number']);
    $destination_number = trim($_POST['destination_number']);
    $max_sms_length = intval($_POST['max_sms_length']);

    $new_config = "[Gmail]\n";
    $new_config .= "username = $gmail_username\n";
    $new_config .= "password = $gmail_password\n\n";
    $new_config .= "[Twilio]\n";
    $new_config .= "account_sid = $twilio_account_sid\n";
    $new_config .= "auth_token = $twilio_auth_token\n";
    $new_config .= "from_number = $twilio_from_number\n";
    $new_config .= "destination_number = $destination_number\n\n";
    $new_config .= "[Settings]\n";
    $new_config .= "max_sms_length = $max_sms_length\n";

    if (file_put_contents($config_file, $new_config)) {
        $message = 'Configuration saved successfully.';
        $message_type = 'success';
    } else {
        $message = 'Failed to save configuration. Please check file permissions.';
        $message_type = 'error';
    }
}

$config = parse_ini_file($config_file, true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email-to-SMS Configuration</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-color: #1f2937;
            --border-color: #e2e8f0;
            --success-color: #22c55e;
            --error-color: #ef4444;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            background: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
        }
        h1 {
            color: var(--text-color);
            font-size: 1.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        .message.success {
            color: var(--success-color);
            background-color: rgba(34, 197, 94, 0.1);
        }
        .message.error {
            color: var(--error-color);
            background-color: rgba(239, 68, 68, 0.1);
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .form-section {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        label {
            font-weight: 500;
            color: var(--text-color);
        }
        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.15s ease;
        }
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.15s ease;
            align-self: flex-start;
        }
        input[type="submit"]:hover {
            background-color: var(--secondary-color);
        }
        .description {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
        }
        .logout {
            color: var(--text-color);
            text-decoration: none;
            font-size: 0.875rem;
        }
        .logout:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Gmail2sms Configuration</h1>
            <?php if (isset($message)) : ?>
                <p class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            
            <form method="post">
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

                <div class="form-section">
                    <h2>Twilio Settings</h2>
                    <div class="form-group">
                        <label for="destination_number">Destination Number</label>
                        <input type="text" id="destination_number" name="destination_number" 
                               value="<?php echo htmlspecialchars($config['Twilio']['destination_number']); ?>" required>
                        <p class="description">The phone number to send SMS to (format: +1XXXXXXXXXX)</p>
                    </div>
                    <div class="form-group">
                        <label for="from_number">From Number</label>
                        <input type="text" id="from_number" name="from_number" 
                               value="<?php echo htmlspecialchars($config['Twilio']['from_number']); ?>" required>
                        <p class="description">Your Twilio phone number (format: +1XXXXXXXXXX)</p>
                    </div>

                    
                    <div class="form-group">
                        <label for="account_sid">Account SID</label>
                        <input type="text" id="account_sid" name="account_sid" 
                               value="<?php echo htmlspecialchars($config['Twilio']['account_sid']); ?>" required>
                        <p class="description">Your Twilio Account SID</p>
                    </div>
                    <div class="form-group">
                        <label for="auth_token">Auth Token</label>
                        <input type="password" id="auth_token" name="auth_token" 
                               value="<?php echo htmlspecialchars($config['Twilio']['auth_token']); ?>" required>
                        <p class="description">Your Twilio Auth Token</p>
                    </div>

                   
                </div>

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
                    <a href="?logout=1" class="logout">Logout</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
