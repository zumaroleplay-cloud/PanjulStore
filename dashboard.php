<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$panel = current_panel();
if(!$panel){ session_destroy(); header('Location: index.php'); exit; }

$config = get_config($panel['id']);
if(!$config){ header('Location: setup.php'); exit; }

$notice = '';

// Simpan pengaturan interval backup
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_interval'){
    $minutes = max(5, (int)($_POST['interval_minutes'] ?? 60));
    $config['backup_interval_minutes'] = $minutes;
    save_config($panel['id'], $config);
    $notice = "Jadwal backup diset setiap {$minutes} menit.";
}

// Jalankan backup manual (simulasi - di server sungguhan ini dipanggil oleh cron job setiap X menit)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_backup'){
    $filename = 'backup_' . $panel['id'] . '_' . date('Ymd_His') . '.zip';
    $filepath = BACKUP_DIR . '/' . $filename;
    // Simulasi isi file backup (di server sungguhan: zip folder dari backup_paths via SPTF/SFTP)
    $summary = "Backup Panel: {$panel['name']}\nDibuat: " . date('c') . "\nSumber folder:\n";
    foreach($config['backup_paths'] as $p){ $summary .= " - {$p}\n"; }
    file_put_contents($filepath, $summary);

    add_backup_record($panel['id'], [
        'file' => $filename,
        'created_at' => date('c'),
        'size' => filesize($filepath),
    ]);
    $notice = "Backup baru berhasil dibuat.";
}

$daysLeft = days_left($panel['exp']);
$backups = get_backups($panel['id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Panjul Store</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
  <div class="brand-wrap">
    <div class="logo" style="width:40px;height:40px;font-size:16px;">PS</div>
    <div>
      <div class="brand"><?= htmlspecialchars($panel['name']) ?></div>
      <small style="color:var(--ink-soft); font-size:11px;">RAM <?= $panel['ram'] >= 99 ? 'Unlimited' : $panel['ram'].' GB' ?></small>
    </div>
  </div>
  <a href="logout.php" class="logout-link">Keluar →</a>
</div>

<div class="container">

  <?php if($notice): ?>
    <div class="alert ok" style="margin-bottom:16px;">✓ <?= htmlspecialchars($notice) ?></div>
  <?php endif; ?>

  <?php if($daysLeft !== null && $daysLeft <= 2): ?>
    <div class="exp-banner danger">
      <div class="icon">⚠️</div>
      <div class="txt">
        <b><?= $daysLeft <= 0 ? 'Panel kamu sudah expired!' : 'Panel kamu akan segera expired!' ?></b>
        <span>
          <?= $daysLeft <= 0
            ? 'Panel akan segera dihapus. Segera perpanjang hosting untuk menghindari kehilangan data.'
            : "Sisa {$daysLeft} hari lagi. Segera perpanjang hosting atau panel akan dihapus." ?>
        </span>
      </div>
    </div>
  <?php elseif($daysLeft !== null): ?>
    <div class="exp-banner safe">
      <div class="icon">✅</div>
      <div class="txt">
        <b>Panel aktif</b>
        <span>Masa aktif tersisa <?= $daysLeft ?> hari (exp: <?= htmlspecialchars($panel['exp']) ?>)</span>
      </div>
    </div>
  <?php endif; ?>

  <div class="section">
    <h3>Konfigurasi Panel</h3>
    <div class="grid-2">
      <div><span class="chip">Sptf</span><p style="margin-top:6px; font-size:13px;"><?= htmlspecialchars($config['sptf']) ?>:<?= htmlspecialchars($config['sptf_port']) ?></p></div>
      <div><span class="chip">User Sptf</span><p style="margin-top:6px; font-size:13px;"><?= htmlspecialchars($config['sptf_user']) ?></p></div>
      <div><span class="chip">Database</span><p style="margin-top:6px; font-size:13px;"><?= htmlspecialchars($config['db_name']) ?> @ <?= htmlspecialchars($config['db_ip']) ?></p></div>
      <div><span class="chip">User DB</span><p style="margin-top:6px; font-size:13px;"><?= htmlspecialchars($config['db_user']) ?></p></div>
    </div>
    <label style="margin-top:16px;">Folder yang dibackup</label>
    <?php foreach($config['backup_paths'] as $p): ?>
      <div style="font-size:13px; color:var(--ink-soft); padding:6px 0; border-bottom:1px dashed var(--line);"><?= htmlspecialchars($p) ?></div>
    <?php endforeach; ?>
  </div>

  <div class="section">
    <h3>Jadwal Backup Otomatis</h3>
    <form method="post">
      <input type="hidden" name="action" value="save_interval">
      <label>Backup setiap berapa menit?</label>
      <input type="number" name="interval_minutes" min="5" value="<?= (int)$config['backup_interval_minutes'] ?>">
      <button type="submit" class="btn-primary">Simpan Jadwal</button>
    </form>
    <p style="font-size:11.5px; color:var(--ink-soft); margin-top:10px;">
      Catatan: jadwal ini dijalankan oleh cron job di server setiap interval yang ditentukan, dan otomatis membuat file backup baru.
    </p>
  </div>

  <div class="section">
    <h3>Riwayat Backup</h3>
    <form method="post" style="margin-bottom:14px;">
      <input type="hidden" name="action" value="run_backup">
      <button type="submit" class="btn-ghost">Jalankan Backup Sekarang</button>
    </form>

    <?php if(empty($backups)): ?>
      <p style="font-size:13px; color:var(--ink-soft);">Belum ada backup. Tekan tombol di atas untuk membuat backup pertama.</p>
    <?php else: ?>
      <?php foreach($backups as $b): ?>
        <div class="backup-row">
          <div>
            <div class="name"><?= htmlspecialchars($b['file']) ?></div>
            <div class="date"><?= date('d M Y, H:i', strtotime($b['created_at'])) ?> WIB</div>
          </div>
          <a class="dl-link" href="download.php?file=<?= urlencode($b['file']) ?>">Download</a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
