<?php
//โฮส
$host = "localhost";
//Username phpmyadmin
$username = "root";
//Password phpMyadmin
$password = "Ple01010!@#";
$db = "green_gps";

//คำสั่ง Connect ฐานข้อมูล
$conn = new mysqli($host, $username, $password, $db);
mysqli_query($conn, "SET NAMES UTF8");
// check connection
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
 ?>
