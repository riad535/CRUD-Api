<?php
session_start();
include 'database.php';
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// create folders if not exist
if (!file_exists("uploads/images")) mkdir("uploads/images", 0777, true);
if (!file_exists("uploads/files")) mkdir("uploads/files", 0777, true);

// ---------- GET (read student or all students) ----------
if ($method == 'GET') {

    // DOWNLOAD FILE
    if (isset($_GET['type']) && $_GET['type'] == 'download' && isset($_GET['file'])) {
        $filePath = $_GET['file'];
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
            readfile($filePath);
            exit;
        } else {
            echo json_encode(["error" => "File not found"]);
            exit;
        }
    }

    // GET by ID
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM students WHERE id = $id";
        $result = $conn->query($sql);
        $data = $result->fetch_assoc();
        if ($data) {
            $data['images'] = json_decode($data['images'] ?? "[]", true);
            $data['files'] = json_decode($data['files'] ?? "[]", true);
        }
        echo json_encode($data);
    } 
    // GET all
    else {
        $sql = "SELECT * FROM students";
        $result = $conn->query($sql);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['images'] = json_decode($row['images'] ?? "[]", true);
            $row['files'] = json_decode($row['files'] ?? "[]", true);
            $users[] = $row;
        }
        echo json_encode($users);
    }
}
// ---------- REGISTER ----------
elseif ($method == 'POST' && isset($_GET['type']) && $_GET['type'] == 'register') {
    $username = $input['username'];
    $password = password_hash($input['password'], PASSWORD_BCRYPT);
    $name = $input['name'];
    $age = $input['age'];
    $city = $input['city'];

    $sql = "INSERT INTO students (name, age, city, username, password, images, files) 
            VALUES ('$name', '$age', '$city', '$username', '$password', '[]', '[]')";
    if ($conn->query($sql)) {
        echo json_encode(["message" => "Registration successful"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
    exit;
}

// ---------- LOGIN ----------
elseif ($method == 'POST' && isset($_GET['type']) && $_GET['type'] == 'login') {
    $username = $input['username'];
    $password = $input['password'];

    $sql = "SELECT * FROM students WHERE username='$username'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            echo json_encode(["message" => "Login successful", "id" => $user['id']]);
        } else {
            echo json_encode(["error" => "Invalid password"]);
        }
    } else {
        echo json_encode(["error" => "User not found"]);
    }
    exit;
}

// ---------- LOGOUT ----------
elseif ($method == 'POST' && isset($_GET['type']) && $_GET['type'] == 'logout') {
    session_destroy();
    echo json_encode(["message" => "Logged out successfully"]);
    exit;
}

// ---------- AUTH CHECK ----------
elseif (!isset($_SESSION['user_id']) && !in_array($_GET['type'] ?? '', ['login','register'])) {
    echo json_encode(["error" => "Unauthorized. Please log in first."]);
    exit;
}


// ---------- POST (upload images/files by ID) ----------
elseif ($method == 'POST' && isset($_GET['type'])) {
    $id = intval($_GET['id']);

    // Get existing record
    $res = $conn->query("SELECT * FROM students WHERE id = $id");
    $student = $res->fetch_assoc();
    if (!$student) {
        echo json_encode(["error" => "Student not found"]);
        exit;
    }

    $images = json_decode($student['images'] ?? "[]", true);
    $files = json_decode($student['files'] ?? "[]", true);

    if ($_GET['type'] == 'upload_image' && isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $filename = uniqid() . "_" . $_FILES['images']['name'][$key];
            $destination = "uploads/images/" . $filename;
            if (move_uploaded_file($tmp_name, $destination)) {
                $images[] = $destination;
            }
        }
    }

    if ($_GET['type'] == 'upload_file' && isset($_FILES['files'])) {
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            $filename = uniqid() . "_" . $_FILES['files']['name'][$key];
            $destination = "uploads/files/" . $filename;
            if (move_uploaded_file($tmp_name, $destination)) {
                $files[] = $destination;
            }
        }
    }

    $sql = "UPDATE students SET images = '" . json_encode($images) . "', files = '" . json_encode($files) . "' WHERE id = $id";
    if ($conn->query($sql)) {
        echo json_encode(["message" => "Upload successful"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

// ---------- PUT (update name, age, city, images, files) ----------
elseif ($method == 'PUT') {
    $id = intval($_GET['id']);

    $name = $input['name'];
    $age = $input['age'];
    $city = $input['city'];
    $username = $input['username'];
    $password= $input['password'];
    $images = json_encode($input['images'] ?? []);
    $files = json_encode($input['files'] ?? []);

    if(!empty($password))
        {
            $hashpwd = password_hash($password,PASSWORD_DEFAULT);
            $sql = "UPDATE students SET 
                    name = '$name',
                    age = '$age',
                    city = '$city',
                    images='$images',
                    files='$files',
                    username = '$username',
                    password = '$hashpwd' where id = '$id'";
        }
        else {
             $sql = "UPDATE students SET 
                    name = '$name',
                    age = '$age',
                    city = '$city',
                    images='$images',
                    files='$files',
                    username = '$username' where id = '$id'";
        }
    if ($conn->query($sql)) {
        echo json_encode(["message" => "Student updated successfully"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

// ---------- DELETE ----------
elseif ($method == 'DELETE') {
    $id = intval($_GET['id']);

    // If deleting specific image/file
    if (isset($_GET['type']) && isset($_GET['file'])) {
        $file = $_GET['file'];

        $res = $conn->query("SELECT * FROM students WHERE id = $id");
        $student = $res->fetch_assoc();

        if ($student) {
            $images = json_decode($student['images'] ?? "[]", true);
            $files = json_decode($student['files'] ?? "[]", true);

            // Normalize paths (important!)
            $images = is_array($images) ? $images : [];
            $files = is_array($files) ? $files : [];

            if ($_GET['type'] === 'image') {
                $images = array_values(array_filter($images, fn($i) => $i !== $file));
            } elseif ($_GET['type'] === 'file') {
                $files = array_values(array_filter($files, fn($f) => $f !== $file));
            }

            // --- Delete physical file if exists ---
            $realPath = __DIR__ . '/' . $file; // Make sure to use absolute path
            if (file_exists($realPath)) {
                unlink($realPath);
            }

            // --- Update DB ---
            $conn->query("UPDATE students 
                SET images = '" . $conn->real_escape_string(json_encode($images)) . "',
                    files = '" . $conn->real_escape_string(json_encode($files)) . "'
                WHERE id = $id");

            echo json_encode(["message" => "File deleted successfully"]);
            exit;
        } else {
            echo json_encode(["error" => "Student not found"]);
            exit;
        }
    }

    // If deleting entire student
    $sql = "DELETE FROM students WHERE id = $id";
    if ($conn->query($sql)) {
        echo json_encode(["message" => "Student deleted successfully"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

// ---------- DEFAULT ----------
else {
    echo json_encode(["error" => "Invalid request"]);
}

$conn->close();
?>
