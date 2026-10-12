<?php
require_once __DIR__.'/sistema/auth.php';
if(!current_user()){ header('Location: docentes-login.php'); exit; }
header('Location: sistema/dashboard.php'); exit;
