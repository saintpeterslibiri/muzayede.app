<?php
session_start();
require_once 'includes/api.php';

logout();

header('Location: index.php');
exit;

