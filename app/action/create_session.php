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

$regionMap = [
    'head_front' => 'Cabeza (frontal)',
    'shoulder_left_front' => 'Hombro izquierdo (frontal)',
    'shoulder_right_front' => 'Hombro derecho (frontal)',
    'chest_left' => 'Pecho izquierdo',
    'chest_right' => 'Pecho derecho',
    'abdomen_upper' => 'Abdomen superior',
    'abdomen_lower' => 'Abdomen inferior',
    'arm_left_front' => 'Brazo izquierdo (frontal)',
    'arm_right_front' => 'Brazo derecho (frontal)',
    'forearm_left_front' => 'Antebrazo izquierdo (frontal)',
    'forearm_right_front' => 'Antebrazo derecho (frontal)',
    'hand_left_front' => 'Mano izquierda (frontal)',
    'hand_right_front' => 'Mano derecha (frontal)',
    'thigh_left_front' => 'Muslo izquierdo (frontal)',
    'thigh_right_front' => 'Muslo derecho (frontal)',
    'knee_left_front' => 'Rodilla izquierda (frontal)',
    'knee_right_front' => 'Rodilla derecha (frontal)',
    'leg_left_front' => 'Pierna izquierda (frontal)',
    'leg_right_front' => 'Pierna derecha (frontal)',
    'foot_left_front' => 'Pie izquierdo (frontal)',
    'foot_right_front' => 'Pie derecho (frontal)',
    'head_back' => 'Cabeza (posterior)',
    'neck_back' => 'Cuello (posterior)',
    'shoulder_left_back' => 'Hombro izquierdo (posterior)',
    'shoulder_right_back' => 'Hombro derecho (posterior)',
    'upper_back' => 'Espalda alta',
    'mid_back' => 'Espalda media',
    'lower_back' => 'Espalda baja',
    'arm_left_back' => 'Brazo izquierdo (posterior)',
    'arm_right_back' => 'Brazo derecho (posterior)',
    'forearm_left_back' => 'Antebrazo izquierdo (posterior)',
    'forearm_right_back' => 'Antebrazo derecho (posterior)',
    'hand_left_back' => 'Mano izquierda (posterior)',
    'hand_right_back' => 'Mano derecha (posterior)',
    'glute_left' => 'Glúteo izquierdo',
    'glute_right' => 'Glúteo derecho',
    'thigh_left_back' => 'Muslo izquierdo (posterior)',
    'thigh_right_back' => 'Muslo derecho (posterior)',
    'knee_left_back' => 'Rodilla izquierda (posterior)',
    'knee_right_back' => 'Rodilla derecha (posterior)',
    'leg_left_back' => 'Pierna izquierda (posterior)',
    'leg_right_back' => 'Pierna derecha (posterior)',
    'foot_left_back' => 'Pie izquierdo (posterior)',
    'foot_right_back' => 'Pie derecho (posterior)'
];

$viewMap = [
    'front' => 'Frontal',
    'back' => 'Posterior'
];

$sideMap = [
    'left' => 'Izquierdo',
    'right' => 'Derecho',
    'center' => 'Centro'
];

/* ===========================
   NORMALIZACIÓN DE SÍNTOMAS
=========================== */

$symptoms = $decoded_details['symptoms'] ?? [];
$otherSymptom = trim((string)($decoded_details['other'] ?? ''));

$allSymptomText = mb_strtolower(implode(' ', $symptoms) . ' ' . $otherSymptom . ' ' . $notes);

$isChest = str_contains($region_id, 'chest');
$isHead = str_contains($region_id, 'head') || str_contains($region_id, 'neck');
$isAbdomen = str_contains($region_id, 'abdomen');
$isBack = str_contains($region_id, 'back') || str_contains($region_id, 'glute');
$isLimb = !$isChest && !$isHead && !$isAbdomen && !$isBack;

$hasBreathingIssue = str_contains($allSymptomText, 'dificultad para respirar') || str_contains($allSymptomText, 'dolor al respirar');
$hasVisionIssue = str_contains($allSymptomText, 'visión borrosa');
$hasDizziness = str_contains($allSymptomText, 'mareo');
$hasFever = str_contains($allSymptomText, 'fiebre');
$hasNausea = str_contains($allSymptomText, 'náusea');
$hasVomiting = str_contains($allSymptomText, 'vómito');
$hasDiarrhea = str_contains($allSymptomText, 'diarrea');
$hasPalpitations = str_contains($allSymptomText, 'palpitaciones');
$hasSwelling = str_contains($allSymptomText, 'hinchazón');
$hasRedness = str_contains($allSymptomText, 'enrojecimiento');
$hasNumbness = str_contains($allSymptomText, 'adormecimiento');
$hasWeakness = str_contains($allSymptomText, 'debilidad');

/* ===========================
   REGLAS DE ALERTA MEJORADAS
=========================== */

$alert_level = 'sin alerta';
$alert_message = 'No se detectaron señales de alarma básicas con la información capturada.';

if ($isChest && ($hasBreathingIssue || $hasPalpitations || $intensity >= 8)) {
    $alert_level = 'alta';
    $alert_message = 'Dolor en tórax con datos potencialmente relevantes. Se recomienda valoración médica inmediata.';
}
elseif ($isHead && (($hasVisionIssue && $intensity >= 7) || ($hasDizziness && $intensity >= 7) || ($hasFever && $hasNausea))) {
    $alert_level = 'alta';
    $alert_message = 'Síntomas en cabeza o cuello con posible señal de alarma. Se recomienda atención médica pronta.';
}
elseif ($isAbdomen && (($hasVomiting && $intensity >= 8) || ($hasDiarrhea && $intensity >= 8))) {
    $alert_level = 'alta';
    $alert_message = 'Dolor abdominal intenso con síntomas digestivos asociados. Se recomienda valoración médica pronta.';
}
elseif ($isAbdomen && (($hasVomiting || $hasDiarrhea) && $intensity >= 6)) {
    $alert_level = 'media';
    $alert_message = 'Dolor abdominal con síntomas asociados relevantes. Se recomienda seguimiento clínico.';
}
elseif ($isBack && $intensity >= 8) {
    $alert_level = 'media';
    $alert_message = 'Dolor intenso en espalda o zona posterior. Se recomienda revisión médica.';
}
elseif ($isLimb && (($hasSwelling && $hasRedness) || ($hasNumbness && $hasWeakness))) {
    $alert_level = 'media';
    $alert_message = 'Síntomas en extremidad con datos de inflamación o alteración funcional. Se recomienda valoración clínica.';
}
elseif ($intensity >= 9) {
    $alert_level = 'media';
    $alert_message = 'Dolor de muy alta intensidad. Se recomienda atención médica para valoración.';
}

/* ===========================
   RESUMEN CLÍNICO
=========================== */

$summaryParts = [];
$summaryParts[] = "Zona afectada: " . ($regionMap[$region_id] ?? $region_id);
$summaryParts[] = "Vista corporal: " . ($viewMap[$body_view] ?? $body_view);
$summaryParts[] = "Lado: " . ($sideMap[$body_side] ?? $body_side);
$summaryParts[] = "Intensidad del dolor: {$intensity}/10";

if (!empty($symptoms)) {
    $summaryParts[] = "Síntomas asociados: " . implode(', ', $symptoms);
}
if ($otherSymptom !== '') {
    $summaryParts[] = "Otro síntoma referido: " . $otherSymptom;
}
if ($notes !== '') {
    $summaryParts[] = "Notas adicionales: " . $notes;
}
$summaryParts[] = "Nivel de alerta: " . strtoupper($alert_level);
$summaryParts[] = "Observación automática: " . $alert_message;

$clinical_summary = implode(". ", $summaryParts) . ".";

/* ===========================
   GUARDAR
=========================== */

$stmt = $pdo->prepare("
    INSERT INTO symptom_sessions (
        patient_id,
        body_view,
        body_side,
        region_id,
        intensity,
        notes,
        symptom_details,
        alert_level,
        alert_message,
        clinical_summary
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $patient_id,
    $body_view,
    $body_side,
    $region_id,
    $intensity,
    $notes,
    $symptom_details !== '' ? json_encode($decoded_details, JSON_UNESCAPED_UNICODE) : null,
    $alert_level,
    $alert_message,
    $clinical_summary
]);

$_SESSION['form_success'] = "Síntoma registrado correctamente.";
$_SESSION['last_alert_level'] = $alert_level;
$_SESSION['last_alert_message'] = $alert_message;
$_SESSION['last_clinical_summary'] = $clinical_summary;

header("Location: ../../index.php");
exit;