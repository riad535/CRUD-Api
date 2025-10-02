<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$db_name = "testing";

$conn = new mysqli($servername,$username,$password,$db_name);

if($conn->connect_error)
{
    die("Connection Error : ".$conn->connect_error);
}

?>