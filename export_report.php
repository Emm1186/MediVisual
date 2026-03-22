<?php
declare(strict_types=1);

require __DIR__ . '/app/helpers/auth.php';
require __DIR__ . '/app/config/db.php';
require_login();

$patient_id = (int)$_SESSION['patient_id'];
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($record_id <= 0) {
    exit('Registro inválido.');
}

$stmt = $pdo->prepare("
    SELECT id, body_view, body_side, region_id, intensity, notes, symptom_details,
           alert_level, alert_message, clinical_summary, created_at
    FROM symptom_sessions
    WHERE id = ? AND patient_id = ?
    LIMIT 1
");
$stmt->execute([$record_id, $patient_id]);
$record = $stmt->fetch();

if (!$record) {
    exit('No se encontró el registro solicitado.');
}

$patient_name = $_SESSION['patient_name'] ?? 'Paciente';

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

$details = [];
if (!empty($record['symptom_details'])) {
    $decoded = json_decode($record['symptom_details'], true);
    if (is_array($decoded)) {
        $details = $decoded;
    }
}

$alertClass = 'none';
if (($record['alert_level'] ?? '') === 'alta') $alertClass = 'high';
if (($record['alert_level'] ?? '') === 'media') $alertClass = 'medium';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reporte clínico - MediVisual</title>
<style>
:root{
  --bg:#f4f7f2;
  --card:#ffffff;
  --text:#2f3a2f;
  --muted:#6b7c6b;
  --line:#dfe7d8;
  --accent:#7aa874;
  --shadow:0 8px 24px rgba(0,0,0,.08);
  --radius:16px;
}
*{box-sizing:border-box}
body{
  margin:0;
  font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--bg);
  color:var(--text);
  padding:24px;
}
.container{
  max-width:900px;
  margin:auto;
}
.toolbar{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin-bottom:20px;
}
.btn{
  display:inline-block;
  padding:10px 14px;
  border-radius:12px;
  text-decoration:none;
  border:1px solid var(--line);
  background:#fff;
  color:var(--text);
  font-weight:600;
  cursor:pointer;
}
.btn.primary{
  background:linear-gradient(135deg,#7aa874,#5f8f63);
  color:#fff;
}
.card{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:24px;
}
.header{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:16px;
  flex-wrap:wrap;
  margin-bottom:20px;
}
h1,h2,h3{
  margin:0 0 10px;
}
.meta{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
  gap:12px;
  margin-bottom:18px;
}
.meta-item{
  border:1px solid var(--line);
  border-radius:12px;
  padding:12px;
  background:#fbfdf9;
}
.label{
  font-size:.85rem;
  color:var(--muted);
  margin-bottom:4px;
}
.alert-box{
  margin:18px 0;
  padding:16px;
  border-radius:14px;
  border:1px solid var(--line);
}
.alert-box.high{
  background:#fde2de;
  border-color:#efb8ae;
}
.alert-box.medium{
  background:#fff0d8;
  border-color:#ecd7a4;
}
.alert-box.none{
  background:#e7f3e5;
  border-color:#cfe2cb;
}
.section{
  margin-top:18px;
}
.section-box{
  border:1px solid var(--line);
  border-radius:12px;
  padding:14px;
  background:#fcfdfb;
}
ul{
  margin:8px 0 0 18px;
}
.summary{
  white-space:pre-wrap;
  line-height:1.6;
}
@media print{
  body{
    background:#fff;
    padding:0;
  }
  .toolbar{
    display:none;
  }
  .card{
    box-shadow:none;
    border:none;
    padding:0;
  }
}
</style>
</head>
<body>

<div class="container">
  <div class="toolbar">
    <a href="history.php" class="btn">Volver</a>
    <button class="btn primary" onclick="window.print()">Imprimir / Guardar PDF</button>
  </div>

  <div class="card">
    <div class="header">
      <div>
        <h1>Reporte clínico automático</h1>
        <div style="color:#6b7c6b;">MediVisual · Registro de síntomas</div>
      </div>
      <div style="text-align:right;">
        <div><strong>Paciente:</strong> <?= htmlspecialchars($patient_name) ?></div>
        <div><strong>Fecha:</strong> <?= htmlspecialchars($record['created_at']) ?></div>
        <div><strong>ID de registro:</strong> #<?= htmlspecialchars((string)$record['id']) ?></div>
      </div>
    </div>

    <div class="meta">
      <div class="meta-item">
        <div class="label">Vista corporal</div>
        <div><?= htmlspecialchars($viewMap[$record['body_view']] ?? $record['body_view']) ?></div>
      </div>
      <div class="meta-item">
        <div class="label">Lado del cuerpo</div>
        <div><?= htmlspecialchars($sideMap[$record['body_side']] ?? $record['body_side']) ?></div>
      </div>
      <div class="meta-item">
        <div class="label">Región</div>
        <div><?= htmlspecialchars($regionMap[$record['region_id']] ?? $record['region_id']) ?></div>
      </div>
      <div class="meta-item">
        <div class="label">Intensidad</div>
        <div><?= htmlspecialchars((string)$record['intensity']) ?>/10</div>
      </div>
    </div>

    <div class="alert-box <?= $alertClass ?>">
      <h3>Nivel de alerta: <?= htmlspecialchars(strtoupper((string)($record['alert_level'] ?? 'sin alerta'))) ?></h3>
      <div><?= htmlspecialchars($record['alert_message'] ?? 'Sin mensaje automático.') ?></div>
    </div>

    <div class="section">
      <h2>Notas del paciente</h2>
      <div class="section-box">
        <?= nl2br(htmlspecialchars($record['notes'] ?: 'Sin notas registradas.')) ?>
      </div>
    </div>

    <div class="section">
      <h2>Respuestas relacionadas</h2>
      <div class="section-box">
        <?php if (!empty($details['symptoms']) && is_array($details['symptoms'])): ?>
          <strong>Síntomas asociados:</strong>
          <ul>
            <?php foreach ($details['symptoms'] as $sym): ?>
              <li><?= htmlspecialchars($sym) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div>No se registraron síntomas asociados específicos.</div>
        <?php endif; ?>

        <?php if (!empty($details['other'])): ?>
          <div style="margin-top:10px;">
            <strong>Otro síntoma referido:</strong>
            <?= htmlspecialchars($details['other']) ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="section">
      <h2>Resumen clínico automático</h2>
      <div class="section-box summary"><?= htmlspecialchars($record['clinical_summary'] ?? 'Sin resumen generado.') ?></div>
    </div>
  </div>
</div>

</body>
</html>