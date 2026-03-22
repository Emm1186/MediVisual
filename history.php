<?php
declare(strict_types=1);

require __DIR__ . '/app/helpers/auth.php';
require __DIR__ . '/app/config/db.php';
require_login();

$patient_id = $_SESSION['patient_id'];

$stmt = $pdo->prepare("
    SELECT id, body_view, body_side, region_id, intensity, notes, symptom_details, created_at
    FROM symptom_sessions
    WHERE patient_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$patient_id]);
$rows = $stmt->fetchAll();

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
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Historial - MediVisual</title>
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
  width:min(1200px,100%);
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
a.btn{
  display:inline-block;
  padding:10px 14px;
  border-radius:12px;
  text-decoration:none;
  color:white;
  background:linear-gradient(135deg,#7aa874,#5f8f63);
  font-weight:600;
}
.record{
  border:1px solid var(--line);
  border-radius:14px;
  background:#fff;
  padding:16px;
  margin-bottom:14px;
}
.meta{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
  gap:10px;
  margin-bottom:10px;
}
.badge{
  display:inline-block;
  padding:4px 10px;
  border-radius:999px;
  background:#eef5ea;
  color:#4f6c4f;
  font-size:.85rem;
  font-weight:600;
}
ul{
  margin:8px 0 0 18px;
}
.empty{
  padding:16px;
  border:1px dashed var(--line);
  border-radius:12px;
  background:#fcfdfb;
  color:var(--muted);
}
</style>
</head>
<body>

<div class="container">
  <div class="card">
    <h1>Historial de síntomas</h1>

    <div class="actions">
      <a class="btn" href="index.php">Volver</a>
      <a class="btn" href="delete_history.php" onclick="return confirm('¿Seguro que deseas eliminar todo tu historial?')">Eliminar historial</a>
    </div>

    <?php if (!$rows): ?>
      <div class="empty">No tienes registros todavía.</div>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <div class="record">
          <div class="meta">
            <div><strong>Fecha:</strong><br><?= htmlspecialchars($r['created_at']) ?></div>
            <div><strong>Vista:</strong><br><span class="badge"><?= htmlspecialchars($viewMap[$r['body_view']] ?? $r['body_view']) ?></span></div>
            <div><strong>Lado:</strong><br><span class="badge"><?= htmlspecialchars($sideMap[$r['body_side']] ?? $r['body_side']) ?></span></div>
            <div><strong>Región:</strong><br><?= htmlspecialchars($regionMap[$r['region_id']] ?? $r['region_id']) ?></div>
            <div><strong>Intensidad:</strong><br><?= htmlspecialchars((string)$r['intensity']) ?>/10</div>
          </div>

          <div>
            <strong>Notas:</strong><br>
            <?= nl2br(htmlspecialchars($r['notes'] ?? 'Sin notas')) ?>
          </div>

          <?php
          $details = [];
          if (!empty($r['symptom_details'])) {
              $decoded = json_decode($r['symptom_details'], true);
              if (is_array($decoded)) {
                  $details = $decoded;
              }
          }
          ?>

          <?php if (!empty($details)): ?>
            <div style="margin-top:12px;">
              <strong>Preguntas relacionadas:</strong>
              <?php if (!empty($details['symptoms']) && is_array($details['symptoms'])): ?>
                <ul>
                  <?php foreach ($details['symptoms'] as $sym): ?>
                    <li><?= htmlspecialchars($sym) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>

              <?php if (!empty($details['other'])): ?>
                <div style="margin-top:6px;">
                  <strong>Otro síntoma:</strong>
                  <?= htmlspecialchars($details['other']) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</body>
</html>