<?php
require_once('admin-common.php');
admin_require_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    $stmt = mysqli_prepare($con, 'SELECT password_hash FROM admin_users WHERE admin_id = ? LIMIT 1');
    if (!$stmt) {
        error_log('Unable to prepare admin password verification: ' . mysqli_error($con));
        $error = 'Password tidak dapat diperbarui saat ini.';
    } else {
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['admin_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $current_hash);
        $admin_found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if (!$admin_found || !password_verify($current_password, $current_hash)) {
            $error = 'Password saat ini tidak sesuai.';
        } elseif (strlen($new_password) < 10) {
            $error = 'Password baru harus minimal 10 karakter.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update = mysqli_prepare($con, 'UPDATE admin_users SET password_hash = ? WHERE admin_id = ?');
            if (!$update) {
                error_log('Unable to prepare admin password update: ' . mysqli_error($con));
                $error = 'Password tidak dapat diperbarui saat ini.';
            } else {
                mysqli_stmt_bind_param($update, 'si', $new_hash, $_SESSION['admin_id']);
                if (mysqli_stmt_execute($update)) {
                    $success = 'Password berhasil diperbarui.';
                } else {
                    error_log('Unable to update admin password: ' . mysqli_stmt_error($update));
                    $error = 'Password tidak dapat diperbarui saat ini.';
                }
                mysqli_stmt_close($update);
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubah Password Admin - Inferno Co.</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: #f3f5f9; }
        .password-card { width: min(480px, calc(100% - 32px)); border: 0; border-radius: 12px; box-shadow: 0 12px 35px #172b4d18; }
        .brand { color: #4e73df; font-weight: 800; }
    </style>
</head>
<body>
    <main class="card password-card">
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 brand mb-2">Ubah password admin</h1>
            <p class="text-muted mb-4">Gunakan password minimal 10 karakter.</p>
            <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo admin_escape($error); ?></div><?php } ?>
            <?php if ($success !== '') { ?><div class="alert alert-success"><?php echo admin_escape($success); ?></div><?php } ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo admin_escape(admin_csrf_token()); ?>">
                <div class="form-group">
                    <label for="current_password">Password saat ini</label>
                    <input class="form-control" type="password" id="current_password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label for="new_password">Password baru</label>
                    <input class="form-control" type="password" id="new_password" name="new_password" minlength="10" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi password baru</label>
                    <input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="10" required autocomplete="new-password">
                </div>
                <button class="btn btn-primary btn-block" type="submit">Simpan password</button>
                <a class="btn btn-light btn-block" href="index.php">Kembali ke dashboard</a>
            </form>
        </div>
    </main>
</body>
</html>
