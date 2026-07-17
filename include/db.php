<?php
// ---------------------------------------------------------
// Panjul Store - Simple JSON "database" helper
// Ganti bagian ini dengan koneksi MySQL/DB asli untuk produksi,
// dan hubungkan panels.json dengan data yang dibuat di admin/index.html
// ---------------------------------------------------------

define('DATA_DIR', __DIR__ . '/../data');
define('PANELS_FILE', DATA_DIR . '/panels.json');
define('CONFIGS_FILE', DATA_DIR . '/configs.json');
define('BACKUPS_FILE', DATA_DIR . '/backups.json');
define('BACKUP_DIR', __DIR__ . '/../backups');

function read_json($file){
    if(!file_exists($file)) return [];
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function write_json($file, $data){
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function get_panels(){ return read_json(PANELS_FILE); }

function find_panel_by_login($username, $password){
    foreach(get_panels() as $p){
        if($p['username'] === $username && $p['password'] === $password){
            return $p;
        }
    }
    return null;
}

function find_panel_by_id($id){
    foreach(get_panels() as $p){
        if($p['id'] === $id) return $p;
    }
    return null;
}

function get_config($panel_id){
    $all = read_json(CONFIGS_FILE);
    return $all[$panel_id] ?? null;
}

function save_config($panel_id, $config){
    $all = read_json(CONFIGS_FILE);
    $all[$panel_id] = $config;
    write_json(CONFIGS_FILE, $all);
}

function days_left($exp){
    if(!$exp) return null;
    $now = new DateTime('today');
    $expDate = new DateTime($exp);
    return (int)$now->diff($expDate)->format('%r%a');
}

function get_backups($panel_id){
    $all = read_json(BACKUPS_FILE);
    return $all[$panel_id] ?? [];
}

function add_backup_record($panel_id, $record){
    $all = read_json(BACKUPS_FILE);
    if(!isset($all[$panel_id])) $all[$panel_id] = [];
    array_unshift($all[$panel_id], $record);
    write_json(BACKUPS_FILE, $all);
}

if(session_status() === PHP_SESSION_NONE) session_start();

function require_login(){
    if(!isset($_SESSION['panel_id'])){
        header('Location: index.php');
        exit;
    }
}

function current_panel(){
    return find_panel_by_id($_SESSION['panel_id'] ?? '');
}
