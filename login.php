<?php
require __DIR__ . '/app/config/db.php';
require __DIR__ . '/app/helpers/auth.php';

redirect_if_logged_in();

$errors = [];
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $email_value = $email;

    $stmt = $pdo->prepare("SELECT id, full_name, password_hash FROM patients WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $errors[] = "Correo o contraseña incorrectos.";
    } else {
        $_SESSION['patient_id'] = (int)$user['id'];
        $_SESSION['patient_name'] = $user['full_name'];
        header("Location: index.php");
        exit;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - MediVisual</title>

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
  display:grid;
  place-items:center;
}

.card{
  width:400px;
  border:1px solid var(--line);
  border-radius:var(--radius);
  padding:28px;
  background:linear-gradient(180deg,var(--card2),var(--card));
  box-shadow:var(--shadow);
}

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
  padding:10px;
  border-radius:12px;
  background:rgba(201,123,99,.25);
  margin:10px 0;
}

a{color:var(--accent)}
</style>
</head>

<body>

<div class="card">
<h2>Iniciar sesión</h2>

<?php if(!empty($errors)): ?>
<div class="alert">
<?php foreach($errors as $e): ?>
<p><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post">
<label>Correo</label>
<input type="email" name="email" value="<?= htmlspecialchars($email_value) ?>" required>

<label>Contraseña</label>
<input type="password" name="password" required>

<button type="submit">Entrar</button>
</form>

<p>¿No tienes cuenta? <a href="register.php">Crear cuenta</a></p>
</div>

</body>
</html>