<?php
declare(strict_types=1);

require __DIR__ . '/app/helpers/auth.php';
require __DIR__ . '/app/config/db.php';
require_login();

$patient_id = (int)$_SESSION['patient_id'];

$stmt = $pdo->prepare("DELETE FROM symptom_sessions WHERE patient_id = ?");
$stmt->execute([$patient_id]);

header("Location: history.php");
exit;