<?php
declare(strict_types=1);

require __DIR__ . '/app/helpers/auth.php';
require __DIR__ . '/app/config/db.php';
require_login();

$name = $_SESSION['patient_name'] ?? 'Paciente';

$errors = $_SESSION['form_errors'] ?? [];
$success = $_SESSION['form_success'] ?? null;

$lastAlertLevel = $_SESSION['last_alert_level'] ?? null;
$lastAlertMessage = $_SESSION['last_alert_message'] ?? null;
$lastClinicalSummary = $_SESSION['last_clinical_summary'] ?? null;

unset(
    $_SESSION['form_errors'],
    $_SESSION['form_success'],
    $_SESSION['last_alert_level'],
    $_SESSION['last_alert_message'],
    $_SESSION['last_clinical_summary']
);

$patient_id = (int)$_SESSION['patient_id'];

/* ===========================
   DASHBOARD STATS
=========================== */

// total registros + promedio intensidad
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total_records,
           AVG(intensity) AS avg_intensity
    FROM symptom_sessions
    WHERE patient_id = ?
");
$stmt->execute([$patient_id]);
$stats = $stmt->fetch() ?: [
    'total_records' => 0,
    'avg_intensity' => null
];

// último registro
$stmt = $pdo->prepare("
    SELECT region_id, alert_level, created_at
    FROM symptom_sessions
    WHERE patient_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$patient_id]);
$lastRecord = $stmt->fetch();

// última alerta relevante
$stmt = $pdo->prepare("
    SELECT alert_level, alert_message, created_at
    FROM symptom_sessions
    WHERE patient_id = ? AND alert_level <> 'sin alerta'
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$patient_id]);
$lastAlertRecord = $stmt->fetch();

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
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MediVisual</title>
<style>
:root{
  --bg1:#f4f7f2;
  --bg2:#e9efe4;
  --card:#ffffff;
  --card2:#f8faf6;
  --text:#2f3a2f;
  --muted:#6b7c6b;
  --line:#e2e8dc;
  --accent:#7aa874;
  --shadow:0 10px 30px rgba(0,0,0,.08);
  --radius:18px;
}
*{box-sizing:border-box}
body{
  margin:0;
  min-height:100vh;
  font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--text);
  background:linear-gradient(180deg, #f4f7f2, #e9efe4);
  padding:24px;
}
.container{
  width:min(1300px, 100%);
  margin:auto;
}
.topbar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:16px;
  margin-bottom:24px;
  flex-wrap:wrap;
}
.brand h1{
  margin:0;
  font-size:2rem;
}
.brand p{
  margin:6px 0 0;
  color:var(--muted);
}
.actions{
  display:flex;
  gap:12px;
  flex-wrap:wrap;
}
.btn-link{
  display:inline-block;
  padding:12px 16px;
  border-radius:12px;
  text-decoration:none;
  font-weight:600;
  background:#ffffff;
  color:var(--text);
  border:1px solid var(--line);
}
.stats-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));
  gap:16px;
  margin-bottom:24px;
}
.stat-card{
  border:1px solid var(--line);
  border-radius:16px;
  padding:18px;
  background:linear-gradient(180deg,var(--card2),var(--card));
  box-shadow:var(--shadow);
}
.stat-label{
  font-size:.9rem;
  color:var(--muted);
  margin-bottom:6px;
}
.stat-value{
  font-size:1.4rem;
  font-weight:800;
  color:#365236;
}
.stat-sub{
  margin-top:6px;
  font-size:.9rem;
  color:var(--muted);
}
.grid{
  display:grid;
  grid-template-columns:1.35fr .95fr;
  gap:24px;
}
@media(max-width:1000px){
  .grid{grid-template-columns:1fr}
}
.card{
  border:1px solid var(--line);
  border-radius:var(--radius);
  padding:24px;
  background:linear-gradient(180deg,var(--card2),var(--card));
  box-shadow:var(--shadow);
}
.card h2{
  margin-top:0;
}
.alert{
  padding:12px;
  border-radius:12px;
  margin-bottom:12px;
}
.alert.error{
  background:#fbe4df;
  color:#8b4a3a;
}
.alert.success{
  background:#e3f0df;
  color:#476241;
}
.alert-box{
  margin-bottom:20px;
  border-radius:16px;
  padding:18px;
  border:1px solid var(--line);
  box-shadow:var(--shadow);
}
.alert-box.high{
  background:#fde2de;
  border-color:#f1c1b8;
}
.alert-box.medium{
  background:#fff0d8;
  border-color:#f0ddb4;
}
.alert-box.none{
  background:#e7f3e5;
  border-color:#cfe2cb;
}
.alert-title{
  font-weight:800;
  margin:0 0 8px;
  font-size:1.05rem;
}
.alert-summary{
  margin-top:10px;
  padding:12px;
  border-radius:12px;
  background:rgba(255,255,255,.6);
  white-space:pre-wrap;
}
.toolbar{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin-bottom:16px;
}
.toggle-btn{
  padding:10px 14px;
  border-radius:10px;
  border:1px solid var(--line);
  background:#ffffff;
  color:var(--text);
  cursor:pointer;
  font-weight:600;
}
.toggle-btn.active{
  background:linear-gradient(135deg,#7aa874,#5f8f63);
  color:white;
}
.map-stage{
  display:grid;
  grid-template-columns:1fr;
  justify-items:center;
}
.figure-box{
  width:100%;
  border:1px solid var(--line);
  border-radius:16px;
  padding:16px;
  background:#fcfdfb;
}
.figure-title{
  margin:0 0 10px;
  color:var(--muted);
  font-size:.95rem;
  text-align:center;
}
svg{
  width:100%;
  max-width:520px;
  height:auto;
  display:block;
  margin:auto;
}
.body-part{
  fill:#cddbc8;
  stroke:#5f8f63;
  stroke-width:2;
  cursor:pointer;
  transition:.2s ease;
}
.body-part:hover{
  fill:#9fc79a;
}
.body-part.active{
  fill:#7aa874;
}
.hidden{
  display:none;
}
.form-group{
  margin-bottom:14px;
}
label{
  display:block;
  margin-bottom:6px;
  font-weight:600;
}
input, textarea, select{
  width:100%;
  padding:12px;
  border-radius:12px;
  border:1px solid var(--line);
  background:#ffffff;
  color:var(--text);
}
textarea{
  resize:vertical;
  min-height:100px;
}
.small{
  color:var(--muted);
  font-size:.92rem;
}
.region-name{
  font-weight:700;
  color:#5f8f63;
  font-size:1rem;
}
.selected-box{
  margin:14px 0;
  padding:14px;
  border:1px solid var(--line);
  border-radius:12px;
  background:#f8fbf6;
}
.submit-btn{
  width:100%;
  padding:14px;
  border:none;
  border-radius:12px;
  background:linear-gradient(135deg,#7aa874,#5f8f63);
  color:white;
  font-size:1rem;
  font-weight:700;
  cursor:pointer;
}
.submit-btn:hover{
  filter:brightness(1.05);
}
.legend{
  margin-top:12px;
  color:var(--muted);
  font-size:.92rem;
}
.dynamic-box{
  margin-top:14px;
  padding:14px;
  border:1px solid var(--line);
  border-radius:12px;
  background:#f8fbf6;
}
.dynamic-box h3{
  margin:0 0 10px;
  font-size:1rem;
}
.option-list{
  display:grid;
  gap:10px;
}
.option-item{
  display:flex;
  align-items:center;
  gap:10px;
  background:#fff;
  border:1px solid var(--line);
  border-radius:10px;
  padding:10px 12px;
}
.option-item input[type="checkbox"]{
  width:auto;
  margin:0;
}
.no-region{
  color:var(--muted);
  font-size:.92rem;
}
</style>
</head>
<body>

<div class="container">
  <div class="topbar">
    <div class="brand">
      <h1>MediVisual</h1>
      <p>Bienvenido, <?= htmlspecialchars($name) ?> 👋</p>
    </div>

    <div class="actions">
      <a class="btn-link" href="history.php">Ver historial</a>
      <a class="btn-link" href="logout.php">Cerrar sesión</a>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Total de registros</div>
      <div class="stat-value"><?= htmlspecialchars((string)($stats['total_records'] ?? 0)) ?></div>
      <div class="stat-sub">Entradas guardadas por el paciente</div>
    </div>

    <div class="stat-card">
      <div class="stat-label">Intensidad promedio</div>
      <div class="stat-value">
        <?= $stats['avg_intensity'] !== null ? number_format((float)$stats['avg_intensity'], 1) . '/10' : '—' ?>
      </div>
      <div class="stat-sub">Promedio de dolor registrado</div>
    </div>

    <div class="stat-card">
      <div class="stat-label">Última zona registrada</div>
      <div class="stat-value" style="font-size:1.05rem;">
        <?= $lastRecord ? htmlspecialchars($regionMap[$lastRecord['region_id']] ?? $lastRecord['region_id']) : 'Sin registros' ?>
      </div>
      <div class="stat-sub">
        <?= $lastRecord ? htmlspecialchars($lastRecord['created_at']) : 'Aún no hay actividad' ?>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-label">Última alerta detectada</div>
      <div class="stat-value" style="font-size:1.05rem;">
        <?= $lastAlertRecord ? htmlspecialchars(strtoupper((string)$lastAlertRecord['alert_level'])) : 'SIN ALERTAS' ?>
      </div>
      <div class="stat-sub">
        <?= $lastAlertRecord ? htmlspecialchars($lastAlertRecord['created_at']) : 'No se han detectado alertas' ?>
      </div>
    </div>
  </div>

  <?php if ($lastAlertLevel !== null && $lastAlertMessage !== null): ?>
    <?php
      $alertClass = 'none';
      $alertTitle = 'Sin alerta';
      if ($lastAlertLevel === 'alta') {
          $alertClass = 'high';
          $alertTitle = '⚠️ Alerta alta';
      } elseif ($lastAlertLevel === 'media') {
          $alertClass = 'medium';
          $alertTitle = '⚠️ Alerta media';
      } else {
          $alertClass = 'none';
          $alertTitle = '✅ Sin alerta inmediata';
      }
    ?>
    <div class="alert-box <?= $alertClass ?>">
      <div class="alert-title"><?= $alertTitle ?></div>
      <div><?= htmlspecialchars($lastAlertMessage) ?></div>

      <?php if ($lastClinicalSummary): ?>
        <div class="alert-summary">
          <strong>Resumen clínico automático:</strong><br><br>
          <?= htmlspecialchars($lastClinicalSummary) ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="grid">
    <div class="card">
      <h2>Selecciona la zona de la molestia</h2>
      <p class="small">Primero elige una vista corporal y después haz clic sobre la zona afectada.</p>

      <div class="toolbar">
        <button type="button" class="toggle-btn active" id="frontBtn">Vista frontal</button>
        <button type="button" class="toggle-btn" id="backBtn">Vista posterior</button>
      </div>

      <div class="map-stage">
        <div class="figure-box" id="frontView">
          <p class="figure-title">Mapa corporal frontal</p>
          <svg viewBox="0 0 600 900" xmlns="http://www.w3.org/2000/svg">
            <ellipse class="body-part" id="head_front" data-view="front" data-side="center" cx="300" cy="85" rx="55" ry="62"/>
            <rect class="body-part" id="shoulder_left_front" data-view="front" data-side="left" x="190" y="150" width="70" height="50" rx="20"/>
            <rect class="body-part" id="shoulder_right_front" data-view="front" data-side="right" x="340" y="150" width="70" height="50" rx="20"/>
            <rect class="body-part" id="chest_left" data-view="front" data-side="left" x="215" y="205" width="78" height="90" rx="24"/>
            <rect class="body-part" id="chest_right" data-view="front" data-side="right" x="307" y="205" width="78" height="90" rx="24"/>
            <rect class="body-part" id="abdomen_upper" data-view="front" data-side="center" x="245" y="305" width="110" height="75" rx="18"/>
            <rect class="body-part" id="abdomen_lower" data-view="front" data-side="center" x="250" y="390" width="100" height="78" rx="18"/>
            <rect class="body-part" id="arm_left_front" data-view="front" data-side="left" x="120" y="205" width="50" height="120" rx="22"/>
            <rect class="body-part" id="arm_right_front" data-view="front" data-side="right" x="430" y="205" width="50" height="120" rx="22"/>
            <rect class="body-part" id="forearm_left_front" data-view="front" data-side="left" x="90" y="330" width="42" height="145" rx="20"/>
            <rect class="body-part" id="forearm_right_front" data-view="front" data-side="right" x="468" y="330" width="42" height="145" rx="20"/>
            <rect class="body-part" id="hand_left_front" data-view="front" data-side="left" x="82" y="480" width="38" height="52" rx="16"/>
            <rect class="body-part" id="hand_right_front" data-view="front" data-side="right" x="480" y="480" width="38" height="52" rx="16"/>
            <rect class="body-part" id="thigh_left_front" data-view="front" data-side="left" x="235" y="480" width="58" height="150" rx="24"/>
            <rect class="body-part" id="thigh_right_front" data-view="front" data-side="right" x="307" y="480" width="58" height="150" rx="24"/>
            <rect class="body-part" id="knee_left_front" data-view="front" data-side="left" x="240" y="635" width="48" height="45" rx="18"/>
            <rect class="body-part" id="knee_right_front" data-view="front" data-side="right" x="312" y="635" width="48" height="45" rx="18"/>
            <rect class="body-part" id="leg_left_front" data-view="front" data-side="left" x="238" y="685" width="46" height="125" rx="18"/>
            <rect class="body-part" id="leg_right_front" data-view="front" data-side="right" x="316" y="685" width="46" height="125" rx="18"/>
            <rect class="body-part" id="foot_left_front" data-view="front" data-side="left" x="220" y="815" width="72" height="36" rx="16"/>
            <rect class="body-part" id="foot_right_front" data-view="front" data-side="right" x="308" y="815" width="72" height="36" rx="16"/>
          </svg>
        </div>

        <div class="figure-box hidden" id="backView">
          <p class="figure-title">Mapa corporal posterior</p>
          <svg viewBox="0 0 600 900" xmlns="http://www.w3.org/2000/svg">
            <ellipse class="body-part" id="head_back" data-view="back" data-side="center" cx="300" cy="85" rx="55" ry="62"/>
            <rect class="body-part" id="neck_back" data-view="back" data-side="center" x="270" y="145" width="60" height="42" rx="16"/>
            <rect class="body-part" id="shoulder_left_back" data-view="back" data-side="left" x="190" y="185" width="70" height="50" rx="20"/>
            <rect class="body-part" id="shoulder_right_back" data-view="back" data-side="right" x="340" y="185" width="70" height="50" rx="20"/>
            <rect class="body-part" id="upper_back" data-view="back" data-side="center" x="220" y="240" width="160" height="80" rx="22"/>
            <rect class="body-part" id="mid_back" data-view="back" data-side="center" x="235" y="326" width="130" height="90" rx="20"/>
            <rect class="body-part" id="lower_back" data-view="back" data-side="center" x="245" y="422" width="110" height="65" rx="18"/>
            <rect class="body-part" id="arm_left_back" data-view="back" data-side="left" x="120" y="235" width="50" height="120" rx="22"/>
            <rect class="body-part" id="arm_right_back" data-view="back" data-side="right" x="430" y="235" width="50" height="120" rx="22"/>
            <rect class="body-part" id="forearm_left_back" data-view="back" data-side="left" x="90" y="360" width="42" height="145" rx="20"/>
            <rect class="body-part" id="forearm_right_back" data-view="back" data-side="right" x="468" y="360" width="42" height="145" rx="20"/>
            <rect class="body-part" id="hand_left_back" data-view="back" data-side="left" x="82" y="510" width="38" height="52" rx="16"/>
            <rect class="body-part" id="hand_right_back" data-view="back" data-side="right" x="480" y="510" width="38" height="52" rx="16"/>
            <rect class="body-part" id="glute_left" data-view="back" data-side="left" x="240" y="495" width="55" height="70" rx="20"/>
            <rect class="body-part" id="glute_right" data-view="back" data-side="right" x="305" y="495" width="55" height="70" rx="20"/>
            <rect class="body-part" id="thigh_left_back" data-view="back" data-side="left" x="235" y="570" width="58" height="145" rx="24"/>
            <rect class="body-part" id="thigh_right_back" data-view="back" data-side="right" x="307" y="570" width="58" height="145" rx="24"/>
            <rect class="body-part" id="knee_left_back" data-view="back" data-side="left" x="240" y="720" width="48" height="45" rx="18"/>
            <rect class="body-part" id="knee_right_back" data-view="back" data-side="right" x="312" y="720" width="48" height="45" rx="18"/>
            <rect class="body-part" id="leg_left_back" data-view="back" data-side="left" x="238" y="770" width="46" height="90" rx="18"/>
            <rect class="body-part" id="leg_right_back" data-view="back" data-side="right" x="316" y="770" width="46" height="90" rx="18"/>
            <rect class="body-part" id="foot_left_back" data-view="back" data-side="left" x="220" y="860" width="72" height="28" rx="16"/>
            <rect class="body-part" id="foot_right_back" data-view="back" data-side="right" x="308" y="860" width="72" height="28" rx="16"/>
          </svg>
        </div>
      </div>

      <div class="legend">
        Consejo: selecciona la zona más cercana a tu molestia principal. Después podrás agregar más detalle en las notas.
      </div>
    </div>

    <div class="card">
      <h2>Registrar síntoma</h2>

      <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="alert error">
          <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="app/action/create_session.php" id="symptomForm">
        <div class="form-group">
          <label>Vista corporal</label>
          <input type="text" id="body_view_display" value="No seleccionada" readonly>
          <input type="hidden" name="body_view" id="body_view" required>
        </div>

        <div class="form-group">
          <label>Lado del cuerpo</label>
          <input type="text" id="body_side_display" value="No seleccionado" readonly>
          <input type="hidden" name="body_side" id="body_side" required>
        </div>

        <div class="form-group">
          <label>Zona corporal</label>
          <input type="text" id="region_display" value="Ninguna seleccionada" readonly>
          <input type="hidden" name="region_id" id="region_id" required>
        </div>

        <div class="selected-box">
          <div class="small">Selección actual:</div>
          <div class="region-name" id="selected-region-label">Ninguna</div>
        </div>

        <div class="dynamic-box">
          <h3>Preguntas relacionadas</h3>
          <div id="dynamicQuestions" class="no-region">
            Selecciona una zona del cuerpo para mostrar preguntas específicas.
          </div>
          <input type="hidden" name="symptom_details" id="symptom_details">
        </div>

        <div class="form-group" style="margin-top:14px;">
          <label for="intensity">Intensidad del dolor (1 a 10)</label>
          <select name="intensity" id="intensity" required>
            <option value="">Selecciona</option>
            <?php for($i=1; $i<=10; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="notes">Notas adicionales</label>
          <textarea name="notes" id="notes" maxlength="255" placeholder="Ej. me duele al respirar, empezó ayer, se siente como presión, empeora al moverme..."></textarea>
        </div>

        <button type="submit" class="submit-btn">Guardar registro</button>
      </form>
    </div>
  </div>
</div>

<script>
const regionMap = {
  head_front: 'Cabeza (frontal)',
  shoulder_left_front: 'Hombro izquierdo (frontal)',
  shoulder_right_front: 'Hombro derecho (frontal)',
  chest_left: 'Pecho izquierdo',
  chest_right: 'Pecho derecho',
  abdomen_upper: 'Abdomen superior',
  abdomen_lower: 'Abdomen inferior',
  arm_left_front: 'Brazo izquierdo (frontal)',
  arm_right_front: 'Brazo derecho (frontal)',
  forearm_left_front: 'Antebrazo izquierdo (frontal)',
  forearm_right_front: 'Antebrazo derecho (frontal)',
  hand_left_front: 'Mano izquierda (frontal)',
  hand_right_front: 'Mano derecha (frontal)',
  thigh_left_front: 'Muslo izquierdo (frontal)',
  thigh_right_front: 'Muslo derecho (frontal)',
  knee_left_front: 'Rodilla izquierda (frontal)',
  knee_right_front: 'Rodilla derecha (frontal)',
  leg_left_front: 'Pierna izquierda (frontal)',
  leg_right_front: 'Pierna derecha (frontal)',
  foot_left_front: 'Pie izquierdo (frontal)',
  foot_right_front: 'Pie derecho (frontal)',
  head_back: 'Cabeza (posterior)',
  neck_back: 'Cuello (posterior)',
  shoulder_left_back: 'Hombro izquierdo (posterior)',
  shoulder_right_back: 'Hombro derecho (posterior)',
  upper_back: 'Espalda alta',
  mid_back: 'Espalda media',
  lower_back: 'Espalda baja',
  arm_left_back: 'Brazo izquierdo (posterior)',
  arm_right_back: 'Brazo derecho (posterior)',
  forearm_left_back: 'Antebrazo izquierdo (posterior)',
  forearm_right_back: 'Antebrazo derecho (posterior)',
  hand_left_back: 'Mano izquierda (posterior)',
  hand_right_back: 'Mano derecha (posterior)',
  glute_left: 'Glúteo izquierdo',
  glute_right: 'Glúteo derecho',
  thigh_left_back: 'Muslo izquierdo (posterior)',
  thigh_right_back: 'Muslo derecho (posterior)',
  knee_left_back: 'Rodilla izquierda (posterior)',
  knee_right_back: 'Rodilla derecha (posterior)',
  leg_left_back: 'Pierna izquierda (posterior)',
  leg_right_back: 'Pierna derecha (posterior)',
  foot_left_back: 'Pie izquierdo (posterior)',
  foot_right_back: 'Pie derecho (posterior)'
};

const viewMap = {
  front: 'Frontal',
  back: 'Posterior'
};

const sideMap = {
  left: 'Izquierdo',
  right: 'Derecho',
  center: 'Centro'
};

const questionGroups = {
  head: ['Dolor de cabeza', 'Mareo', 'Visión borrosa', 'Fiebre', 'Náusea', 'Sensibilidad a la luz'],
  chest: ['Presión en el pecho', 'Ardor', 'Dificultad para respirar', 'Dolor al respirar', 'Palpitaciones'],
  abdomen: ['Náusea', 'Vómito', 'Diarrea', 'Distensión abdominal', 'Dolor tipo cólico', 'Pérdida de apetito'],
  back: ['Rigidez', 'Dolor al moverse', 'Dolor punzante', 'Ardor', 'Sensación de contractura'],
  limb: ['Hinchazón', 'Hormigueo', 'Adormecimiento', 'Dolor al mover', 'Debilidad', 'Enrojecimiento']
};

function getCategoryByRegion(regionId) {
  if (regionId.includes('head') || regionId.includes('neck')) return 'head';
  if (regionId.includes('chest')) return 'chest';
  if (regionId.includes('abdomen')) return 'abdomen';
  if (regionId.includes('back') || regionId.includes('glute')) return 'back';
  return 'limb';
}

function renderQuestions(regionId) {
  const dynamicQuestions = document.getElementById('dynamicQuestions');
  const category = getCategoryByRegion(regionId);
  const questions = questionGroups[category];

  let html = '<div class="option-list">';
  questions.forEach((q) => {
    html += `
      <label class="option-item">
        <input type="checkbox" data-question="${q}" class="dynamic-check">
        <span>${q}</span>
      </label>
    `;
  });
  html += `
    <label style="margin-top:10px; display:block; font-weight:600;">Otro síntoma relacionado</label>
    <input type="text" id="other_dynamic_symptom" placeholder="Ej. sensación de presión, punzadas, ardor..." />
  `;
  html += '</div>';
  dynamicQuestions.innerHTML = html;
}

function updateSymptomDetails() {
  const checks = document.querySelectorAll('.dynamic-check:checked');
  const selected = [];

  checks.forEach(ch => {
    selected.push(ch.dataset.question);
  });

  const other = document.getElementById('other_dynamic_symptom');
  const details = {
    symptoms: selected,
    other: other ? other.value.trim() : ''
  };

  document.getElementById('symptom_details').value = JSON.stringify(details);
}

const parts = document.querySelectorAll('.body-part');
const regionInput = document.getElementById('region_id');
const bodyViewInput = document.getElementById('body_view');
const bodySideInput = document.getElementById('body_side');

const regionDisplay = document.getElementById('region_display');
const bodyViewDisplay = document.getElementById('body_view_display');
const bodySideDisplay = document.getElementById('body_side_display');
const selectedRegionLabel = document.getElementById('selected-region-label');

const frontBtn = document.getElementById('frontBtn');
const backBtn = document.getElementById('backBtn');
const frontView = document.getElementById('frontView');
const backView = document.getElementById('backView');

frontBtn.addEventListener('click', () => {
  frontView.classList.remove('hidden');
  backView.classList.add('hidden');
  frontBtn.classList.add('active');
  backBtn.classList.remove('active');
});

backBtn.addEventListener('click', () => {
  backView.classList.remove('hidden');
  frontView.classList.add('hidden');
  backBtn.classList.add('active');
  frontBtn.classList.remove('active');
});

parts.forEach(part => {
  part.addEventListener('click', () => {
    parts.forEach(p => p.classList.remove('active'));
    part.classList.add('active');

    const regionId = part.id;
    const view = part.dataset.view;
    const side = part.dataset.side;

    regionInput.value = regionId;
    bodyViewInput.value = view;
    bodySideInput.value = side;

    regionDisplay.value = regionMap[regionId];
    bodyViewDisplay.value = viewMap[view];
    bodySideDisplay.value = sideMap[side];

    selectedRegionLabel.textContent = `${regionMap[regionId]} · Vista ${viewMap[view]} · Lado ${sideMap[side]}`;

    renderQuestions(regionId);

    setTimeout(() => {
      document.querySelectorAll('.dynamic-check').forEach(el => {
        el.addEventListener('change', updateSymptomDetails);
      });
      const otherField = document.getElementById('other_dynamic_symptom');
      if (otherField) {
        otherField.addEventListener('input', updateSymptomDetails);
      }
      updateSymptomDetails();
    }, 0);
  });
});

document.getElementById('symptomForm').addEventListener('submit', () => {
  updateSymptomDetails();
});
</script>

</body>
</html>