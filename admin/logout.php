<?php
require_once('admin-common.php');
$_SESSION = array();
session_destroy();
header('Location: login.php');
exit;
