<?php
require_once('admin-common.php');
admin_require_login();

$table = isset($_GET['table']) && in_array($_GET['table'], $admin_tables, true) ? $_GET['table'] : '';
$columns = $table !== '' ? admin_table_columns($table) : array();
$primary_key = admin_primary_key($columns);
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $table !== '') {
    admin_verify_csrf();
    $posted_action = isset($_POST['record_action']) ? $_POST['record_action'] : '';
    $column_names = array_map(function ($column) {
        return $column['Field'];
    }, $columns);

    if ($posted_action === 'delete' && $primary_key && isset($_POST['record_id'])) {
        $record_id = mysqli_real_escape_string($con, (string) $_POST['record_id']);
        $result = mysqli_query($con, "DELETE FROM `" . $table . "` WHERE `" . $primary_key . "` = '" . $record_id . "' LIMIT 1");
        if ($result) {
            admin_flash('Data berhasil dihapus.', 'success');
        } else {
            error_log('Admin delete failed for ' . $table . ': ' . mysqli_error($con));
            admin_flash('Data tidak dapat dihapus. Data mungkin masih digunakan oleh tabel lain.', 'danger');
        }
        admin_redirect('index.php?table=' . urlencode($table));
    }

    if ($posted_action === 'save' && ($action === 'create' || ($action === 'edit' && $primary_key))) {
        $values = array();
        $editing = $action === 'edit';
        foreach ($columns as $column) {
            $field = $column['Field'];
            if ($editing && $field === $primary_key) {
                continue;
            }
            if (!$editing && strpos($column['Extra'], 'auto_increment') !== false) {
                continue;
            }
            if (!$editing && $field === 'date' && $column['Default'] !== null) {
                continue;
            }
            if ($table === 'customer' && $field === 'customer_pass') {
                $password = isset($_POST[$field]) ? (string) $_POST[$field] : '';
                if ($password === '' && $editing) {
                    continue;
                }
                if ($password === '' && !$editing) {
                    $message = 'Password pelanggan wajib diisi saat membuat data baru.';
                    $message_type = 'danger';
                    break;
                }
                $values[$field] = $password;
                continue;
            }
            if (array_key_exists($field, $_POST)) {
                $values[$field] = trim((string) $_POST[$field]);
            } elseif (!$editing && $column['Null'] === 'YES') {
                $values[$field] = null;
            } elseif (!$editing && $column['Default'] === null && $column['Null'] !== 'YES') {
                $message = 'Field ' . $field . ' wajib diisi.';
                $message_type = 'danger';
                break;
            }
        }

        if ($message === '') {
            $safe_values = array();
            foreach ($values as $field => $value) {
                if (!in_array($field, $column_names, true)) {
                    continue;
                }
                $safe_field = '`' . $field . '`';
                $safe_value = $value === null ? 'NULL' : "'" . mysqli_real_escape_string($con, $value) . "'";
                $safe_values[$safe_field] = $safe_value;
            }

            if ($editing) {
                $updates = array();
                foreach ($safe_values as $field => $value) {
                    $updates[] = $field . ' = ' . $value;
                }
                $record_id = mysqli_real_escape_string($con, (string) $_POST['record_id']);
                $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $updates) . ' WHERE `' . $primary_key . "` = '" . $record_id . "' LIMIT 1";
            } else {
                $sql = 'INSERT INTO `' . $table . '` (' . implode(', ', array_keys($safe_values)) . ') VALUES (' . implode(', ', array_values($safe_values)) . ')';
            }

            $result = mysqli_query($con, $sql);
            if ($result) {
                admin_flash($editing ? 'Perubahan berhasil disimpan.' : 'Data baru berhasil ditambahkan.', 'success');
                admin_redirect('index.php?table=' . urlencode($table));
            }
            error_log('Admin save failed for ' . $table . ': ' . mysqli_error($con));
            $message = 'Data gagal disimpan. Periksa input dan relasi antar tabel.';
            $message_type = 'danger';
        }
    }
}

$flash = isset($_SESSION['admin_flash']) ? $_SESSION['admin_flash'] : null;
unset($_SESSION['admin_flash']);
$record = null;
if ($table !== '' && $action === 'edit' && $primary_key && isset($_GET['id'])) {
    $record_id = mysqli_real_escape_string($con, (string) $_GET['id']);
    $record_result = mysqli_query($con, "SELECT * FROM `" . $table . "` WHERE `" . $primary_key . "` = '" . $record_id . "' LIMIT 1");
    $record = $record_result ? mysqli_fetch_assoc($record_result) : null;
    if (!$record) {
        $message = 'Data yang diminta tidak ditemukan.';
        $message_type = 'warning';
    }
}

$table_counts = array();
foreach ($admin_tables as $count_table) {
    $count_result = mysqli_query($con, 'SELECT COUNT(*) AS total FROM `' . $count_table . '`');
    $table_counts[$count_table] = $count_result ? (int) mysqli_fetch_assoc($count_result)['total'] : 0;
}

$list_result = null;
$list_limit = 25;
$list_page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
if ($table !== '' && $action === '') {
    $offset = ($list_page - 1) * $list_limit;
    $list_result = mysqli_query($con, 'SELECT * FROM `' . $table . '` ORDER BY 1 DESC LIMIT ' . $offset . ', ' . $list_limit);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Naon Weh.</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root { --admin-blue: #4e73df; --admin-dark: #253858; --admin-bg: #f5f7fb; }
        body { margin: 0; background: var(--admin-bg); color: #343a40; font-family: Arial, sans-serif; }
        .admin-layout { min-height: 100vh; display: flex; }
        .admin-sidebar { width: 245px; flex: 0 0 245px; background: linear-gradient(180deg, #263b78, #1d2c5b); color: white; }
        .admin-brand { display: block; padding: 22px 20px; color: white; font-size: 19px; font-weight: 700; border-bottom: 1px solid #ffffff20; }
        .admin-brand:hover { color: white; text-decoration: none; }
        .sidebar-heading { padding: 22px 20px 8px; color: #ffffff80; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .admin-nav { list-style: none; padding: 0 12px; margin: 0; }
        .admin-nav a { display: flex; gap: 12px; align-items: center; padding: 11px 12px; color: #ffffffc9; border-radius: 6px; font-size: 14px; }
        .admin-nav a:hover, .admin-nav a.active { color: white; background: #ffffff20; text-decoration: none; }
        .admin-nav i { width: 18px; text-align: center; }
        .admin-main { min-width: 0; flex: 1; }
        .admin-topbar { height: 66px; padding: 0 28px; display: flex; justify-content: flex-end; align-items: center; background: white; box-shadow: 0 2px 10px #2538580d; }
        .admin-content { padding: 28px; }
        .page-title { color: #343a40; font-size: 25px; font-weight: 700; margin-bottom: 22px; }
        .stat-card, .content-card { border: 0; border-radius: 9px; box-shadow: 0 5px 18px #2538580d; }
        .stat-card { border-left: 4px solid var(--admin-blue); }
        .stat-card .number { font-size: 24px; font-weight: 700; color: #333; }
        .stat-card .label { color: #778095; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .content-card .card-header { padding: 17px 20px; background: white; border-bottom: 1px solid #edf0f5; font-weight: 700; }
        .content-card .card-body { padding: 20px; }
        .table-responsive { min-height: 180px; }
        .table thead th { border-top: 0; color: #6b7280; font-size: 12px; text-transform: uppercase; white-space: nowrap; }
        .table td { vertical-align: middle; max-width: 260px; overflow-wrap: anywhere; }
        .action-buttons { white-space: nowrap; }
        .field-help { color: #7d8594; font-size: 12px; }
        @media (max-width: 767.98px) {
            .admin-layout { display: block; }
            .admin-sidebar { width: auto; }
            .admin-brand { padding: 14px 18px; }
            .admin-nav { display: flex; overflow-x: auto; padding: 0 10px 10px; }
            .admin-nav li { flex: 0 0 auto; }
            .sidebar-heading { display: none; }
            .admin-content { padding: 18px 12px; }
            .admin-topbar { height: 54px; padding: 0 16px; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="index.php"><i class="fas fa-store mr-2"></i>Naon Weh Admin</a>
        <div class="sidebar-heading">Dashboard</div>
        <ul class="admin-nav">
            <li><a class="<?php echo $table === '' ? 'active' : ''; ?>" href="index.php"><i class="fas fa-chart-pie"></i>Ringkasan</a></li>
            <li><a href="index.php?table=products&action=create"><i class="fas fa-plus-circle"></i>Tambah Produk</a></li>
        </ul>
        <div class="sidebar-heading">Kelola Database</div>
        <ul class="admin-nav">
            <?php foreach ($admin_tables as $nav_table) { ?>
                <li><a class="<?php echo $table === $nav_table ? 'active' : ''; ?>" href="index.php?table=<?php echo urlencode($nav_table); ?>">
                    <i class="fas <?php echo $nav_table === 'products' ? 'fa-tshirt' : ($nav_table === 'customer' ? 'fa-users' : ($nav_table === 'orders' ? 'fa-receipt' : 'fa-table')); ?>"></i>
                    <?php echo admin_escape(ucwords(str_replace('_', ' ', $nav_table))); ?>
                </a></li>
            <?php } ?>
        </ul>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <span class="text-muted mr-3"><?php echo admin_escape($_SESSION['admin_username']); ?></span>
            <a class="btn btn-sm btn-outline-primary mr-2" href="change-password.php"><i class="fas fa-key mr-1"></i>Ubah password</a>
            <a class="btn btn-sm btn-outline-secondary" href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i>Keluar</a>
        </header>
        <div class="admin-content">
            <?php if ($flash) { ?><div class="alert alert-<?php echo admin_escape($flash['type']); ?> alert-dismissible fade show"><?php echo admin_escape($flash['message']); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php } ?>
            <?php if ($message !== '') { ?><div class="alert alert-<?php echo admin_escape($message_type); ?>"><?php echo admin_escape($message); ?></div><?php } ?>

            <?php if ($table === '') { ?>
                <h1 class="page-title">Ringkasan toko</h1>
                <div class="row">
                    <?php foreach ($table_counts as $count_table => $count) { ?>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a class="text-decoration-none" href="index.php?table=<?php echo urlencode($count_table); ?>">
                                <div class="card stat-card h-100"><div class="card-body d-flex justify-content-between align-items-center">
                                    <div><div class="label"><?php echo admin_escape(ucwords(str_replace('_', ' ', $count_table))); ?></div><div class="number"><?php echo $count; ?></div></div>
                                    <i class="fas fa-database fa-2x text-light"></i>
                                </div></div>
                            </a>
                        </div>
                    <?php } ?>
                </div>
                <div class="card content-card mt-2">
                    <div class="card-header">Kelola toko</div>
                    <div class="card-body">
                        <p class="mb-3">Pilih tabel dari menu untuk melihat, menambah, mengedit, atau menghapus datanya.</p>
                        <a class="btn btn-primary" href="index.php?table=products&action=create"><i class="fas fa-plus mr-1"></i>Tambah produk</a>
                    </div>
                </div>
            <?php } elseif ($columns && ($action === 'create' || $action === 'edit')) { ?>
                <h1 class="page-title"><?php echo $action === 'create' ? 'Tambah data' : 'Edit data'; ?>: <?php echo admin_escape(ucwords(str_replace('_', ' ', $table))); ?></h1>
                <div class="card content-card">
                    <div class="card-header"><?php echo admin_escape($table); ?></div>
                    <div class="card-body">
                        <?php if ($action === 'edit' && !$record) { ?>
                            <p>Data tidak ditemukan.</p>
                        <?php } else { ?>
                            <form method="post" action="index.php?table=<?php echo urlencode($table); ?>&action=<?php echo admin_escape($action); ?><?php echo $action === 'edit' ? '&id=' . urlencode((string) $record[$primary_key]) : ''; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo admin_escape(admin_csrf_token()); ?>">
                                <input type="hidden" name="record_action" value="save">
                                <?php if ($action === 'edit') { ?><input type="hidden" name="record_id" value="<?php echo admin_escape($record[$primary_key]); ?>"><?php } ?>
                                <?php foreach ($columns as $column) {
                                    $field = $column['Field'];
                                    $type = strtolower($column['Type']);
                                    $is_primary = $field === $primary_key;
                                    $is_generated = strpos($column['Extra'], 'auto_increment') !== false;
                                    if ($is_primary && ($action === 'create' && $is_generated)) {
                                        continue;
                                    }
                                    if ($action !== 'edit' && $field === 'date' && $column['Default'] !== null) {
                                        continue;
                                    }
                                    $input_value = isset($record[$field]) ? $record[$field] : '';
                                    $is_password = $table === 'customer' && $field === 'customer_pass';
                                    if ($is_password && $action === 'edit') {
                                        $input_value = '';
                                    }
                                    $is_readonly = $action === 'edit' && $is_primary;
                                    $input_type = preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double)/', $type) ? 'number' : 'text';
                                    $step = preg_match('/^(decimal|float|double)/', $type) ? 'any' : '1';
                                    ?>
                                    <div class="form-group">
                                        <label for="field-<?php echo admin_escape($field); ?>"><?php echo admin_escape(ucwords(str_replace('_', ' ', $field))); ?><?php if ($column['Null'] === 'NO' && $column['Default'] === null && !$is_primary) { ?> <span class="text-danger">*</span><?php } ?></label>
                                        <?php if ($is_password) { ?>
                                            <input class="form-control" type="password" id="field-<?php echo admin_escape($field); ?>" name="<?php echo admin_escape($field); ?>" <?php echo $action === 'create' ? 'required' : ''; ?> autocomplete="new-password">
                                            <small class="field-help"><?php echo $action === 'edit' ? 'Kosongkan jika tidak ingin mengganti password. Nilai lama tidak ditampilkan.' : 'Password ini digunakan untuk login customer.'; ?></small>
                                        <?php } elseif (strpos($type, 'text') !== false || strpos($type, 'blob') !== false) { ?>
                                            <textarea class="form-control" id="field-<?php echo admin_escape($field); ?>" name="<?php echo admin_escape($field); ?>" rows="3" <?php echo $is_readonly ? 'readonly' : ''; ?> <?php echo $column['Null'] === 'NO' && $column['Default'] === null && !$is_primary ? 'required' : ''; ?>><?php echo admin_escape($input_value); ?></textarea>
                                        <?php } else { ?>
                                            <input class="form-control" type="<?php echo $input_type; ?>" step="<?php echo $step; ?>" id="field-<?php echo admin_escape($field); ?>" name="<?php echo admin_escape($field); ?>" value="<?php echo admin_escape($input_value); ?>" <?php echo $is_readonly ? 'readonly' : ''; ?> <?php echo $column['Null'] === 'NO' && $column['Default'] === null && !$is_primary && !$is_generated ? 'required' : ''; ?>>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                                <button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Simpan</button>
                                <a class="btn btn-light" href="index.php?table=<?php echo urlencode($table); ?>">Batal</a>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            <?php } else { ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <h1 class="page-title mb-2"><?php echo admin_escape(ucwords(str_replace('_', ' ', $table))); ?></h1>
                    <a class="btn btn-primary mb-2" href="index.php?table=<?php echo urlencode($table); ?>&action=create"><i class="fas fa-plus mr-1"></i>Tambah data</a>
                </div>
                <div class="card content-card">
                    <div class="card-header">Data tabel <code><?php echo admin_escape($table); ?></code></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead><tr>
                                    <?php foreach ($columns as $column) { if ($table === 'customer' && $column['Field'] === 'customer_pass') { continue; } ?>
                                        <th><?php echo admin_escape($column['Field']); ?></th>
                                    <?php } ?>
                                    <th>Aksi</th>
                                </tr></thead>
                                <tbody>
                                    <?php if ($list_result && mysqli_num_rows($list_result) > 0) { while ($row = mysqli_fetch_assoc($list_result)) { ?>
                                        <tr>
                                            <?php foreach ($columns as $column) {
                                                $field = $column['Field'];
                                                if ($table === 'customer' && $field === 'customer_pass') { continue; }
                                                $cell = isset($row[$field]) ? $row[$field] : '';
                                                if (strlen((string) $cell) > 120) {
                                                    $cell = substr((string) $cell, 0, 120) . '…';
                                                }
                                                ?>
                                                <td><?php echo admin_escape($cell); ?></td>
                                            <?php } ?>
                                            <td class="action-buttons">
                                                <?php if ($primary_key) { ?>
                                                    <a class="btn btn-sm btn-outline-primary" title="Edit" href="index.php?table=<?php echo urlencode($table); ?>&action=edit&id=<?php echo urlencode((string) $row[$primary_key]); ?>"><i class="fas fa-edit"></i></a>
                                                    <form class="d-inline" method="post" action="index.php?table=<?php echo urlencode($table); ?>" onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo admin_escape(admin_csrf_token()); ?>">
                                                        <input type="hidden" name="record_action" value="delete">
                                                        <input type="hidden" name="record_id" value="<?php echo admin_escape($row[$primary_key]); ?>">
                                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                <?php } else { ?><span class="text-muted">Tanpa primary key</span><?php } ?>
                                            </td>
                                        </tr>
                                    <?php } } else { ?>
                                        <tr><td colspan="<?php echo count($columns) + 1; ?>" class="text-center text-muted py-4">Belum ada data.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($list_result && mysqli_num_rows($list_result) === $list_limit) { ?>
                            <a class="btn btn-sm btn-outline-secondary" href="index.php?table=<?php echo urlencode($table); ?>&page=<?php echo $list_page + 1; ?>">Data berikutnya &rarr;</a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
