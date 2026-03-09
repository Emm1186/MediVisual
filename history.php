<?php
declare(strict_types=1);

require __DIR__ . '/app/helpers/auth.php';
require __DIR__ . '/app/config/db.php';
require_login();

$patient_id = $_SESSION['patient_id'];

$stmt = $pdo->prepare("
    SELECT id, region_id, intensity, notes, created_at
    FROM symptom_sessions
    WHERE patient_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$patient_id]);
$rows = $stmt->fetchAll();

$regionMap = [
    'head' => 'Cabeza',
    'chest' => 'Pecho',
    'abdomen' => 'Abdomen',
    'left_arm' => 'Brazo izquierdo',
    'right_arm' => 'Brazo derecho',
    'left_leg' => 'Pierna izquierda',
    'right_leg' => 'Pierna derecha',
    'back' => 'Espalda'
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
  --bg1:#3f4f2f;
  --bg2:#556b2f;
  --card:#1e2416;
  --card2:#2c3a1f;
  --text:#f1f5ec;
  --muted:#a7b39b;
  --line:rgba(255,255,255,.08);
  --accent:#8fbc8f;
  --shadow:0 20px 80px rgba(0,0,0,.45);
  --radius:18px;
}
*{box-sizing:border-box}
body{
  margin:0;
  min-height:100vh;
  font-family:system-ui,-apple-system,Segoe UI,Roboto;
  color:var(--text);
  background:
    radial-gradient(1200px 700px at 15% 15%, rgba(85,107,47,.35), transparent 60%),
    radial-gradient(900px 600px at 85% 20%, rgba(63,79,47,.45), transparent 55%),
    linear-gradient(180deg, #1a2113, #10150c);
  padding:24px;
}
.container{
  width:min(1000px, 100%);
  margin:auto;
}
.card{
  border:1px solid var(--line);
  border-radius:var(--radius);
  padding:24px;
  background:linear-gradient(180deg,var(--card2),var(--card));
  box-shadow:var(--shadow);
}
table{
  width:100%;
  border-collapse:collapse;
  margin-top:16px;
}
th, td{
  padding:12px;
  border-bottom:1px solid var(--line);
  text-align:left;
}
a.btn{
  display:inline-block;
  margin-right:10px;
  padding:10px 14px;
  border-radius:12px;
  text-decoration:none;
  color:white;
  background:linear-gradient(135deg,#6b8e23,#556b2f);
}
a.link{
  color:var(--accent);
  text-decoration:none;
}
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <h1>Historial de síntomas</h1>

    <p>
      <a class="btn" href="index.php">Volver</a>
      <a class="btn" href="delete_history.php" onclick="return confirm('¿Seguro que deseas eliminar todo tu historial?')">Eliminar historial</a>
    </p>

    <?php if (!$rows): ?>
      <p>No tienes registros todavía.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Región</th>
            <th>Intensidad</th>
            <th>Notas</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['created_at']) ?></td>
              <td><?= htmlspecialchars($regionMap[$r['region_id']] ?? $r['region_id']) ?></td>
              <td><?= htmlspecialchars((string)$r['intensity']) ?>/10</td>
              <td><?= htmlspecialchars($r['notes'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>