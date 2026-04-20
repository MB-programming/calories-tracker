<?php
function getAppSettings(PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = $pdo->query("SELECT `key`, value FROM settings WHERE `key` IN ('app_name','app_logo_icon','app_logo_color')");
    $rows = $stmt->fetchAll();
    $s = [];
    foreach ($rows as $r) $s[$r['key']] = $r['value'];
    $cache = [
        'name'        => $s['app_name']        ?? 'CalTrack',
        'logo_icon'   => $s['app_logo_icon']   ?? 'fire',
        'logo_color'  => $s['app_logo_color']  ?? '',
    ];
    return $cache;
}
