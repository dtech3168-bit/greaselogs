<?php
require_once __DIR__ . '/../includes/config.php';
unset($_SESSION['developer_id'], $_SESSION['developer_username']);
session_regenerate_id(true);
header('Location: login.php');
exit();
