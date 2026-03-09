<?php
declare(strict_types=1);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../helpers/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];
$region_id = trim($_POST['region_id'] ?? '');
$intensity = (int)($_POST['intensity'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

$allowed_regions = [
    'head', 'chest', 'abdomen', 'left_arm', 'right_arm',
    'left_leg', 'right_leg', 'back'
];

$errors = [];

if (!in_array($region_id, $allowed_regions, true)) {
    $errors[] = "Región corporal inválida.";
}

if ($intensity < 1 || $intensity > 10) {
    $errors[] = "La intensidad debe estar entre 1 y 10.";
}

if (mb_strlen($notes) > 255) {
    $errors[] = "Las notas no pueden superar 255 caracteres.";
}

if ($errors) {
    $_SESSION['form_errors'] = $errors;
    header("Location: ../../index.php");
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO symptom_sessions (patient_id, region_id, intensity, notes)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$patient_id, $region_id, $intensity, $notes]);

$_SESSION['form_success'] = "Registro guardado correctamente.";
header("Location: ../../index.php");
exit;