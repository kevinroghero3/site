<?php
// Error reporting (for debugging - REMOVE in production!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Installation Step 1: Display the form
if ($_SERVER['REQUEST_METHOD'] != 'POST' || isset($_POST['step1'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation: Database Setup</title>
        <link rel="stylesheet" href="style.css">
        <style>
            /* Override some styles for the install form */
            .login-form input[type="text"],
            .login-form input[type="password"] {
                width: 100%;
                padding: 8px;
                margin-bottom: 10px;
                background-color: #333;
                color: #eee;
                border: none;
                border-radius: 5px;
                box-sizing: border-box; /* Important!  */
                text-align: center;
            }

            .login-form label {
                display: block;
                margin-bottom: 5px;
                color: #eee;
                text-align: center; /* Align labels to the left  */
            }

            .login-form {
                width: 300px; /* Set a fixed width for the form  */
                height: auto;
                box-sizing: border-box; /* Important!  */
                text-align: center; 
            }

            .login-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: auto; /* Adjust height as needed  */
                min-height: 50vh;
            }

            .login-button {
                text-align: center; /* Center the button */
            }
        </style>
    </head>
    <body>
        <header>
            <h1>Installation: Database Setup</h1>
        </header>
        <main class="login-container">
            <form method="post" class="login-form">
                <input type="hidden" name="step2" value="1">
                <label for="db_host">Database Host:</label>
                <input type="text" name="db_host" id="db_host" value="127.0.0.1" required>
                <label for="db_user">Database User:</label>
                <input type="text" name="db_user" id="db_user" required>
                <label for="db_pass">Database Password:</label>
                <input type="password" name="db_pass" id="db_pass">
                <label for="db_name">Database Name:</label>
                <input type="text" name="db_name" id="db_name" required>
                <label for="admin_password">Admin Password:</label>
                <input type="password" name="admin_password" id="admin_password" required>
                <label for="confirm_admin_password">Confirm Admin Password:</label>
                <input type="password" name="confirm_admin_password" id="confirm_admin_password" required>
                <button type="submit" class="button">Submit and Setup Database</button>
            </form>
        </main>
    </body>
    </html>
    <?php
    exit;
}

// Installation Step 2: Process the form, create DB, tables, and set password
if (isset($_POST['step2']) && $_POST['step2'] == 1) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];
    $admin_password = $_POST['admin_password'];
    $confirm_admin_password = $_POST['confirm_admin_password'];

    $errors = [];

    // Validate inputs (basic validation - improve as needed)
    if ($admin_password !== $confirm_admin_password) {
        $errors[] = "Admin passwords do not match.";
    }

    if (empty($errors)) {
        // Connect to MySQL (without specifying the database initially)
        $conn = new mysqli($db_host, $db_user, $db_pass);

        // Check connection
        if ($conn->connect_error) {
            $errors[] = "Connection failed: " . $conn->connect_error;
        }

        if (empty($errors)) {
            // Try to create the database
            $sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name`";
            if ($conn->query($sql_create_db) === TRUE) {
                echo "Database created successfully or already exists<br>";
            } else {
                $errors[] = "Error creating database: " . $conn->error;
            }

            // Select the database
            if (empty($errors) && $conn->select_db($db_name)) {
                echo "Connected to database: $db_name<br>";

                // Try to create the users table
                $sql_create_users_table = "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    password VARCHAR(255) NOT NULL
                )";
                if ($conn->query($sql_create_users_table) === TRUE) {
                    echo "Table 'users' created successfully or already exists<br>";
                } else {
                    $errors[] = "Error creating table 'users': " . $conn->error;
                }

                // Try to create the items table
                $sql_create_items_table = "CREATE TABLE IF NOT EXISTS items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    ram VARCHAR(255) NOT NULL,
                    cpu VARCHAR(255) NOT NULL,
                    disk VARCHAR(255) NOT NULL,
                    sudo BOOLEAN NOT NULL,
                    `24/7` BOOLEAN NOT NULL,
                    location VARCHAR(255) NOT NULL,
                    status VARCHAR(255) NOT NULL,
                    status_text VARCHAR(255) NOT NULL,
                    url VARCHAR(255) NOT NULL
                )";
                if ($conn->query($sql_create_items_table) === TRUE) {
                    echo "Table 'items' created successfully or already exists<br>";
                } else {
                    $errors[] = "Error creating table 'items': " . $conn->error;
                }


                // Insert the admin password
                if (empty($errors)) {
                    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                    $sql_insert_password = "INSERT INTO users (password) VALUES ('$hashed_password')";
                    if ($conn->query($sql_insert_password) === TRUE) {
                        echo "Admin password set successfully!<br>";
                    } else {
                        $errors[] = "Error setting admin password: " . $conn->error;
                    }
                }
            } else {
                $errors[] = "Error selecting database: " . $conn->error;
            }

            $conn->close();
        }
    }

    // Display results or errors
    if (empty($errors)) {
        echo "<p>Installation complete!  You can now access your admin panel.</p>";

        // Modify admin.php with the new database credentials
        $admin_file = 'admin.php';
        $admin_content = file_get_contents($admin_file);
        $admin_content = preg_replace('/\$db_host = \"[^\"]+\";/', "\$db_host = \"$db_host\";", $admin_content);
        $admin_content = preg_replace('/\$db_user = \"[^\"]+\";/', "\$db_user = \"$db_user\";", $admin_content);
        $admin_content = preg_replace('/\$db_pass = \"[^\"]*\";/', "\$db_pass = \"$db_pass\";", $admin_content);
        $admin_content = preg_replace('/\$db_name = \"[^\"]+\";/', "\$db_name = \"$db_name\";", $admin_content);
        file_put_contents($admin_file, $admin_content);

        echo "<p>admin.php updated with new database credentials.</p>";

        // Attempt to delete this installation file
        if (unlink(__FILE__)) {
            echo "<p>Installation file (install.php) has been automatically deleted.</p>";
        } else {
            echo "<p>WARNING: Could not automatically delete install.php.  Please delete it manually for security reasons!</p>";
        }

        if (file_exists('transfer.php')) {
            $transfer_file = 'transfer.php';
            $transfer_content = file_get_contents($transfer_file);
            $transfer_content = preg_replace('/\$db_host = \"[^\"]+\";/', "\$db_host = \"$db_host\";", $transfer_content);
            $transfer_content = preg_replace('/\$db_user = \"[^\"]+\";/', "\$db_user = \"$db_user\";", $transfer_content);
            $transfer_content = preg_replace('/\$db_pass = \"[^\"]*\";/', "\$db_pass = \"$db_pass\";", $transfer_content);
            $transfer_content = preg_replace('/\$db_name = \"[^\"]+\";/', "\$db_name = \"$db_name\";", $transfer_content);
            file_put_contents($transfer_file, $transfer_content);

            echo "<p>transfer.php updated with new database credentials.</p>";

            echo "<p><a href='transfer.php'>Go to next step</a></p>";

            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Installation Complete</title>
                <style>
                /* Basic styling to center the message */
                .container { textAlign: center; }
                </style>
                <script>
                setTimeout(function() {
                window.location.href = 'transfer.php';
                }, 5000); // 5000 milliseconds = 5 seconds
                </script>
            </head>
            <body>
                <div class="container">
                <p>You will be redirected in 5 seconds...</p>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
        echo "<p><a href='admin.php'>Go to Admin Panel</a></p>";
    } else {
        echo "<h2>Installation Failed:</h2>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li class='error'>$error</li>";
        }
        echo "</ul>";
        echo "<p>Please correct the errors and try again.</p>";
    }
}
?>

<!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Installation Complete</title>
  <style>
  /* Basic styling to center the message */
  .container { textAlign: center; }
  </style>
  <script>
  setTimeout(function() {
  window.location.href = 'admin.php';
  }, 5000); // 5000 milliseconds = 5 seconds
  </script>
  </head>
  <body>
  <div class="container">
  <p>You will be redirected to the admin panel in 5 seconds...</p>
  </div>
  </body>
  </html>
  <?php
  exit; //  Important!