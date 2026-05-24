<?php
// Database credentials
$db_host = "null"; //  !!! CAMBIA QUESTO !!!
$db_user = "null"; //  !!! CAMBIA QUESTO !!!
$db_pass = "null"; //  !!! CAMBIA QUESTO !!!
$db_name = "null"; //  !!! CAMBIA QUESTO !!!

// Check configuration
if ($db_host == 'null' || $db_user == 'null' || $db_name == 'null') {
    if (file_exists('install.php')) {
        header("Location: install.php");
    } else {
        echo "<p>ERROR: install.php not found, put it in the some folder of admin.php.</p>";
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error</title>
        <style>
        /* Basic styling to center the message */
        .container { textAlign: center; }
        </style>
        <script>
        setTimeout(function() {
        window.location.href = '/';
        }, 5000); // 5000 milliseconds = 5 seconds
        </script>
    </head>
    <body>
        <div class="container">
        <p>You will be redirected to the homepage in 5 seconds...</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Connect to MySQL
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$data_file = 'data.json';
$data = json_decode(file_get_contents($data_file), true);

if (isset($data['items']) && is_array($data['items'])) {
    foreach ($data['items'] as $item) {
        $title = $conn->real_escape_string($item['title']);
        $ram = $conn->real_escape_string($item['ram']);
        $cpu = $conn->real_escape_string($item['cpu']);
        $disk = $conn->real_escape_string($item['disk']);
        $sudo = $item['sudo'] ? 1 : 0;  // Convert boolean to 0 or 1
        $twenty47 = $item['24/7'] ? 1 : 0; // Convert boolean
        $location = $conn->real_escape_string($item['location']);
        $status = $conn->real_escape_string($item['status']);
        $status_text = $conn->real_escape_string($item['status_text']);
        $url = $conn->real_escape_string($item['url']);

        $sql = "INSERT INTO items (title, ram, cpu, disk, sudo, `24/7`, location, status, status_text, url)
                VALUES ('$title', '$ram', '$cpu', '$disk', $sudo, $twenty47, '$location', '$status', '$status_text', '$url')";

        if ($conn->query($sql) === TRUE) {
            echo "Record inserted successfully<br>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error . "<br>";
        }
    }
} else {
    echo "No items found in data.json or data.json is not formatted correctly.<br>";
}

$conn->close();

if (file_exists('data.json')) {
    if (unlink('data.json')) {
        echo "<p>Data file (data.json) has been automatically deleted.</p>";
    } else {
        echo "<p>WARNING: Could not automatically delete data.json.  Please delete it manually for security reasons!</p>";
    }
}

if (unlink(__FILE__)) {
    echo "<p>Installation file (transfer.php) has been automatically deleted.</p>";
} else {
    echo "<p>WARNING: Could not automatically delete transfer.php.  Please delete it manually for security reasons!</p>";
}

echo "<a href='admin.php'>Back to Admin Panel</a>"; // Add a link to go back
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Complete</title>
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
exit;