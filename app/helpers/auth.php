<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void {
    if (empty($_SESSION['patient_id'])) {
        header("Location: login.php");
        exit;
    }
}

function redirect_if_logged_in(): void {
    if (!empty($_SESSION['patient_id'])) {
        header("Location: index.php");
        exit;
    }
}