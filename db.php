<?php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";

$db_name = "threaderz_store";
$con = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$con) {
    $db_name = "store";
    $con = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
}

if ($con) {
    mysqli_set_charset($con, "utf8mb4");
}
?>