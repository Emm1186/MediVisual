<?php
declare(strict_types=1);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../helpers/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.php");
    exit;
}

$patient_id = (int)$_SESSION['patient_id'];

$body_view = trim($_POST['body_view'] ?? '');
$body_side = trim($_POST['body_side'] ?? '');
$region_id = trim($_POST['region_id'] ?? '');
$intensity = (int)($_POST['intensity'] ?? 0);
$notes = trim($_POST['notes'] ?? '');
$symptom_details = $_POST['symptom_details'] ?? '';

$allowed_views = ['front', 'back'];
$allowed_sides = ['left', 'right', 'center'];

$allowed_regions = [
    'head_front',
    'shoulder_left_front',
    'shoulder_right_front',
    'chest_left',
    'chest_right',
    'abdomen_upper',
    'abdomen_lower',
    'arm_left_front',
    'arm_right_front',
    'forearm_left_front',
    'forearm_right_front',
    'hand_left_front',
    'hand_right_front',
    'thigh_left_front',
    'thigh_right_front',
    'knee_left_front',
    'knee_right_front',
    'leg_left_front',
    'leg_right_front',
    'foot_left_front',
    'foot_right_front',
    'head_back',
    'neck_back',
    'shoulder_left_back',
    'shoulder_right_back',
    'upper_back',
    'mid_back',
    'lower_back',
    'arm_left_back',
    'arm_right_back',
    'forearm_left_back',
    'forearm_right_back',
    'hand_left_back',
    'hand_right_back',
    'glute_left',
    'glute_right',
    'thigh_left_back',
    'thigh_right_back',
    'knee_left_back',
    'knee_right_back',
    'leg_left_back',
    'leg_right_back',
    'foot_left_back',
    'foot_right_back'
];

$errors = [];

if (!in_array($body_view, $allowed_views, true)) {
    $errors[] = "Vista corporal inválida.";
}

if (!in_array($body_side, $allowed_sides, true)) {
    $errors[] = "Lado corporal inválido.";
}

if (!in_array($region_id, $allowed_regions, true)) {
    $errors[] = "Región corporal inválida.";
}

if ($intensity < 1 || $intensity > 10) {
    $errors[] = "La intensidad debe estar entre 1 y 10.";
}

if (mb_strlen($notes) > 255) {
    $errors[] = "Las notas no pueden superar 255 caracteres.";
}

$decoded_details = [];
if ($symptom_details !== '') {
    $decoded_details = json_decode($symptom_details, true);
    if (!is_array($decoded_details)) {
        $errors[] = "Los detalles del síntoma no son válidos.";
    }
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    header("Location: ../../index.php");
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO symptom_sessions (
        patient_id,
        body_view,
        body_side,
        region_id,
        intensity,
        notes,
        symptom_details
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $patient_id,
    $body_view,
    $body_side,
    $region_id,
    $intensity,
    $notes,
    $symptom_details !== '' ? json_encode($decoded_details, JSON_UNESCAPED_UNICODE) : null
]);

$_SESSION['form_success'] = "Síntoma registrado correctamente.";
header("Location: ../../index.php");
exit;