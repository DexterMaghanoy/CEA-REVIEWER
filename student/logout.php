<?php 
session_start();

$_SESSION = array();

session_destroy();

$_SESSION['lock'] = false;

header("Location: index.php");
exit;
?>