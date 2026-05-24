<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

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

// Autenticazione
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = $_POST['password'];

        $sql = "SELECT password FROM users LIMIT 1";  //  Assumes only one user
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $hashed_password = $row['password'];

            if (password_verify($password, $hashed_password)) {
                $_SESSION['logged_in'] = true;
                header("Location: admin.php");
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No user found. Run install.php first.";
        }
    }

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header>
            <h1 onclick="window.location.href='/'">Admin Login</h1>
        </header>
        <main class="login-container">
            <form method="post" class="login-form">
                <div class="login-input">
                    <label for="password">Password:</label> 
                    <input type="password" name="password" style="margin-left: 10px;">
                </div>
                <div class="login-error">
                    <?php if (isset($error)) echo "<p class='error-message'>$error</p>"; ?>
                </div>
                <div class="login-button">
                    <button type="submit" class="button">Login</button>
                </div>
            </form>
        </main>
        <footer>
            <p>&copy; 2025 by kevHeroX</p>
        </footer>
    </body>
    </html>
    <?php
    exit;
}

// Gestione degli Items
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $title = $_POST['title'];
        $ram = $_POST['ram'];
        $cpu = $_POST['cpu'];
        $disk = $_POST['disk'];
        $sudo = isset($_POST['sudo']) ? 1 : 0;
        $twenty47 = isset($_POST['24/7']) ? 1 : 0;
        $location = $_POST['location'];
        $status = $_POST['status'];
        $status_text = $_POST['status_text'];
        $url = $_POST['url'];

        $sql = "INSERT INTO items (title, ram, cpu, disk, sudo, `24/7`, location, status, status_text, url) 
                VALUES ('$title', '$ram', '$cpu', '$disk', $sudo, $twenty47, '$location', '$status', '$status_text', '$url')";

        if ($conn->query($sql) === TRUE) {
            //echo "New record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif ($_POST['action'] == 'delete' && isset($_POST['index'])) {
        $id = $_POST['index'];  // Use 'id' from the database
        $sql = "DELETE FROM items WHERE id = $id";
        if ($conn->query($sql) === TRUE) {
            //echo "Record deleted successfully";
        } else {
            echo "Error deleting record: " . $conn->error;
        }

    } elseif ($_POST['action'] == 'edit' && isset($_POST['edit_index'])) {
        $id = $_POST['edit_index']; // Use 'id' from the database
        $title = $_POST['title'];
        $ram = $_POST['ram'];
        $cpu = $_POST['cpu'];
        $disk = $_POST['disk'];
        $sudo = isset($_POST['sudo']) ? 1 : 0;
        $twenty47 = isset($_POST['24/7']) ? 1 : 0;
        $location = $_POST['location'];
        $status = $_POST['status'];
        $status_text = $_POST['status_text'];
        $url = $_POST['url'];

        $sql = "UPDATE items SET 
                title = '$title', ram = '$ram', cpu = '$cpu', disk = '$disk', 
                sudo = $sudo, `24/7` = $twenty47, location = '$location', 
                status = '$status', status_text = '$status_text', url = '$url' 
                WHERE id = $id";

        if ($conn->query($sql) === TRUE) {
            //echo "Record updated successfully";
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }

    header("Location: admin.php");
    exit;
}

// Mostra gli Items e Rigenera index.html
$sql = "SELECT * FROM items ORDER BY `order` ASC";
$result = $conn->query($sql);

$data['items'] = []; // Initialize as an empty array
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data['items'][] = $row;
    }
}


// Costruisci il nuovo contenuto del main (same as before, but fetching from $data)
// Costruisci il nuovo contenuto del main
$main_content = "<main class='container'>\n";
foreach ($data['items'] as $item) {
    $main_content .= "    <div class='item'>\n";
    $main_content .= "        <h2>" . htmlspecialchars($item['title']) . "</h2>\n";
    $main_content .= "        <p>Ram: " . htmlspecialchars($item['ram']) . " | CPU: " . htmlspecialchars($item['cpu']) . " | Disk: " . htmlspecialchars($item['disk']) . "</p>\n";
    $main_content .= "        <p>\n";
    $main_content .= $item['sudo'] ? '            <span class="label-green"> Sudo </span> | ' : '            <span class="label-red"> Sudo </span> | ';
    $main_content .= $item['24/7'] ? '<span class="label-green"> 24/7 </span>' : '<span class="label-red"> 24/7 </span>';
    $main_content .= "\n        </p>\n";
    $main_content .= "        <p>" . htmlspecialchars($item['location']) . "</p>\n";
    $main_content .= "        <div class='status'>\n";
    if ($item['status'] == 'Online') {
        $main_content .= '            <span class="online"></span> ' . htmlspecialchars($item['status_text']) . "\n";
    } elseif ($item['status'] == 'Offline') {
        $main_content .= '            <span class="offline"></span> ' . htmlspecialchars($item['status_text']) . "\n";
    } elseif ($item['status'] == 'Warning') {
        $main_content .= '            <span class="warning"></span> ' . htmlspecialchars($item['status_text']) . "\n";
    }
    $main_content .= "        </div>\n";
    $main_content .= '        <a href="' . htmlspecialchars($item['url']) . '" class="button" target="_blank">Visit</a>' . "\n";
    $main_content .= "    </div>\n\n";
}
$main_content .= "</main>";

// Carica l'HTML originale in DOMDocument (same as before)
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML(file_get_contents("index.html"));
libxml_use_internal_errors(false);

// Trova l'elemento main (same as before)
$main = $dom->getElementsByTagName('main')->item(0);

// Crea un nuovo frammento di documento per il nuovo contenuto del main (same as before)
$new_main_content_node = $dom->createDocumentFragment();
$new_main_content_node->appendXML($main_content);

// Sostituisci il vecchio contenuto del main con il nuovo (same as before)
$main->parentNode->replaceChild($new_main_content_node, $main);

// Salva l'HTML modificato (same as before)
$new_html = $dom->saveHTML();
file_put_contents("index.html", $new_html);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Admin Panel</h1>
        <button type="button" class="button add-button">Add New Item</button>
        <a href="/" class="button logout-button" target="_blank">Homepage</a>
        <a href="logout.php" class="button logout-button">Logout</a>
    </header>
    <div class="admin-container">
        <div class="admin-main">
        
        <?php if (count($data['items']) > 0): ?>
            <?php foreach ($data['items'] as $index => $item): ?>
            <div class="admin-item">
                <h2><?php echo htmlspecialchars($item['title']); ?></h2>
                <p>RAM: <?php echo htmlspecialchars($item['ram']); ?> | CPU: <?php echo htmlspecialchars($item['cpu']); ?> |
                    Disk: <?php echo htmlspecialchars($item['disk']); ?></p>
                <p>Sudo: <?php echo $item['sudo'] ?
'<span class="label-green">Yes</span>' : '<span
                        class="label-red">No</span>';
?> | 24/7: <?php echo $item['24/7'] ? '<span
                        class="label-green">Yes</span>' : '<span class="label-red">No</span>';
?></p>
                <p>Location: <?php echo htmlspecialchars($item['location']); ?></p>
                <p>Status: <?php echo htmlspecialchars($item['status']); ?></p>
                <p>Text: <?php echo htmlspecialchars($item['status_text']); ?></p>
                <p><?php echo htmlspecialchars($item['url']); ?></p>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="index" value="<?php echo $item['id']; ?>">
                    <button type="button" class="button edit-button" data-index="<?php echo $index; ?>">Edit</button>
      
                    <button type="submit" class="button">Delete</button>
                </form>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p>No items found.</p>
            <?php endif; ?>
        </div>

        <div class="add-form-container" style="display:none;">
            <form method="post" class="add-form">
                <input type="hidden" name="action" value="add">
                <h2>Add New Item</h2>
                Title: <input type="text" name="title" required><br>
           
                RAM: <input type="text" name="ram" required><br>
                CPU: <input type="text" name="cpu" required><br>
                Disk: <input type="text" name="disk" required><br>
                <div class="form-group">
                    <label for="sudo">Sudo:</label>
                    <input type="checkbox" name="sudo" id="sudo">
                </div>
                <div class="form-group">
                    <label for="24/7">24/7:</label>
                    <input type="checkbox" name="24/7" id="24/7">
                </div><br>
                Location: <input type="text" name="location" required><br>
                Status: <select name="status">
                    <option value="Online">Work</option>
                    <option value="Offline">Offline</option>
                    <option value="Warning">Warning</option>
                </select>
                <input type="text" name="status_text" required><br>
                URL: <input type="url" name="url" required><br>
                <button type="submit" class="button">Add Item</button>
                <button type="button" class="button cancel-button">Cancel</button>
            </form>
        </div>

        <div class="edit-form-container" style="display:none;">
            <form method="post" class="edit-form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_index" value="">
                <h2>Edit Item</h2>
                Title: <input type="text" name="title" required><br>
                RAM: <input type="text" name="ram" required><br>
                CPU: <input type="text" name="cpu" required><br>
                Disk: <input type="text" name="disk" required><br>
                <div class="form-group">
                    <label for="sudo">Sudo:</label>
                    <input type="checkbox" name="sudo" id="sudo">
                </div>
                <div class="form-group">
                    <label for="24/7">24/7:</label>
                    <input type="checkbox" name="24/7" id="24/7">
                </div><br>
                Location: <input type="text" name="location" required><br>
                Status: <select name="status">
                    <option value="Online">Work</option>
                    <option value="Offline">Offline</option>
                    <option value="Warning">Warning</option>
                </select>
                <input type="text" name="status_text" required><br>
                URL: <input type="url" name="url" required><br>
                <button type="submit" class="button">Save Changes</button>
                <button type="button" class="button cancel-edit-button">Cancel</button>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const addButton = document.querySelector('.add-button');
        const addFormContainer = document.querySelector('.add-form-container');
        const cancelAddButton = document.querySelector('.cancel-button');
        const editFormContainer = document.querySelector('.edit-form-container');
        const cancelEditButton = document.querySelector('.cancel-edit-button');
        const editButtons = document.querySelectorAll('.edit-button');
        const editIndexInput = document.querySelector('input[name="edit_index"]');

        addButton.addEventListener('click', function() {
            addFormContainer.style.display = 'flex';
        });

        cancelAddButton.addEventListener('click', function() {
            addFormContainer.style.display = 'none';
        });

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const itemDiv = this.closest('.admin-item');
                const id = itemDiv.querySelector('input[name="index"]').value;
                editIndexInput.value = id;
                editFormContainer.style.display = 'flex';

                // Popola il form di modifica con i dati dell'item
                const title = itemDiv.querySelector('h2').textContent;
                const ram = itemDiv.querySelector('p:nth-of-type(1)').textContent.split('|')[0].trim().split(':')[1].trim();
                const cpu = itemDiv.querySelector('p:nth-of-type(1)').textContent.split('|')[1].trim().split(':')[1].trim();
                const disk = itemDiv.querySelector('p:nth-of-type(1)').textContent.split('|')[2].trim().split(':')[1].trim();
                const sudo = itemDiv.querySelector('p:nth-of-type(2)').textContent.includes('Yes');
                const _247 = itemDiv.querySelector('p:nth-of-type(2)').textContent.split('|')[1].includes('Yes');
                const location = itemDiv.querySelector('p:nth-of-type(3)').textContent.split(':')[1].trim();
                const status = itemDiv.querySelector('p:nth-of-type(4)').textContent.split(':')[1].trim();
                const statusText = itemDiv.querySelector('p:nth-of-type(5)').textContent.split(':')[1].trim();
                const url = itemDiv.querySelector('p:nth-of-type(6)').textContent;

                document.querySelector('.edit-form input[name="title"]').value = title;
                document.querySelector('.edit-form input[name="ram"]').value = ram;
                document.querySelector('.edit-form input[name="cpu"]').value = cpu;
                document.querySelector('.edit-form input[name="disk"]').value = disk;
                document.querySelector('.edit-form input[name="sudo"]').checked = sudo;
                document.querySelector('.edit-form input[name="24/7"]').checked = _247;
                document.querySelector('.edit-form input[name="location"]').value = location;
                document.querySelector('.edit-form select[name="status"]').value = status;
                document.querySelector('.edit-form input[name="status_text"]').value = statusText;
                document.querySelector('.edit-form input[name="url"]').value = url;
            });
        });

        cancelEditButton.addEventListener('click', function() {
            editFormContainer.style.display = 'none';
        });
    });
    </script>
    <footer>
        <p>&copy; 2025 by kevHeroX</p>
    </footer>
</body>
</html>
