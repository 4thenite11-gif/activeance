<?php
/**
 * Activeance - Shared bootstrap: DB connection + settings helper
 */
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

function activeance_settings(PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    try {
        $row = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($row) return $cache = $row;

        $defaults = [
            'business_name'   => 'Activeance Investment CC',
            'tagline'         => 'PREMIUM PRE-OWNED VEHICLES',
            'quote_text'      => 'Driven by trust. Delivered with care.',
            'logo_url'        => 'public/logo.png',
            'owner_photo_url' => 'public/owner.jpg',
            'owner_name'      => 'Mr. A Muxinga',
            'owner_bio'       => 'With over a decade of experience in the automotive industry, Mr. Muxinga founded Activeance Investment CC with a vision to provide high-quality, inspected, and polished vehicles to Namibia.',
            'color_primary'   => '#D62828',
            'color_dark'      => '#0B132B',
            'color_accent'    => '#25D366',
            'phone'           => '+264814540060',
            'stat_years'      => '10+',
            'stat_cars'       => '500+',
            'stat_inspected'  => '100%',
            'stat_support'    => '24/7',
            'value1_title'    => 'Honest Inspection',
            'value1_desc'     => 'Every vehicle is thoroughly checked — engine, body, interior, paperwork. If it doesn\'t pass, it doesn\'t sell.',
            'value2_title'    => 'Fair Pricing',
            'value2_desc'     => 'No inflated markups, no hidden fees. The price you see is the price you pay — because trust builds repeat customers.',
            'value3_title'    => 'Direct Communication',
            'value3_desc'     => 'Talk directly to the owner via WhatsApp. No call centres, no middlemen — real answers from someone who knows every car.',
        ];
        $cols = implode(',', array_keys($defaults));
        $ph   = implode(',', array_fill(0, count($defaults), '?'));
        $pdo->prepare("INSERT INTO settings ($cols) VALUES ($ph)")->execute(array_values($defaults));
        $cache = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $cache = [
            'id'              => 0,
            'business_name'   => 'Activeance Investment CC',
            'tagline'         => 'PREMIUM PRE-OWNED VEHICLES',
            'quote_text'      => 'Driven by trust. Delivered with care.',
            'logo_url'        => 'public/logo.png',
            'owner_photo_url' => 'public/owner.jpg',
            'owner_name'      => 'Mr. A Muxinga',
            'owner_bio'       => 'With over a decade of experience...',
            'color_primary'   => '#D62828',
            'color_dark'      => '#0B132B',
            'color_accent'    => '#25D366',
            'phone'           => '+264814540060',
            'stat_years'      => '10+',
            'stat_cars'       => '500+',
            'stat_inspected'  => '100%',
            'stat_support'    => '24/7',
            'value1_title'    => 'Honest Inspection',
            'value1_desc'     => 'Every vehicle is thoroughly checked.',
            'value2_title'    => 'Fair Pricing',
            'value2_desc'     => 'No inflated markups, no hidden fees.',
            'value3_title'    => 'Direct Communication',
            'value3_desc'     => 'Talk directly to the owner via WhatsApp.',
            '_needs_migration'=> true,
        ];
    }
    return $cache;
}

// SECURITY FIX: Removed 'svg' from allowed extensions to prevent XSS
function handle_upload(array $file, string $prefix, array $allowed = ['jpg','jpeg','png','webp','gif']): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) return null;
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) return null;

    $dir = dirname(__DIR__) . '/public';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return 'public/' . $name;
    }
    return null;
}