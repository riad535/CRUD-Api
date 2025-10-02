<?php
include 'database.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'),true);

switch ($method){
    case 'GET':
        if(isset($_GET['id']))
        {
            $id = $_GET['id'];
            $sql = "SELECT * FROM students WHERE id = $id";
            $result = $conn->query($sql);
            $data = $result->fetch_assoc();
            echo json_encode($data);
        }
        else{
            $sql = "SELECT * FROM students";
            $result = $conn->query($sql);
            $user=[];
            while($row = $result->fetch_assoc())
            {
                $user[] = $row;
            }
            echo json_encode($user);
        }
        break;
    
    case 'POST':
        $name = $input['name'];
        $age = $input['age'];
        $city = $input['city'];

        $sql = "INSERT INTO students (name,age,city) VALUES ('$name','$age','$city')";
        if($conn->query($sql))
        {
            echo json_encode(["message" => "Successfully add new user"]);
        }
        else
        {
            die("Error in upload new user");
        }
        break;

    case 'PUT':
        $id = $_GET['id'];
        $name = $input['name'];
        $age = $input['age'];
        $city = $input['city'];


        $sql = "UPDATE students SET name = '$name',age ='$age',city = '$city' WHERE id = $id";

        if($conn->query($sql))
        {
            echo json_encode(["message" => "Successfully update user"]);
        }
        else
        {
            die("Error in update user");
        }
        break;

    case 'DELETE':
        $id = $_GET['id'];
        $sql = "DELETE FROM students where id = $id";

        if($conn->query($sql))
        {
            echo json_encode(["message" => "Successfully dalete new user"]);
        }
        else
        {
            die("Error in delete user");
        }
        break;
    
    default:
        echo json_encode(["Message" => "invalid request Method"]);
        break; 

}
$conn->close();
?>