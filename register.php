<?php
require __DIR__ . '/app/config/db.php';
require __DIR__ . '/app/helpers/auth.php';

redirect_if_logged_in();

$errors = [];
$success = false;
$name_value = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $name_value = $name;
    $email_value = $email;

    if (mb_strlen($name) < 3) $errors[] = "Nombre muy corto (mínimo 3 caracteres).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Correo inválido.";
    if (strlen($password) < 6) $errors[] = "La contraseña debe tener mínimo 6 caracteres.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = "Ese correo ya está registrado.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO patients (full_name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hash]);
            $success = true;
            $name_value = '';
            $email_value = '';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro - MediVisual</title>

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
  --good:#6b8e23;
  --bad:#c97b63;
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
  display:grid;
  place-items:center;
  padding:28px 16px;
}

.container{
  width:min(900px,100%);
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:22px;
}

@media(max-width:900px){
  .container{grid-template-columns:1fr}
}

.card{
  border:1px solid var(--line);
  border-radius:var(--radius);
  padding:28px;
  background:linear-gradient(180deg,var(--card2),var(--card));
  box-shadow:var(--shadow);
}

.logo{
  width:50px;
  height:50px;
  border-radius:15px;
  background:linear-gradient(135deg,#8fbc8f,#556b2f);
  display:grid;
  place-items:center;
  font-weight:900;
  color:#0b1207;
}

h1{margin:10px 0 0}
p{color:var(--muted)}

input{
  width:100%;
  padding:12px;
  border-radius:12px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.04);
  color:var(--text);
  margin-top:6px;
}

button{
  width:100%;
  padding:12px;
  margin-top:18px;
  border-radius:12px;
  border:1px solid rgba(143,188,143,.35);
  background:linear-gradient(135deg,#6b8e23,#556b2f);
  font-weight:700;
  cursor:pointer;
}

.alert{
  padding:12px;
  border-radius:12px;
  margin:10px 0;
}

.alert.success{background:rgba(107,142,35,.25)}
.alert.error{background:rgba(201,123,99,.25)}

a{color:var(--accent)}
</style>
</head>

<body>

<div class="container">

<div class="card">
<div class="logo">MV</div>
<h1>MediVisual</h1>
<p>Registro de paciente </p>

<h2>Tu reporte de síntomas, claro desde el inicio.</h2>
<p>Crea tu cuenta para guardar tu historial y registrar tus síntomas.</p>
</div>

<div class="card">

<h2>Crear cuenta</h2>

<?php if($success): ?>
<div class="alert success">Registro exitoso. <a href="login.php">Iniciar sesión</a></div>
<?php endif; ?>

<?php if(!empty($errors)): ?>
<div class="alert error">
<?php foreach($errors as $e): ?>
<p><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post">
<label>Nombre completo</label>
<input name="name" value="<?= htmlspecialchars($name_value) ?>" required>

<label>Correo</label>
<input type="email" name="email" value="<?= htmlspecialchars($email_value) ?>" required>

<label>Contraseña</label>
<input type="password" name="password" required>

<button type="submit">Registrarme</button>
</form>

<p>¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>

</div>
</div>

</body>
</html>