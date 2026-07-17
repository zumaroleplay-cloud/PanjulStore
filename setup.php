<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$panel = current_panel();
if(!$panel){ session_destroy(); header('Location: index.php'); exit; }

// jika sudah pernah setup, langsung ke dashboard
if(get_config($panel['id'])){
    header('Location: dashboard.php');
    exit;
}

$ram = (int)$panel['ram'];
$slotOptions = $ram > 3 ? [1,2,3] : [1];
$defaultSlots = $ram > 3 ? 2 : 1;

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $slotCount = max(1, min(3, (int)($_POST['slot_count'] ?? 1)));
    if($ram <= 3) $slotCount = 1;

    $paths = [];
    for($i = 1; $i <= $slotCount; $i++){
        $p = trim($_POST['backup_path_' . $i] ?? '');
        if($p === ''){ $error = 'Semua "Tempat File yang Akan Kamu Backup" wajib diisi.'; break; }
        $paths[] = $p;
    }

    $sptf     = trim($_POST['sptf'] ?? '');
    $sptfPort = trim($_POST['sptf_port'] ?? '');
    $sptfUser = trim($_POST['sptf_user'] ?? '');
    $sptfPw   = trim($_POST['sptf_pw'] ?? '');
    $dbIp     = trim($_POST['db_ip'] ?? '');
    $dbUser   = trim($_POST['db_user'] ?? '');
    $dbPw     = trim($_POST['db_pw'] ?? '');
    $dbName   = trim($_POST['db_name'] ?? '');

    if(!$error && (!$sptf || !$sptfPort || !$sptfUser || !$sptfPw || !$dbIp || !$dbUser || !$dbName)){
        $error = 'Semua field SPTF dan Database wajib diisi.';
    }

    if(!$error){
        save_config($panel['id'], [
            'sptf' => $sptf,
            'sptf_port' => $sptfPort,
            'sptf_user' => $sptfUser,
            'sptf_pw' => $sptfPw,
            'db_ip' => $dbIp,
            'db_user' => $dbUser,
            'db_pw' => $dbPw,
            'db_name' => $dbName,
            'backup_paths' => $paths,
            'backup_interval_minutes' => 60,
            'created_at' => date('c'),
        ]);
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup Panel — Panjul Store</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="center-wrap">
  <div class="card" style="max-width:480px;">
    <div class="logo">PS</div>
    <div class="title">Setup Awal Panel</div>
    <div class="subtitle">Halo <b><?= htmlspecialchars($panel['name']) ?></b>, lengkapi data berikut sebelum panel bisa dipakai.</div>

    <?php if($error): ?>
      <div class="alert error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" id="setupForm">

      <label>Sptf</label>
      <input type="text" name="sptf" placeholder="Alamat host SPTF" required>

      <label>Port Sptf</label>
      <input type="text" name="sptf_port" placeholder="Contoh: 2022" required>

      <label>Username Sptf</label>
      <input type="text" name="sptf_user" placeholder="Username SPTF" required>

      <label>Pw</label>
      <input type="password" name="sptf_pw" placeholder="Password SPTF" required>

      <label>Ip Database</label>
      <input type="text" name="db_ip" placeholder="Contoh: 127.0.0.1" required>

      <label>Username</label>
      <input type="text" name="db_user" placeholder="Username database" required>

      <label>Password</label>
      <input type="password" name="db_pw" placeholder="Password database">

      <label>Database</label>
      <input type="text" name="db_name" placeholder="Nama database" required>

      <hr style="border:none; border-top:1px solid var(--line); margin:20px 0;">

      <?php if($ram > 3): ?>
        <label>Jumlah folder yang ingin dibackup <small>(RAM kamu <?= $ram ?>GB, boleh lebih dari 1)</small></label>
        <select name="slot_count" id="slotCount" onchange="renderSlots()">
          <?php foreach($slotOptions as $n): ?>
            <option value="<?= $n ?>" <?= $n === $defaultSlots ? 'selected' : '' ?>><?= $n ?> folder</option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input type="hidden" name="slot_count" value="1">
      <?php endif; ?>

      <div id="slotsWrap"></div>

      <button type="submit" class="btn-primary">Simpan & Lanjutkan</button>
    </form>
  </div>
</div>

<script>
const RAM = <?= json_encode($ram) ?>;
const DEFAULT_SLOTS = <?= json_encode($defaultSlots) ?>;

function renderSlots(){
  const count = RAM > 3 ? parseInt(document.getElementById('slotCount').value) : 1;
  const wrap = document.getElementById('slotsWrap');
  let html = '';
  for(let i = 1; i <= count; i++){
    html += `<label>Masukan Tempat File Yg Akan Kamu Backup ${count > 1 ? '('+i+')' : ''}</label>
      <input type="text" name="backup_path_${i}" placeholder="Contoh: /home/user/data-${i}" required>`;
  }
  wrap.innerHTML = html;
}
renderSlots();
</script>
</body>
</html>
