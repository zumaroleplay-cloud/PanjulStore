<?php
require_once __DIR__ . '/includes/db.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $panel = find_panel_by_login($username, $password);

    if($panel){
        $_SESSION['panel_id'] = $panel['id'];
        $cfg = get_config($panel['id']);
        if($cfg){
            header('Location: dashboard.php');
        } else {
            header('Location: setup.php');
        }
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Panel — Panjul Store</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="center-wrap">
  <div class="card">
    <div class="logo">PS</div>
    <div class="title">Masuk ke Panel Kamu</div>
    <div class="subtitle">Panjul Store — Player Access</div>

    <?php if($error): ?>
      <div class="alert error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Username</label>
      <input type="text" name="username" placeholder="Masukkan username panel" required autofocus>

      <label>Password</label>
      <input type="password" name="password" placeholder="Masukkan password panel" required>

      <button type="submit" class="btn-primary">Login</button>
    </form>
  </div>
</div>
</body>
</html>
