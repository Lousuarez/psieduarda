<?php
require_once __DIR__ . '/inc/auth.php';
jad_logout();
header('Location: index.php');
exit;
