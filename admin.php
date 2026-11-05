<?php
require __DIR__ . '/backend/bootstrap.php';
$settings = activeance_settings($pdo);

/* ---------- AUTO-SEED ADMIN ---------- */
$count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($count === 0) {
    $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)")
        ->execute(['admin', password_hash('Activeance2024!', PASSWORD_DEFAULT)]);
}

/* ---------- LOGOUT ---------- */
if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit; }

/* ---------- LOGIN ---------- */
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([trim($_POST['username'] ?? '')]);
    $u = $stmt->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php"); exit;
    }
    $login_error = 'Invalid username or password.';
}

$loggedIn = !empty($_SESSION['admin_logged_in']);
$tab = $_GET['tab'] ?? 'cars';

/* =====================================================================
   ADMIN ACTIONS (only when logged in)
   ===================================================================== */
if ($loggedIn) {

    /* --- SAVE SETTINGS --- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
        $hexOk = fn($v, $fb) => preg_match('/^#[0-9a-fA-F]{6}$/', $v ?? '') ? $v : $fb;
        $cleanStat = fn($v, $fb) => mb_substr(trim((string)($v ?? '')), 0, 20) ?: $fb;

        $updates = [
            'business_name' => trim($_POST['business_name'] ?? $settings['business_name']),
            'tagline'       => trim($_POST['tagline'] ?? $settings['tagline']),
            'quote_text'    => trim($_POST['quote_text'] ?? ''),
            'owner_name'    => trim($_POST['owner_name'] ?? $settings['owner_name']),
            'owner_bio'     => trim($_POST['owner_bio'] ?? $settings['owner_bio']),
            'phone'         => trim($_POST['phone'] ?? $settings['phone']),
            'color_primary' => $hexOk($_POST['color_primary'] ?? '', $settings['color_primary']),
            'color_dark'    => $hexOk($_POST['color_dark'] ?? '',    $settings['color_dark']),
            'color_accent'  => $hexOk($_POST['color_accent'] ?? '',  $settings['color_accent']),
            
            // Stats
            'stat_years'     => $cleanStat($_POST['stat_years']     ?? '', $settings['stat_years']     ?? '10+'),
            'stat_cars'      => $cleanStat($_POST['stat_cars']      ?? '', $settings['stat_cars']      ?? '500+'),
            'stat_inspected' => $cleanStat($_POST['stat_inspected'] ?? '', $settings['stat_inspected'] ?? '100%'),
            'stat_support'   => $cleanStat($_POST['stat_support']   ?? '', $settings['stat_support']   ?? '24/7'),
            
            // Values Cards
            'value1_title'   => trim($_POST['value1_title'] ?? $settings['value1_title'] ?? 'Honest Inspection'),
            'value1_desc'    => trim($_POST['value1_desc'] ?? $settings['value1_desc'] ?? ''),
            'value2_title'   => trim($_POST['value2_title'] ?? $settings['value2_title'] ?? 'Fair Pricing'),
            'value2_desc'    => trim($_POST['value2_desc'] ?? $settings['value2_desc'] ?? ''),
            'value3_title'   => trim($_POST['value3_title'] ?? $settings['value3_title'] ?? 'Direct Communication'),
            'value3_desc'    => trim($_POST['value3_desc'] ?? $settings['value3_desc'] ?? ''),
        ];

        if (!empty($_FILES['logo_file']['name'])) {
            $url = handle_upload($_FILES['logo_file'], 'logo');
            if ($url) $updates['logo_url'] = $url;
        } elseif (!empty($_POST['logo_url'])) {
            $updates['logo_url'] = trim($_POST['logo_url']);
        }

        if (!empty($_FILES['owner_file']['name'])) {
            $url = handle_upload($_FILES['owner_file'], 'owner');
            if ($url) $updates['owner_photo_url'] = $url;
        } elseif (!empty($_POST['owner_photo_url'])) {
            $updates['owner_photo_url'] = trim($_POST['owner_photo_url']);
        }

        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($updates)));
        $pdo->prepare("UPDATE settings SET $set WHERE id = ?")
            ->execute([...array_values($updates), $settings['id']]);

        header("Location: admin.php?tab=settings&saved=1"); exit;
    }

    /* --- ADD CAR --- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_car'])) {
        $img = handle_upload($_FILES['car_image'] ?? [], 'car') ?? trim($_POST['image_url'] ?? '');
        $make  = trim($_POST['make_model'] ?? '');
        $year  = (int) ($_POST['year'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        if ($make !== '' && $year > 0 && $price > 0 && $img !== '') {
            $pdo->prepare("INSERT INTO cars (make_model, year, price, image_url, waiting_period, shipment_status)
                           VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([
                    $make, $year, $price, $img,
                    trim($_POST['waiting_period'] ?? '') ?: null,
                    $_POST['shipment_status'] ?? 'In Stock',
                ]);
        }
        header("Location: admin.php?tab=cars"); exit;
    }

    /* --- UPDATE CAR --- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_car'])) {
        $id = (int) $_POST['car_id'];
        $current = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
        $current->execute([$id]);
        $car = $current->fetch();
        if ($car) {
            $img = $car['image_url'];
            $newImg = handle_upload($_FILES['car_image'] ?? [], 'car');
            if ($newImg) $img = $newImg;
            elseif (!empty($_POST['image_url'])) $img = trim($_POST['image_url']);

            $pdo->prepare("UPDATE cars SET make_model=?, year=?, price=?, image_url=?, waiting_period=?, shipment_status=? WHERE id=?")
                ->execute([
                    trim($_POST['make_model']),
                    (int) $_POST['year'],
                    (float) $_POST['price'],
                    $img,
                    trim($_POST['waiting_period'] ?? '') ?: null,
                    $_POST['shipment_status'] ?? 'In Stock',
                    $id,
                ]);
        }
        header("Location: admin.php?tab=cars"); exit;
    }

    /* --- DELETE CAR --- */
    if (isset($_GET['delete_car'])) {
        $pdo->prepare("DELETE FROM cars WHERE id = ?")->execute([(int) $_GET['delete_car']]);
        header("Location: admin.php?tab=cars"); exit;
    }

    /* --- MARK SOLD --- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_sold'])) {
        $id = (int) $_POST['car_id'];
        $soldPrice = (float) ($_POST['sold_price'] ?? 0);
        $pdo->prepare("UPDATE cars SET status='Sold', sold_at=NOW(), sold_price=? WHERE id=?")
            ->execute([$soldPrice, $id]);
        header("Location: admin.php?tab=cars"); exit;
    }

    /* --- UNDO SOLD --- */
    if (isset($_GET['mark_available'])) {
        $pdo->prepare("UPDATE cars SET status='Available', sold_at=NULL, sold_price=NULL WHERE id=?")
            ->execute([(int) $_GET['mark_available']]);
        header("Location: admin.php?tab=cars"); exit;
    }

    /* --- REVIEW APPROVE / DELETE --- */
    if (isset($_GET['approve_review'])) {
        $pdo->prepare("UPDATE reviews SET approved = 1 WHERE id = ?")->execute([(int) $_GET['approve_review']]);
        header("Location: admin.php?tab=reviews"); exit;
    }
    if (isset($_GET['delete_review'])) {
        $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([(int) $_GET['delete_review']]);
        header("Location: admin.php?tab=reviews"); exit;
    }
}

/* ---------- UPLOAD TAB DATA (recent files) ---------- */
$recentUploads = [];
if ($loggedIn) {
    $allowedExt = ['jpg','jpeg','png','webp','gif'];
    $publicDir  = __DIR__ . '/public';
    foreach (glob($publicDir . '/*') ?: [] as $path) {
        if (!is_file($path)) continue;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) continue;
        $recentUploads[] = [
            'file' => basename($path),
            'url'  => 'public/' . basename($path),
            'time' => filemtime($path),
            'size' => filesize($path),
        ];
    }
    usort($recentUploads, fn($a, $b) => $b['time'] <=> $a['time']);
    $recentUploads = array_slice($recentUploads, 0, 40);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin — <?= htmlspecialchars($settings['business_name']) ?></title>
<style>
    :root { --red: <?= htmlspecialchars($settings['color_primary']) ?>; --dark: <?= htmlspecialchars($settings['color_dark']) ?>; }
    * { box-sizing: border-box; }
    body { font-family: system-ui, -apple-system, sans-serif; background: var(--dark); color: #e2e8f0; margin: 0; padding: 0; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
    .card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 24px; margin-bottom: 20px; }
    h1, h2, h3 { margin-top: 0; color: white; }
    h1 { font-size: 1.5rem; }
    h2 { font-size: 1.25rem; margin-bottom: 16px; }
    label { display: block; font-size: .85rem; color: #94a3b8; margin: 12px 0 4px; text-transform: uppercase; letter-spacing: .05em; }
    input[type=text], input[type=number], input[type=url], input[type=email], input[type=password], select, textarea {
        width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: white; font-size: 1rem;
    }
    input[type=color] { width: 60px; height: 40px; border: none; background: transparent; cursor: pointer; }
    textarea { min-height: 100px; resize: vertical; font-family: inherit; }
    button, .btn { padding: 10px 18px; background: var(--red); color: white; border: none;
        border-radius: 8px; font-weight: 700; cursor: pointer; font-size: .95rem; text-decoration: none; display: inline-block; }
    .btn-green { background: #25D366; }
    .btn-gray { background: #475569; }
    .btn-sm { padding: 6px 12px; font-size: .8rem; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: .9rem; vertical-align: middle; }
    th { color: #94a3b8; text-transform: uppercase; font-size: .75rem; letter-spacing: .05em; }
    .badge { padding: 3px 8px; border-radius: 4px; font-size: .75rem; font-weight: 700; text-transform: uppercase; }
    .badge-avail { background: rgba(37,211,102,0.2); color: #4ade80; }
    .badge-sold  { background: rgba(214,40,40,0.2); color: #f87171; }
    .badge-transit { background: rgba(250,204,21,0.2); color: #facc15; }
    .tabs { display: flex; gap: 4px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 24px; flex-wrap: wrap; }
    .tabs a { padding: 12px 20px; color: #94a3b8; text-decoration: none; font-weight: 600;
              border-bottom: 3px solid transparent; transition: .15s; font-size: .9rem; }
    .tabs a:hover { color: white; }
    .tabs a.active { color: white; border-bottom-color: var(--red); }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
    @media (max-width: 640px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
    .stat { text-align: center; padding: 24px; }
    .stat .n { font-size: 2.5rem; font-weight: 900; color: var(--red); }
    .stat .l { color: #94a3b8; text-transform: uppercase; letter-spacing: .1em; font-size: .75rem; margin-top: 6px; }
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
    .alert-ok { background: rgba(37,211,102,0.15); color: #4ade80; border: 1px solid rgba(37,211,102,0.3); }
    .alert-warn { background: rgba(250,204,21,0.15); color: #facc15; border: 1px solid rgba(250,204,21,0.3); }
    .thumb { width: 70px; height: 45px; object-fit: cover; border-radius: 6px; }
    .stars { color: #facc15; font-size: 1.1rem; letter-spacing: 1px; }
    .hint { font-size: .8rem; color: #64748b; margin-top: 4px; }

    /* Password Toggle */
    .password-wrapper { position: relative; }
    .password-toggle {
        position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
        background: transparent; border: none; padding: 4px; cursor: pointer;
        color: #64748b; display: flex; align-items: center; justify-content: center;
    }
    .password-toggle:hover { color: #cbd5e1; }

    /* ---- UPLOAD TAB ---- */
    .drop {
        border: 2px dashed rgba(255,255,255,0.25); border-radius: 12px;
        padding: 40px 20px; text-align: center; transition: .2s;
        cursor: pointer; background: rgba(255,255,255,0.02);
    }
    .drop:hover, .drop.drag { border-color: var(--red); background: rgba(255,255,255,0.05); }
    .drop svg { width: 48px; height: 48px; color: #64748b; margin-bottom: 12px; }
    .drop.has-file { border-style: solid; border-color: #25D366; }
    .drop img { max-height: 180px; border-radius: 8px; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto; }
    .upload-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: end; margin-top: 16px; }
    .upload-row > div { flex: 1; min-width: 180px; }
    .upload-row select, .upload-row button {
        width: 100%; padding: 11px 14px; background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;
        color: white; font-size: 1rem; font-family: inherit;
    }
    .upload-row select option { background: #1e293b; }
    .upload-row button { background: var(--red); font-weight: 700; cursor: pointer; border: none; transition: .15s; }
    .upload-row button:hover:not(:disabled) { filter: brightness(1.15); }
    .upload-row button:disabled { opacity: .5; cursor: not-allowed; }
    .progress { height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; margin-top: 12px; display: none; }
    .progress > div { height: 100%; width: 0%; background: var(--red); transition: width .15s; }
    .upload-result { background: rgba(37,211,102,0.12); border: 1px solid rgba(37,211,102,0.35); border-radius: 8px; padding: 16px; margin-top: 16px; display: none; }
    .upload-result.error { background: rgba(214,40,40,0.12); border-color: rgba(214,40,40,0.4); }
    .upload-result img { max-height: 140px; border-radius: 6px; margin-top: 10px; display: block; }
    .url-box { display: flex; gap: 8px; margin-top: 12px; }
    .url-box input { flex: 1; padding: 9px 12px; background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #93c5fd; font-family: monospace; font-size: .9rem; }
    .url-box button { width: auto; padding: 9px 16px; background: #475569; border: none; border-radius: 6px; color: white; font-weight: 700; cursor: pointer; }
    .uploads-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-top: 16px; }
    .tile { position: relative; background: rgba(0,0,0,0.3); border-radius: 8px; overflow: hidden; aspect-ratio: 4/3; cursor: pointer; transition: .15s; }
    .tile:hover { transform: scale(1.02); box-shadow: 0 0 20px rgba(214,40,40,0.3); }
    .tile img { width: 100%; height: 100%; object-fit: cover; }
    .tile .info { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.9)); padding: 6px 8px; font-size: .7rem; color: #cbd5e1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tile .del { position: absolute; top: 6px; right: 6px; width: 28px; height: 28px; border-radius: 50%; background: rgba(214,40,40,0.85); color: white; border: none; cursor: pointer; font-weight: 700; padding: 0; font-size: 1rem; line-height: 1; }
    .tile .del:hover { background: #991b1b; }
</style>
</head>
<body>
<div class="wrap">

<?php if (!$loggedIn): ?>
    <!-- LOGIN -->
    <div class="card" style="max-width:480px;margin:80px auto;">
        <h1>Admin Login</h1>
        <?php if ($login_error): ?><div class="alert alert-warn"><?= htmlspecialchars($login_error) ?></div><?php endif; ?>
        <form method="POST">
            <label>Username</label>
            <input type="text" name="username" required>
            
            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="adminPassword" required style="padding-right: 45px;">
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <svg id="eye-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
            </div>

            <button type="submit" name="login" style="margin-top:16px;width:100%;">Login</button>
        </form>
    </div>

<?php else: ?>
    <!-- DASHBOARD HEADER -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <h1 style="margin:0;">⚙️ <?= htmlspecialchars($settings['business_name']) ?> — Admin</h1>
        <div>
            <a href="index.php" target="_blank" class="btn btn-gray btn-sm">View Site</a>
            <a href="?logout=1" class="btn btn-gray btn-sm">Logout</a>
        </div>
    </div>

    <?php if (!empty($settings['_needs_migration'])): ?>
        <div class="alert alert-warn">⚠️ The <code>settings</code> table doesn't exist yet or needs new columns. Run the SQL provided in the setup guide in phpMyAdmin.</div>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-ok">✅ Settings saved.</div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="tabs">
        <a href="?tab=cars"     class="<?= $tab==='cars'?'active':'' ?>">🚗 Cars</a>
        <a href="?tab=uploads"  class="<?= $tab==='uploads'?'active':'' ?>">📤 Uploads</a>
        <a href="?tab=settings" class="<?= $tab==='settings'?'active':'' ?>">🎨 Settings</a>
        <a href="?tab=reviews"  class="<?= $tab==='reviews'?'active':'' ?>">⭐ Reviews</a>
        <a href="?tab=stats"    class="<?= $tab==='stats'?'active':'' ?>">📊 Stats</a>
    </div>

    <?php /* ============================== CARS TAB ============================== */
    if ($tab === 'cars'):
        $editCar = null;
        if (!empty($_GET['edit'])) {
            $s = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
            $s->execute([(int) $_GET['edit']]);
            $editCar = $s->fetch();
        }
        $cars = $pdo->query("SELECT * FROM cars ORDER BY id DESC")->fetchAll();
    ?>
        <?php if ($editCar): ?>
            <!-- EDIT CAR -->
            <div class="card">
                <h2>✏️ Edit Car #<?= (int)$editCar['id'] ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="car_id" value="<?= (int)$editCar['id'] ?>">
                    <div class="grid-2">
                        <div>
                            <label>Make & Model</label>
                            <input type="text" name="make_model" value="<?= htmlspecialchars($editCar['make_model']) ?>" required>
                        </div>
                        <div>
                            <label>Year</label>
                            <input type="number" name="year" value="<?= (int)$editCar['year'] ?>" required>
                        </div>
                        <div>
                            <label>Price (N$)</label>
                            <input type="number" step="0.01" name="price" value="<?= (float)$editCar['price'] ?>" required>
                        </div>
                        <div>
                            <label>Shipment Status</label>
                            <select name="shipment_status">
                                <?php foreach (['In Stock','In Transit','Arriving Soon'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $editCar['shipment_status']===$opt?'selected':'' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Waiting Period (optional)</label>
                            <input type="text" name="waiting_period" value="<?= htmlspecialchars($editCar['waiting_period'] ?? '') ?>" placeholder="e.g. 2 weeks, Arriving March 2026">
                        </div>
                        <div>
                            <label>Replace Image (optional)</label>
                            <input type="file" name="car_image" accept="image/*">
                            <p class="hint">Current: <?= htmlspecialchars($editCar['image_url']) ?></p>
                        </div>
                    </div>
                    <label>Or Image URL</label>
                    <input type="text" name="image_url" value="<?= htmlspecialchars($editCar['image_url']) ?>">
                    <div style="margin-top:16px;">
                        <button type="submit" name="update_car">💾 Save Changes</button>
                        <a href="?tab=cars" class="btn btn-gray">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- ADD CAR -->
            <div class="card">
                <h2>➕ Add a Car</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid-2">
                        <div>
                            <label>Make & Model *</label>
                            <input type="text" name="make_model" placeholder="e.g. Toyota Hilux" required>
                        </div>
                        <div>
                            <label>Year *</label>
                            <input type="number" name="year" placeholder="2020" required>
                        </div>
                        <div>
                            <label>Price N$ *</label>
                            <input type="number" step="0.01" name="price" placeholder="250000" required>
                        </div>
                        <div>
                            <label>Shipment Status</label>
                            <select name="shipment_status">
                                <option>In Stock</option>
                                <option>In Transit</option>
                                <option>Arriving Soon</option>
                            </select>
                        </div>
                        <div>
                            <label>Waiting Period</label>
                            <input type="text" name="waiting_period" placeholder="e.g. 2 weeks">
                        </div>
                        <div>
                            <label>Image Upload</label>
                            <input type="file" name="car_image" accept="image/*">
                        </div>
                    </div>
                    <label>Or Image URL</label>
                    <input type="text" name="image_url" placeholder="public/car1.jpg">
                    <p class="hint">Tip: use the <a href="?tab=uploads" style="color:#facc15;">📤 Uploads</a> tab to upload the image first, then paste its URL here.</p>
                    <div style="margin-top:16px;">
                        <button type="submit" name="add_car" class="btn-green">Add Car</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- CARS TABLE -->
        <div class="card">
            <h2>🚗 All Cars (<?= count($cars) ?>)</h2>
            <table>
                <tr><th>Image</th><th>Car</th><th>Price</th><th>Status</th><th>Waiting</th><th>Actions</th></tr>
                <?php foreach ($cars as $c): ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($c['image_url']) ?>" class="thumb" onerror="this.src='https://via.placeholder.com/70x45?text=—'"></td>
                        <td><strong><?= htmlspecialchars($c['year'].' '.$c['make_model']) ?></strong></td>
                        <td>N$<?= number_format((float)$c['price']) ?></td>
                        <td>
                            <?php if ($c['status']==='Sold'): ?>
                                <span class="badge badge-sold">Sold</span>
                                <div class="hint">N$<?= number_format((float)($c['sold_price'] ?? $c['price'])) ?></div>
                            <?php elseif ($c['shipment_status']==='In Stock'): ?>
                                <span class="badge badge-avail">Available</span>
                            <?php else: ?>
                                <span class="badge badge-transit"><?= htmlspecialchars($c['shipment_status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($c['waiting_period'] ?? '—') ?></td>
                        <td>
                            <a href="?tab=cars&edit=<?= (int)$c['id'] ?>" class="btn btn-sm">Edit</a>
                            <?php if ($c['status'] !== 'Sold'): ?>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="car_id" value="<?= (int)$c['id'] ?>">
                                    <input type="number" step="0.01" name="sold_price" placeholder="Sold price"
                                           value="<?= (float)$c['price'] ?>" style="width:110px;display:inline-block;padding:6px;font-size:.8rem;">
                                    <button type="submit" name="mark_sold" class="btn btn-green btn-sm"
                                            onclick="return confirm('Mark as SOLD?')">Sold</button>
                                </form>
                            <?php else: ?>
                                <a href="?mark_available=<?= (int)$c['id'] ?>" class="btn btn-gray btn-sm"
                                   onclick="return confirm('Undo sold status?')">Undo</a>
                            <?php endif; ?>
                            <a href="?delete_car=<?= (int)$c['id'] ?>" class="btn btn-sm" style="background:#991b1b;"
                               onclick="return confirm('Delete this car permanently?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($cars)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#64748b;padding:30px;">No cars yet.</td></tr>
                <?php endif; ?>
            </table>
        </div>

    <?php /* ============================== UPLOADS TAB ============================== */
    elseif ($tab === 'uploads'): ?>
        <div class="card">
            <h2>📤 Upload Images</h2>
            <p class="hint" style="margin-bottom:16px;">
                Drag &amp; drop or click to select. Max <strong>5 MB</strong> per file. Allowed: JPG · PNG · WEBP · GIF.
                After uploading, copy the URL — paste it into a car's <em>Image URL</em> field, or into <a href="?tab=settings" style="color:#facc15;">Settings → Logo</a>.
            </p>

            <div class="drop" id="drop">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16a4 4 0 01-.755-7.927 4.5 4.5 0 018.902-1.116A5.5 5.5 0 0120 12.5M12 12v9m0-9l-3 3m3-3l3 3"/>
                </svg>
                <div id="dropText"><strong>Drop an image here</strong> or click to choose a file</div>
                <div class="hint" id="fileInfo"></div>
            </div>

            <div class="upload-row">
                <div>
                    <label>Upload as</label>
                    <select id="uploadType">
                        <option value="car">Car image</option>
                        <option value="logo">Business logo</option>
                        <option value="owner">Owner photo</option>
                    </select>
                </div>
                <div style="flex: 0 0 160px;">
                    <button id="uploadBtn" disabled>Upload</button>
                </div>
            </div>

            <div class="progress" id="progress"><div></div></div>

            <div class="upload-result" id="uploadResult">
                <div id="resultMsg"></div>
                <img id="resultImg" alt="" style="display:none;">
                <div class="url-box" id="urlBox" style="display:none;">
                    <input type="text" id="resultUrl" readonly>
                    <button onclick="copyUploadUrl()">Copy</button>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>🗂️ Recent Uploads (<?= count($recentUploads) ?>)</h2>
            <p class="hint" style="margin-bottom:12px;">Click any tile to copy its URL. Delete is refused if the file is used by a car, the logo, or the owner photo.</p>
            <div class="uploads-grid" id="uploadsGrid">
                <?php if (empty($recentUploads)): ?>
                    <p style="grid-column:1/-1;color:#64748b;">No images uploaded yet.</p>
                <?php else: foreach ($recentUploads as $r): ?>
                    <div class="tile" data-file="<?= htmlspecialchars($r['file']) ?>">
                        <img src="<?= htmlspecialchars($r['url']) ?>?t=<?= $r['time'] ?>" alt="">
                        <button class="del" title="Delete">✕</button>
                        <div class="info" title="<?= htmlspecialchars($r['url']) ?>"><?= htmlspecialchars($r['file']) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

    <?php /* ============================== SETTINGS TAB ============================== */
    elseif ($tab === 'settings'): ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="card">
                <h2>🏢 Business Identity</h2>
                <div class="grid-2">
                    <div>
                        <label>Business Name</label>
                        <input type="text" name="business_name" value="<?= htmlspecialchars($settings['business_name']) ?>" required>
                    </div>
                    <div>
                        <label>Phone / WhatsApp</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($settings['phone']) ?>">
                    </div>
                </div>
                <label>Tagline (hero subtitle)</label>
                <input type="text" name="tagline" value="<?= htmlspecialchars($settings['tagline']) ?>">

                <label>Quote / Motto (shown in hero, leave blank to hide)</label>
                <textarea name="quote_text" rows="2"><?= htmlspecialchars($settings['quote_text']) ?></textarea>
            </div>

            <div class="card">
                <h2>🖼️ Logo</h2>
                <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                    <img src="<?= htmlspecialchars($settings['logo_url']) ?>" style="max-height:80px;background:#fff;padding:6px;border-radius:8px;" onerror="this.style.display='none'">
                    <div style="flex:1;min-width:240px;">
                        <label>Upload New Logo</label>
                        <input type="file" name="logo_file" accept="image/*">
                        <label style="margin-top:12px;">Or Logo URL</label>
                        <input type="text" name="logo_url" value="<?= htmlspecialchars($settings['logo_url']) ?>">
                        <p class="hint">Tip: use the <a href="?tab=uploads" style="color:#facc15;">📤 Uploads</a> tab to get a permanent URL.</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>👤 Owner</h2>
                <div class="grid-2">
                    <div>
                        <label>Owner Name</label>
                        <input type="text" name="owner_name" value="<?= htmlspecialchars($settings['owner_name']) ?>">
                    </div>
                    <div>
                        <label>Owner Photo</label>
                        <input type="file" name="owner_file" accept="image/*">
                        <input type="text" name="owner_photo_url" value="<?= htmlspecialchars($settings['owner_photo_url']) ?>" style="margin-top:8px;">
                    </div>
                </div>
                <label>Owner Bio</label>
                <textarea name="owner_bio" rows="4"><?= htmlspecialchars($settings['owner_bio']) ?></textarea>
            </div>

            <!-- NEW CARD: Stats & Values -->
            <div class="card">
                <h2>📈 About Page: Stats & Values</h2>
                <p class="hint" style="margin-bottom:16px;">These numbers and cards appear on the About page.</p>
                
                <h3 style="font-size:1rem; margin-bottom:8px;">The 4 Stat Numbers</h3>
                <div class="grid-2">
                    <div>
                        <label>Years Experience</label>
                        <input type="text" name="stat_years" maxlength="20" value="<?= htmlspecialchars($settings['stat_years'] ?? '10+') ?>">
                    </div>
                    <div>
                        <label>Cars Delivered</label>
                        <input type="text" name="stat_cars" maxlength="20" value="<?= htmlspecialchars($settings['stat_cars'] ?? '500+') ?>">
                    </div>
                    <div>
                        <label>Inspected (%)</label>
                        <input type="text" name="stat_inspected" maxlength="20" value="<?= htmlspecialchars($settings['stat_inspected'] ?? '100%') ?>">
                    </div>
                    <div>
                        <label>WhatsApp Support</label>
                        <input type="text" name="stat_support" maxlength="20" value="<?= htmlspecialchars($settings['stat_support'] ?? '24/7') ?>">
                    </div>
                </div>

                <hr style="border-color: rgba(255,255,255,0.1); margin: 24px 0;">

                <h3 style="font-size:1rem; margin-bottom:8px;">The 3 Values Cards</h3>
                <div class="grid-3">
                    <div>
                        <label>Value 1 Title</label>
                        <input type="text" name="value1_title" value="<?= htmlspecialchars($settings['value1_title'] ?? 'Honest Inspection') ?>">
                        <label>Value 1 Description</label>
                        <textarea name="value1_desc" rows="3"><?= htmlspecialchars($settings['value1_desc'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label>Value 2 Title</label>
                        <input type="text" name="value2_title" value="<?= htmlspecialchars($settings['value2_title'] ?? 'Fair Pricing') ?>">
                        <label>Value 2 Description</label>
                        <textarea name="value2_desc" rows="3"><?= htmlspecialchars($settings['value2_desc'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label>Value 3 Title</label>
                        <input type="text" name="value3_title" value="<?= htmlspecialchars($settings['value3_title'] ?? 'Direct Communication') ?>">
                        <label>Value 3 Description</label>
                        <textarea name="value3_desc" rows="3"><?= htmlspecialchars($settings['value3_desc'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>🎨 Brand Colors</h2>
                <div class="grid-3">
                    <div>
                        <label>Primary (red)</label>
                        <input type="color" name="color_primary" value="<?= htmlspecialchars($settings['color_primary']) ?>">
                        <span style="font-family:monospace;"><?= htmlspecialchars($settings['color_primary']) ?></span>
                    </div>
                    <div>
                        <label>Dark Background</label>
                        <input type="color" name="color_dark" value="<?= htmlspecialchars($settings['color_dark']) ?>">
                        <span style="font-family:monospace;"><?= htmlspecialchars($settings['color_dark']) ?></span>
                    </div>
                    <div>
                        <label>Accent (WhatsApp)</label>
                        <input type="color" name="color_accent" value="<?= htmlspecialchars($settings['color_accent']) ?>">
                        <span style="font-family:monospace;"><?= htmlspecialchars($settings['color_accent']) ?></span>
                    </div>
                </div>
                <p class="hint" style="margin-top:12px;">These update the public site's Tailwind palette instantly.</p>
            </div>

            <button type="submit" name="save_settings">💾 Save Settings</button>
        </form>

    <?php /* ============================== REVIEWS TAB ============================== */
    elseif ($tab === 'reviews'):
        $pending  = $pdo->query("SELECT * FROM reviews WHERE approved=0 ORDER BY created_at DESC")->fetchAll();
        $approved = $pdo->query("SELECT * FROM reviews WHERE approved=1 ORDER BY created_at DESC")->fetchAll();
    ?>
        <div class="card">
            <h2>⏳ Pending Approval (<?= count($pending) ?>)</h2>
            <?php if (empty($pending)): ?>
                <p style="color:#64748b;">No pending reviews.</p>
            <?php else: ?>
                <?php foreach ($pending as $r): ?>
                    <div style="border:1px solid rgba(255,255,255,0.1);border-radius:8px;padding:16px;margin-bottom:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:start;gap:12px;flex-wrap:wrap;">
                            <div>
                                <strong><?= htmlspecialchars($r['name']) ?></strong>
                                <span class="stars"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span>
                                <div class="hint"><?= htmlspecialchars($r['created_at']) ?></div>
                            </div>
                            <div>
                                <a href="?approve_review=<?= (int)$r['id'] ?>" class="btn btn-green btn-sm">Approve</a>
                                <a href="?delete_review=<?= (int)$r['id'] ?>" class="btn btn-sm" style="background:#991b1b;" onclick="return confirm('Delete?')">Delete</a>
                            </div>
                        </div>
                        <p style="margin-top:12px;"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>✅ Published Reviews (<?= count($approved) ?>)</h2>
            <?php if (empty($approved)): ?>
                <p style="color:#64748b;">No published reviews yet.</p>
            <?php else: ?>
                <?php foreach ($approved as $r): ?>
                    <div style="border-bottom:1px solid rgba(255,255,255,0.1);padding:12px 0;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <strong><?= htmlspecialchars($r['name']) ?></strong>
                            <span class="stars"><?= str_repeat('★', (int)$r['rating']) ?></span>
                            <p style="margin:6px 0;color:#cbd5e1;"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                        </div>
                        <a href="?delete_review=<?= (int)$r['id'] ?>" class="btn btn-sm" style="background:#991b1b;" onclick="return confirm('Delete?')">Delete</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php /* ============================== STATS TAB ============================== */
    else:
        $totalCars     = (int) $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
        $availableCars = (int) $pdo->query("SELECT COUNT(*) FROM cars WHERE status='Available'")->fetchColumn();
        $inTransit     = (int) $pdo->query("SELECT COUNT(*) FROM cars WHERE status='Available' AND shipment_status<>'In Stock'")->fetchColumn();
        $soldCars      = (int) $pdo->query("SELECT COUNT(*) FROM cars WHERE status='Sold'")->fetchColumn();
        $revenue       = (float) $pdo->query("SELECT COALESCE(SUM(sold_price),0) FROM cars WHERE status='Sold'")->fetchColumn();
        $recentSales   = $pdo->query("SELECT * FROM cars WHERE status='Sold' ORDER BY sold_at DESC LIMIT 20")->fetchAll();
        $reviewCount   = (int) $pdo->query("SELECT COUNT(*) FROM reviews WHERE approved=1")->fetchColumn();
        $avgRating     = (float) $pdo->query("SELECT COALESCE(AVG(rating),0) FROM reviews WHERE approved=1")->fetchColumn();
    ?>
        <div class="grid-3">
            <div class="card stat">
                <div class="n"><?= $soldCars ?></div>
                <div class="l">Cars Sold</div>
            </div>
            <div class="card stat">
                <div class="n">N$<?= number_format($revenue) ?></div>
                <div class="l">Total Revenue</div>
            </div>
            <div class="card stat">
                <div class="n"><?= $availableCars ?></div>
                <div class="l">Available</div>
            </div>
            <div class="card stat">
                <div class="n"><?= $inTransit ?></div>
                <div class="l">In Transit</div>
            </div>
            <div class="card stat">
                <div class="n"><?= $reviewCount ?></div>
                <div class="l">Published Reviews</div>
            </div>
            <div class="card stat">
                <div class="n"><?= $avgRating ? number_format($avgRating, 1) : '—' ?></div>
                <div class="l">Avg Rating</div>
            </div>
        </div>

        <div class="card">
            <h2>💰 Recent Sales</h2>
            <?php if (empty($recentSales)): ?>
                <p style="color:#64748b;">No sales recorded yet.</p>
            <?php else: ?>
                <table>
                    <tr><th>Date</th><th>Car</th><th>Sold For</th></tr>
                    <?php foreach ($recentSales as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['sold_at']) ?></td>
                            <td><?= htmlspecialchars($s['year'].' '.$s['make_model']) ?></td>
                            <td>N$<?= number_format((float)($s['sold_price'] ?? $s['price'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>
</div>

<script>
    // Toggle password visibility
    function togglePassword() {
        const input = document.getElementById('adminPassword');
        const icon = document.getElementById('eye-icon');
        
        if (input.type === 'password') {
            input.type = 'text';
            // Eye with slash (hide)
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
        } else {
            input.type = 'password';
            // Normal eye (show)
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
        }
    }
</script>

<?php if ($loggedIn && $tab === 'uploads'): ?>
<script>
(function () {
    const drop        = document.getElementById('drop');
    const dropText    = document.getElementById('dropText');
    const fileInfo    = document.getElementById('fileInfo');
    const typeSelect  = document.getElementById('uploadType');
    const uploadBtn   = document.getElementById('uploadBtn');
    const progress    = document.getElementById('progress');
    const progressBar = progress.querySelector('div');
    const result      = document.getElementById('uploadResult');
    const resultMsg   = document.getElementById('resultMsg');
    const resultImg   = document.getElementById('resultImg');
    const urlBox      = document.getElementById('urlBox');
    const resultUrl   = document.getElementById('resultUrl');
    const grid        = document.getElementById('uploadsGrid');
    const MAX_MB      = 5;

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
    fileInput.style.display = 'none';
    document.body.appendChild(fileInput);

    let currentFile = null;

    drop.addEventListener('click', () => fileInput.click());
    drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('drag'); });
    drop.addEventListener('dragleave', () => drop.classList.remove('drag'));
    drop.addEventListener('drop', e => {
        e.preventDefault();
        drop.classList.remove('drag');
        if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', () => { if (fileInput.files.length) setFile(fileInput.files[0]); });

    function setFile(file) {
        result.style.display = 'none';
        resultImg.style.display = 'none';
        urlBox.style.display = 'none';

        if (!file.type.startsWith('image/')) {
            showResult(false, 'Selected file is not an image.');
            return;
        }
        if (file.size > MAX_MB * 1024 * 1024) {
            showResult(false, 'File is larger than ' + MAX_MB + ' MB.');
            return;
        }

        currentFile = file;
        uploadBtn.disabled = false;
        drop.classList.add('has-file');
        dropText.innerHTML = '<strong>' + file.name + '</strong>';
        fileInfo.textContent = (file.size / 1024).toFixed(1) + ' KB · ' + file.type;

        const existing = drop.querySelector('img');
        if (existing) existing.remove();
        const img = new Image();
        img.src = URL.createObjectURL(file);
        drop.insertBefore(img, dropText);
    }

    uploadBtn.addEventListener('click', () => {
        if (!currentFile) return;

        const form = new FormData();
        form.append('file', currentFile);
        form.append('type', typeSelect.value);

        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading…';
        progress.style.display = 'block';
        progressBar.style.width = '0%';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload.php');

        xhr.upload.addEventListener('progress', e => {
            if (e.lengthComputable) {
                progressBar.style.width = Math.round(e.loaded / e.total * 100) + '%';
            }
        });

        xhr.addEventListener('load', () => {
            progress.style.display = 'none';
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload';

            let data;
            try { data = JSON.parse(xhr.responseText); }
            catch (e) { return showResult(false, 'Unexpected server response.'); }

            if (data.success) {
                showResult(true, '✅ Uploaded successfully.');
                resultImg.src = data.url + '?t=' + Date.now();
                resultImg.style.display = 'block';
                resultUrl.value = data.url;
                urlBox.style.display = 'flex';
                addTile(data.filename);

                currentFile = null;
                drop.classList.remove('has-file');
                const oldImg = drop.querySelector('img');
                if (oldImg) oldImg.remove();
                dropText.innerHTML = '<strong>Drop an image here</strong> or click to choose a file';
                fileInfo.textContent = '';
                fileInput.value = '';
            } else {
                showResult(false, '❌ ' + (data.error || 'Upload failed.'));
            }
        });

        xhr.addEventListener('error', () => {
            progress.style.display = 'none';
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload';
            showResult(false, '❌ Network error.');
        });

        xhr.send(form);
    });

    function showResult(ok, msg) {
        result.style.display = 'block';
        result.classList.toggle('error', !ok);
        resultMsg.textContent = msg;
        if (!ok) {
            resultImg.style.display = 'none';
            urlBox.style.display = 'none';
        }
    }

    window.copyUploadUrl = function () {
        resultUrl.select();
        navigator.clipboard.writeText(resultUrl.value).then(() => {
            const old = resultUrl.value;
            resultUrl.value = '✔ Copied!';
            setTimeout(() => resultUrl.value = old, 1200);
        });
    };

    function addTile(filename) {
        const noImgs = grid.querySelector('p');
        if (noImgs) noImgs.remove();

        const tile = document.createElement('div');
        tile.className = 'tile';
        tile.dataset.file = filename;
        tile.innerHTML =
            '<img src="public/' + filename + '?t=' + Date.now() + '" alt="">' +
            '<button class="del" title="Delete">✕</button>' +
            '<div class="info" title="public/' + filename + '">' + filename + '</div>';
        grid.insertBefore(tile, grid.firstChild);
        wireTile(tile);
    }

    function wireTile(tile) {
        tile.querySelector('.del').addEventListener('click', async e => {
            e.stopPropagation();
            const file = tile.dataset.file;
            if (!confirm('Delete "' + file + '"?')) return;

            const form = new FormData();
            form.append('delete', file);
            const res  = await fetch('upload.php', { method: 'POST', body: form });
            const data = await res.json();

            if (data.success) {
                tile.remove();
            } else {
                alert(data.error || 'Could not delete file.');
            }
        });

        tile.addEventListener('click', () => {
            const url = 'public/' + tile.dataset.file;
            navigator.clipboard.writeText(url).then(() => {
                const info = tile.querySelector('.info');
                const original = info.textContent;
                info.textContent = '✔ Copied: ' + url;
                setTimeout(() => info.textContent = original, 1400);
            });
        });
    }

    document.querySelectorAll('.tile').forEach(wireTile);
})();
</script>
<?php endif; ?>

</body>
</html>