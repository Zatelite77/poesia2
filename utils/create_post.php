<?php
session_start();
include '../functions/functions.php';
$conn = conn();
$user_id=$_SESSION['user_id'];
$title = $_POST['name'];
$content = $_POST['content'];
if($_POST['folders'] != 'null'){
    $folderid = $_POST['folders'];
}else{
    $folderid = 0;
}
$consult = mysqli_query($conn, "INSERT INTO posts (id_owner, id_folder, title, content) VALUES ('$user_id', '$folderid', '$title', '$content') ");
if($consult){
    header("Location: http://localhost:8888/poesia/?loc=dash");
}

