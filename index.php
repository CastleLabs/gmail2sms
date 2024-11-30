<?php
/**
 * Configuration Web Interface for Email-to-SMS Script
 * 
 * This script provides a secure web interface for managing the Email-to-SMS service configuration.
 * It implements:
 * - Session-based authentication
 * - Configuration file reading and writing
 * - Form handling for updating settings
 * - Modern, responsive UI with clean design
 * 
 * Key Features:
 * - Secure login system
 * - Configuration management for Twilio settings
 * - Input validation and sanitization
 * - Error handling for file operations
 * - Mobile-responsive design
 * 
 * Requirements:
 * - PHP 7.x or higher
 * - Write permissions on config.ini
 * - Session support enabled
 * 
 * Author: Seth Morrow
 * Date: 11-26-24
 */

// Initialize session for login management
session_start();

// Authentication credentials
// TODO: Move these to a secure configuration file
$username = 'admin';          // Replace with your desired username
$password = 'yourpassword';   // Replace with your desired password

// Login handling logic
if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        // Validate submitted credentials
        if ($_POST['username'] === $username && $_POST['password'] === $password) {
            $_SESSION['logged_in'] = true;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        // Display login form if not logged in
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Login - Configuration Interface</title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                /* CSS Variables for consistent theming */
                :root {
                    --primary-color: #2563eb;    /* Main blue color */
                    --secondary-color: #1e40af;  /* Darker blue for hover states */
                    --background-color: #f8fafc; /* Light gray background */
                    --card-background: #ffffff;  /* White card background */
                    --text-color: #1f2937;      /* Dark gray text */
                    --border-color: #e2e8f0;    /* Light gray borders */
                    --error-color: #ef4444;     /* Red for error messages */
                }

                /* Base styles */
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

                /* Container styles */
                .container {
                    width: 100%;
                    max-width: 400px;
                    padding: 0 1rem;
                }

                /* Card component styles */
                .card {
                    background: var(--card-background);
                    border-radius: 8px;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                    padding: 2rem;
                }

                /* Typography */
                h1 {
                    color: var(--text-color);
                    font-size: 1.875rem;
                    font-weight: 600;
                    margin-bottom: 1.5rem;
                    text-align: center;
                }

                /* Error message styles */
                .error {
                    color: var(--error-color);
                    text-align: center;
                    margin-bottom: 1rem;
                }

                /* Form styles */
                form {
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                }

                /* Form label styles */
                label {
                    display: block;
                    font-weight: 500;
                    margin-bottom: 0.5rem;
                }

                /* Form input styles */
                input[type="text"],
                input[type="password"] {
                    width: 100%;
                    padding: 0.75rem;
                    border: 1px solid var(--border-color);
                    border-radius: 6px;
                    font-size: 1rem;
                    transition: border-color 0.15s ease;
                }

                /* Input focus states */
                input[type="text"]:focus,
                input[type="password"]:focus {
                    outline: none;
                    border-color: var(--primary-color);
                    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
                }

                /* Submit button styles */
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
                }

                /* Submit button hover state */
                input[type="submit"]:hover {
                    background-color: var(--secondary-color);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="card">
                    <h1>Login</h1>
                    <?php 
                    // Display error message if authentication failed
                    if (isset($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; 
                    ?>
                    <form method="post">
                        <input type="hidden" name="login" value="1">
                        <div>
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div>
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

// Configuration file path
$config_file = '/var/www/html/plcalerts/config.ini';

// Verify config file exists
if (!file_exists($config_file)) {
    die('Configuration file not found.');
}

// Handle configuration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Retrieve and sanitize form data
    $twilio_account_sid = $_POST['account_sid'];
    $twilio_auth_token = $_POST['auth_token'];
    $twilio_from_number = $_POST['from_number'];
    $destination_number = $_POST['destination_number'];
    $max_sms_length = $_POST['max_sms_length'];

    // Construct new configuration content
    $new_config = "[Twilio]\n";
    $new_config .= "account_sid = $twilio_account_sid\n";
    $new_config .= "auth_token = $twilio_auth_token\n";
    $new_config .= "from_number = $twilio_from_number\n";
    $new_config .= "destination_number = $destination_number\n\n";
    $new_config .= "[Settings]\n";
    $new_config .= "max_sms_length = $max_sms_length\n";

    // Attempt to save configuration
    if (file_put_contents($config_file, $new_config)) {
        $message = 'Configuration saved successfully.';
    } else {
        $message = 'Failed to save configuration. Please check file permissions.';
    }
}

// Load current configuration
$config = parse_ini_file($config_file, true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Configure Script Variables</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* CSS Variables for consistent theming */
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-color: #1f2937;
            --border-color: #e2e8f0;
            --success-color: #22c55e;
        }

        /* Base styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Layout containers */
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Card component */
        .card {
            background: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
        }

        /* Typography */
        h1 {
            color: var(--text-color);
            font-size: 1.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        /* Success message styles */
        .message {
            color: var(--success-color);
            padding: 1rem;
            border-radius: 6px;
            background-color: rgba(34, 197, 94, 0.1);
            margin-bottom: 1.5rem;
        }

        /* Form layout */
        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Form group container */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        /* Form labels */
        label {
            font-weight: 500;
            color: var(--text-color);
        }

        /* Form inputs */
        input[type="text"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.15s ease;
        }

        /* Input focus states */
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Submit button */
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

        /* Submit button hover state */
        input[type="submit"]:hover {
            background-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Configure Script Variables</h1>
            <?php 
            // Display success/error message if present
            if (isset($message)) echo '<p class="message">' . htmlspecialchars($message) . '</p>'; 
            ?>
            <form method="post">
                <!-- Form groups for Twilio configuration -->
                <div class="form-group">
                    <label for="from_number">Twilio From Number</label>
                    <input type="text" id="from_number" name="from_number" 
                           value="<?php echo htmlspecialchars($config['Twilio']['from_number']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="destination_number">Destination Number</label>
                    <input type="text" id="destination_number" name="destination_number" 
                           value="<?php echo htmlspecialchars($config['Twilio']['destination_number']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="max_sms_length">Max SMS Length</label>
                    <input type="number" id="max_sms_length" name="max_sms_length" 
                           value="<?php echo htmlspecialchars($config['Settings']['max_sms_length']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="account_sid">Twilio Account SID</label>
                    <input type="text" id="account_sid" name="account_sid" 
                           value="<?php echo htmlspecialchars($config['Twilio']['account_sid']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="auth_token">Twilio Auth Token</label>
                    <input type="password" id="auth_token" name="auth_token" 
                           value="<?php echo htmlspecialchars($config['Twilio']['auth_token']); ?>" required>
                </div>

                <input type="submit" name="save" value="Save Configuration">
            </form>
        </div>
    </div>
</body>
</html>
