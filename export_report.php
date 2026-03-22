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
$alertTitle = 'Sin alerta inmediata';
if (($record['alert_level'] ?? '') === 'alta') {
    $alertClass = 'high';
    $alertTitle = 'Alerta alta';
} elseif (($record['alert_level'] ?? '') === 'media') {
    $alertClass = 'medium';
    $alertTitle = 'Alerta media';
}

$today = date('d/m/Y H:i');
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
  --accent-dark:#5f8f63;
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
  max-width:950px;
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
  background:linear-gradient(135deg,var(--accent),var(--accent-dark));
  color:#fff;
}
.sheet{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:20px;
  box-shadow:var(--shadow);
  overflow:hidden;
}
.sheet-header{
  background:linear-gradient(135deg,#edf5e8,#f8fbf6);
  border-bottom:1px solid var(--line);
  padding:26px 28px;
}
.header-top{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:20px;
  flex-wrap:wrap;
}
.brand{
  display:flex;
  align-items:center;
  gap:14px;
}
.logo{
  width:54px;
  height:54px;
  border-radius:16px;
  background:linear-gradient(135deg,var(--accent),var(--accent-dark));
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:800;
  font-size:1rem;
}
.brand h1{
  margin:0;
  font-size:1.6rem;
}
.brand p{
  margin:4px 0 0;
  color:var(--muted);
}
.header-meta{
  text-align:right;
  color:var(--muted);
  font-size:.95rem;
}
.sheet-body{
  padding:26px 28px;
}
.section{
  margin-bottom:22px;
}
.section h2{
  margin:0 0 12px;
  font-size:1.08rem;
  color:#304430;
}
.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
  gap:12px;
}
.info-box{
  border:1px solid var(--line);
  border-radius:14px;
  background:#fcfdfb;
  padding:14px;
}
.label{
  font-size:.84rem;
  color:var(--muted);
  margin-bottom:6px;
}
.value{
  font-weight:600;
}
.alert-box{
  padding:16px;
  border-radius:16px;
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
.alert-box h3{
  margin:0 0 8px;
  font-size:1.05rem;
}
.panel{
  border:1px solid var(--line);
  border-radius:14px;
  padding:16px;
  background:#fcfdfb;
}
.panel p{
  margin:0;
  line-height:1.65;
}
ul{
  margin:8px 0 0 18px;
}
.summary{
  white-space:pre-wrap;
  line-height:1.7;
}
.footer-note{
  margin-top:28px;
  padding-top:16px;
  border-top:1px dashed var(--line);
  color:var(--muted);
  font-size:.9rem;
}
@media print{
  body{
    background:#fff;
    padding:0;
  }
  .toolbar{
    display:none;
  }
  .sheet{
    border:none;
    box-shadow:none;
    border-radius:0;
  }
  .sheet-header{
    background:#fff;
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

  <div class="sheet">
    <div class="sheet-header">
      <div class="header-top">
        <div class="brand">
          <div class="logo">MV</div>
          <div>
            <h1>Reporte clínico automático</h1>
            <p>MediVisual · Sistema de registro y apoyo inicial de síntomas</p>
          </div>
        </div>

        <div class="header-meta">
          <div><strong>Generado:</strong> <?= htmlspecialchars($today) ?></div>
          <div><strong>ID de registro:</strong> #<?= htmlspecialchars((string)$record['id']) ?></div>
        </div>
      </div>
    </div>

    <div class="sheet-body">
      <div class="section">
        <h2>Identificación del paciente</h2>
        <div class="grid">
          <div class="info-box">
            <div class="label">Paciente</div>
            <div class="value"><?= htmlspecialchars($patient_name) ?></div>
          </div>
          <div class="info-box">
            <div class="label">Fecha del registro</div>
            <div class="value"><?= htmlspecialchars($record['created_at']) ?></div>
          </div>
        </div>
      </div>

      <div class="section">
        <h2>Datos generales del síntoma</h2>
        <div class="grid">
          <div class="info-box">
            <div class="label">Vista corporal</div>
            <div class="value"><?= htmlspecialchars($viewMap[$record['body_view']] ?? $record['body_view']) ?></div>
          </div>
          <div class="info-box">
            <div class="label">Lado del cuerpo</div>
            <div class="value"><?= htmlspecialchars($sideMap[$record['body_side']] ?? $record['body_side']) ?></div>
          </div>
          <div class="info-box">
            <div class="label">Región anatómica</div>
            <div class="value"><?= htmlspecialchars($regionMap[$record['region_id']] ?? $record['region_id']) ?></div>
          </div>
          <div class="info-box">
            <div class="label">Intensidad</div>
            <div class="value"><?= htmlspecialchars((string)$record['intensity']) ?>/10</div>
          </div>
        </div>
      </div>

      <div class="section">
        <h2>Evaluación automática de alerta</h2>
        <div class="alert-box <?= $alertClass ?>">
          <h3><?= htmlspecialchars($alertTitle) ?></h3>
          <div><?= htmlspecialchars($record['alert_message'] ?? 'Sin mensaje automático.') ?></div>
        </div>
      </div>

      <div class="section">
        <h2>Notas del paciente</h2>
        <div class="panel">
          <p><?= nl2br(htmlspecialchars($record['notes'] ?: 'Sin notas registradas.')) ?></p>
        </div>
      </div>

      <div class="section">
        <h2>Respuestas relacionadas</h2>
        <div class="panel">
          <?php if (!empty($details['symptoms']) && is_array($details['symptoms'])): ?>
            <strong>Síntomas asociados:</strong>
            <ul>
              <?php foreach ($details['symptoms'] as $sym): ?>
                <li><?= htmlspecialchars($sym) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>No se registraron síntomas asociados específicos.</p>
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
        <div class="panel summary"><?= htmlspecialchars($record['clinical_summary'] ?? 'Sin resumen generado.') ?></div>
      </div>

      <div class="footer-note">
        Este documento es un reporte automatizado generado por MediVisual como apoyo a la comunicación inicial médico-paciente.
        No sustituye una valoración clínica profesional.
      </div>
    </div>
  </div>
</div>

</body>
</html>