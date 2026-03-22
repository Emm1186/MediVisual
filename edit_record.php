<?php
declare(strict_types=1);

require __DIR__ . '/app/helpers/auth.php';
require __DIR__ . '/app/config/db.php';
require_login();

$patient_id = (int)$_SESSION['patient_id'];
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);

if ($record_id <= 0) {
    exit('Registro inválido.');
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

function getCategoryByRegionPHP(string $regionId): string {
    if (str_contains($regionId, 'head') || str_contains($regionId, 'neck')) return 'head';
    if (str_contains($regionId, 'chest')) return 'chest';
    if (str_contains($regionId, 'abdomen')) return 'abdomen';
    if (str_contains($regionId, 'back') || str_contains($regionId, 'glute')) return 'back';
    return 'limb';
}

$questionGroups = [
    'head' => ['Dolor de cabeza', 'Mareo', 'Visión borrosa', 'Fiebre', 'Náusea', 'Sensibilidad a la luz'],
    'chest' => ['Presión en el pecho', 'Ardor', 'Dificultad para respirar', 'Dolor al respirar', 'Palpitaciones'],
    'abdomen' => ['Náusea', 'Vómito', 'Diarrea', 'Distensión abdominal', 'Dolor tipo cólico', 'Pérdida de apetito'],
    'back' => ['Rigidez', 'Dolor al moverse', 'Dolor punzante', 'Ardor', 'Sensación de contractura'],
    'limb' => ['Hinchazón', 'Hormigueo', 'Adormecimiento', 'Dolor al mover', 'Debilidad', 'Enrojecimiento']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intensity = (int)($_POST['intensity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $symptom_details = $_POST['symptom_details'] ?? '';

    $stmt = $pdo->prepare("
        SELECT *
        FROM symptom_sessions
        WHERE id = ? AND patient_id = ?
        LIMIT 1
    ");
    $stmt->execute([$record_id, $patient_id]);
    $record = $stmt->fetch();

    if (!$record) {
        exit('Registro no encontrado.');
    }

    $decoded_details = [];
    if ($symptom_details !== '') {
        $decoded_details = json_decode($symptom_details, true);
        if (!is_array($decoded_details)) {
            $decoded_details = [];
        }
    }

    $symptoms = $decoded_details['symptoms'] ?? [];
    $otherSymptom = trim((string)($decoded_details['other'] ?? ''));

    $allSymptomText = mb_strtolower(implode(' ', $symptoms) . ' ' . $otherSymptom . ' ' . $notes);

    $region_id = $record['region_id'];

    $isChest = str_contains($region_id, 'chest');
    $isHead = str_contains($region_id, 'head') || str_contains($region_id, 'neck');
    $isAbdomen = str_contains($region_id, 'abdomen');
    $isBack = str_contains($region_id, 'back') || str_contains($region_id, 'glute');

    $hasBreathingIssue = str_contains($allSymptomText, 'dificultad para respirar') || str_contains($allSymptomText, 'dolor al respirar');
    $hasVisionIssue = str_contains($allSymptomText, 'visión borrosa');
    $hasDizziness = str_contains($allSymptomText, 'mareo');
    $hasVomiting = str_contains($allSymptomText, 'vómito');
    $hasDiarrhea = str_contains($allSymptomText, 'diarrea');
    $hasPalpitations = str_contains($allSymptomText, 'palpitaciones');

    $alert_level = 'sin alerta';
    $alert_message = 'No se detectaron señales de alarma básicas.';

    if ($isChest && ($intensity >= 8 || $hasBreathingIssue || $hasPalpitations)) {
        $alert_level = 'alta';
        $alert_message = 'Dolor torácico con posible signo de alarma. Se recomienda valoración médica inmediata.';
    } elseif ($isHead && ($hasVisionIssue || $hasDizziness) && $intensity >= 7) {
        $alert_level = 'alta';
        $alert_message = 'Síntoma neurológico potencial con intensidad elevada. Se recomienda atención médica pronta.';
    } elseif ($isAbdomen && ($hasVomiting || $hasDiarrhea) && $intensity >= 7) {
        $alert_level = 'media';
        $alert_message = 'Dolor abdominal con síntomas asociados relevantes. Se recomienda valoración médica.';
    } elseif ($isBack && $intensity >= 8) {
        $alert_level = 'media';
        $alert_message = 'Dolor intenso en espalda o zona posterior. Se recomienda revisión clínica.';
    } elseif ($intensity >= 9) {
        $alert_level = 'media';
        $alert_message = 'Dolor de alta intensidad. Se recomienda valoración médica.';
    }

    $summaryParts = [];
    $summaryParts[] = "Zona afectada: " . ($regionMap[$record['region_id']] ?? $record['region_id']);
    $summaryParts[] = "Vista corporal: " . ($record['body_view'] === 'front' ? 'Frontal' : 'Posterior');
    $summaryParts[] = "Lado: " . match($record['body_side']) {
        'left' => 'Izquierdo',
        'right' => 'Derecho',
        default => 'Centro'
    };
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

    $stmt = $pdo->prepare("
        UPDATE symptom_sessions
        SET intensity = ?, notes = ?, symptom_details = ?, alert_level = ?, alert_message = ?, clinical_summary = ?
        WHERE id = ? AND patient_id = ?
    ");

    $stmt->execute([
        $intensity,
        $notes,
        json_encode($decoded_details, JSON_UNESCAPED_UNICODE),
        $alert_level,
        $alert_message,
        $clinical_summary,
        $record_id,
        $patient_id
    ]);

    header("Location: history.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM symptom_sessions
    WHERE id = ? AND patient_id = ?
    LIMIT 1
");
$stmt->execute([$record_id, $patient_id]);
$record = $stmt->fetch();

if (!$record) {
    exit('Registro no encontrado.');
}

$details = [];
if (!empty($record['symptom_details'])) {
    $decoded = json_decode($record['symptom_details'], true);
    if (is_array($decoded)) {
        $details = $decoded;
    }
}

$currentSymptoms = $details['symptoms'] ?? [];
$currentOther = $details['other'] ?? '';

$category = getCategoryByRegionPHP($record['region_id']);
$questions = $questionGroups[$category];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Editar registro - MediVisual</title>
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
  background:linear-gradient(180deg,#f4f7f2,#e9efe4);
  padding:24px;
}
.container{
  width:min(900px,100%);
  margin:auto;
}
.card{
  border:1px solid var(--line);
  border-radius:var(--radius);
  padding:24px;
  background:linear-gradient(180deg,var(--card2),var(--card));
  box-shadow:var(--shadow);
}
.actions{
  margin-bottom:18px;
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
a.btn, button.btn{
  display:inline-block;
  padding:10px 14px;
  border-radius:12px;
  text-decoration:none;
  color:white;
  background:linear-gradient(135deg,#7aa874,#5f8f63);
  font-weight:600;
  border:none;
  cursor:pointer;
}
label{
  display:block;
  margin:12px 0 6px;
  font-weight:600;
}
input, textarea, select{
  width:100%;
  padding:12px;
  border-radius:12px;
  border:1px solid var(--line);
  background:#fff;
  color:var(--text);
}
textarea{
  min-height:110px;
  resize:vertical;
}
.info{
  padding:12px;
  border:1px solid var(--line);
  border-radius:12px;
  background:#fcfdfb;
  margin-bottom:16px;
}
.option-list{
  display:grid;
  gap:10px;
  margin-top:8px;
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
}
</style>
</head>
<body>

<div class="container">
  <div class="card">
    <div class="actions">
      <a class="btn" href="history.php">Volver</a>
    </div>

    <h1>Editar registro</h1>

    <div class="info">
      <strong>Zona:</strong> <?= htmlspecialchars($regionMap[$record['region_id']] ?? $record['region_id']) ?><br>
      <strong>Vista:</strong> <?= htmlspecialchars($record['body_view'] === 'front' ? 'Frontal' : 'Posterior') ?><br>
      <strong>Lado:</strong> <?= htmlspecialchars(match($record['body_side']) {
        'left' => 'Izquierdo',
        'right' => 'Derecho',
        default => 'Centro'
      }) ?>
    </div>

    <form method="post" id="editForm">
      <input type="hidden" name="id" value="<?= htmlspecialchars((string)$record['id']) ?>">
      <input type="hidden" name="symptom_details" id="symptom_details">

      <label for="intensity">Intensidad del dolor (1 a 10)</label>
      <select name="intensity" id="intensity" required>
        <?php for ($i=1; $i<=10; $i++): ?>
          <option value="<?= $i ?>" <?= ((int)$record['intensity'] === $i) ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
      </select>

      <label>Preguntas relacionadas</label>
      <div class="option-list">
        <?php foreach ($questions as $q): ?>
          <label class="option-item">
            <input
              type="checkbox"
              class="dynamic-check"
              data-question="<?= htmlspecialchars($q) ?>"
              <?= in_array($q, $currentSymptoms, true) ? 'checked' : '' ?>
            >
            <span><?= htmlspecialchars($q) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <label for="other_dynamic_symptom">Otro síntoma relacionado</label>
      <input
        type="text"
        id="other_dynamic_symptom"
        value="<?= htmlspecialchars((string)$currentOther) ?>"
        placeholder="Ej. sensación de presión, punzadas, ardor..."
      >

      <label for="notes">Notas adicionales</label>
      <textarea name="notes" id="notes" maxlength="255"><?= htmlspecialchars($record['notes'] ?? '') ?></textarea>

      <div class="actions" style="margin-top:18px;">
        <button type="submit" class="btn">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<script>
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

document.querySelectorAll('.dynamic-check').forEach(el => {
  el.addEventListener('change', updateSymptomDetails);
});

document.getElementById('other_dynamic_symptom').addEventListener('input', updateSymptomDetails);

document.getElementById('editForm').addEventListener('submit', () => {
  updateSymptomDetails();
});

updateSymptomDetails();
</script>

</body>
</html>