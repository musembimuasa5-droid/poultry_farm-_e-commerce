<?php require_once 'config.php'; unset($_SESSION['user']); session_regenerate_id(true); flash('success','You have been signed out.'); redirect('index.php');
