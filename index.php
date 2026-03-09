<?php
declare(strict_types=1);

require __DIR__ . '/app/helpers/auth.php';
require __DIR__ . '/app/config/db.php';
require_login();

$name = $_SESSION['patient_name'] ?? 'Paciente';

$errors = $_SESSION['form_errors'] ?? [];
$success = $_SESSION['form_success'] ?? null;

unset($_SESSION['form_errors'], $_SESSION['form_success']);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MediVisual</title>
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
  width:min(1200px, 100%);
  margin:auto;
}
.topbar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:16px;
  margin-bottom:24px;
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
.btn, .btn-link{
  display:inline-block;
  padding:12px 16px;
  border-radius:12px;
  text-decoration:none;
  font-weight:600;
}
.btn{
  background:linear-gradient(135deg,#6b8e23,#556b2f);
  color:#fff;
  border:1px solid rgba(255,255,255,.08);
}
.btn-link{
  background:rgba(255,255,255,.05);
  color:var(--text);
  border:1px solid var(--line);
}
.grid{
  display:grid;
  grid-template-columns:1.2fr .9fr;
  gap:24px;
}
@media(max-width:950px){
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
.alert.error{background:rgba(201,123,99,.25)}
.alert.success{background:rgba(107,142,35,.25)}
.map-wrap{
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:12px;
}
svg{
  width:100%;
  max-width:350px;
  height:auto;
}
.body-part{
  fill:#d9e4cf;
  stroke:#3b4a2b;
  stroke-width:2;
  cursor:pointer;
  transition:.2s ease;
}
.body-part:hover{
  fill:#8fbc8f;
}
.body-part.active{
  fill:#6b8e23;
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
  background:rgba(255,255,255,.05);
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
  color:#cfe8b7;
}
.submit-btn{
  width:100%;
  padding:14px;
  border:none;
  border-radius:12px;
  background:linear-gradient(135deg,#6b8e23,#556b2f);
  color:white;
  font-size:1rem;
  font-weight:700;
  cursor:pointer;
}
.submit-btn:hover{
  filter:brightness(1.08);
}
</style>
</head>
<body>

<div class="container">
  <div class="topbar">
    <div class="brand">
      <h1>MediVisual</h1>
      <p>Bienvenido, <?= htmlspecialchars($name) ?> </p>
    </div>

    <div class="actions">
      <a class="btn-link" href="history.php">Ver historial</a>
      <a class="btn-link" href="logout.php">Cerrar sesión</a>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <h2>Selecciona dónde sientes la molestia</h2>
      <p class="small">Haz clic sobre una zona del cuerpo para registrarla.</p>

      <div class="map-wrap">
        <svg viewBox="0 0 220 500" xmlns="http://www.w3.org/2000/svg">
          <!-- Cabeza -->
          <circle class="body-part" id="head" cx="110" cy="45" r="30"/>
          <!-- Pecho -->
          <rect class="body-part" id="chest" x="75" y="90" width="70" height="65" rx="20"/>
          <!-- Abdomen -->
          <rect class="body-part" id="abdomen" x="78" y="160" width="64" height="70" rx="18"/>
          <!-- Brazo izq -->
          <rect class="body-part" id="left_arm" x="35" y="95" width="28" height="120" rx="14"/>
          <!-- Brazo der -->
          <rect class="body-part" id="right_arm" x="157" y="95" width="28" height="120" rx="14"/>
          <!-- Pierna izq -->
          <rect class="body-part" id="left_leg" x="82" y="240" width="24" height="170" rx="12"/>
          <!-- Pierna der -->
          <rect class="body-part" id="right_leg" x="114" y="240" width="24" height="170" rx="12"/>
          <!-- Espalda (botón aparte visual) -->
          <rect class="body-part" id="back" x="70" y="420" width="80" height="40" rx="12"/>
          <text x="110" y="445" text-anchor="middle" font-size="14" fill="#223018">ESPALDA</text>
        </svg>

        <p class="small">
          Región seleccionada:
          <span class="region-name" id="selected-region-label">Ninguna</span>
        </p>
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

      <form method="post" action="app/actions/create_session.php">
        <div class="form-group">
          <label>Zona corporal</label>
          <input type="text" id="region_display" value="Ninguna seleccionada" readonly>
          <input type="hidden" name="region_id" id="region_id" required>
        </div>

        <div class="form-group">
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
          <textarea name="notes" id="notes" maxlength="255" placeholder="Ej. me duele desde ayer, se siente punzante..."></textarea>
        </div>

        <button type="submit" class="submit-btn">Guardar registro</button>
      </form>
    </div>
  </div>
</div>

<script>
const regionMap = {
  head: 'Cabeza',
  chest: 'Pecho',
  abdomen: 'Abdomen',
  left_arm: 'Brazo izquierdo',
  right_arm: 'Brazo derecho',
  left_leg: 'Pierna izquierda',
  right_leg: 'Pierna derecha',
  back: 'Espalda'
};

const parts = document.querySelectorAll('.body-part');
const regionInput = document.getElementById('region_id');
const regionDisplay = document.getElementById('region_display');
const selectedRegionLabel = document.getElementById('selected-region-label');

parts.forEach(part => {
  part.addEventListener('click', () => {
    parts.forEach(p => p.classList.remove('active'));
    part.classList.add('active');

    const id = part.id;
    regionInput.value = id;
    regionDisplay.value = regionMap[id];
    selectedRegionLabel.textContent = regionMap[id];
  });
});
</script>

</body>
</html>