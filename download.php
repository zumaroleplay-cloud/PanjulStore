<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$panel = current_panel();
$file = basename($_GET['file'] ?? '');

// pastikan file ini memang milik panel yang sedang login
$owned = false;
foreach(get_backups($panel['id']) as $b){
    if($b['file'] === $file){ $owned = true; break; }
}

$filepath = BACKUP_DIR . '/' . $file;

if(!$owned || !file_exists($filepath)){
    http_response_code(404);
    die('File backup tidak ditemukan.');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
