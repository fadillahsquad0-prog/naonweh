<?php
require_once('admin-common.php');

$admin_table = mysqli_query($con, "SHOW TABLES LIKE 'admin_users'");
if (!$admin_table || mysqli_num_rows($admin_table) === 0) {
    admin_redirect('setup-admin.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $stmt = mysqli_prepare($con, 'SELECT admin_id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1');
    if (!$stmt) {
        error_log('Unable to prepare admin login: ' . mysqli_error($con));
        $error = 'Login tidak dapat diproses. Silakan coba lagi.';
    } else {
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $admin_id, $admin_username, $password_hash);
        if (mysqli_stmt_fetch($stmt) && password_verify($password, $password_hash)) {
            mysqli_stmt_close($stmt);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['admin_username'] = $admin_username;
            unset($_SESSION['admin_csrf']);
            admin_redirect('index.php');
        }
        mysqli_stmt_close($stmt);
        $error = 'Username atau password salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Inferno Co.</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: #f3f5f9; }
        .login-card { width: min(440px, calc(100% - 32px)); border: 0; border-radius: 12px; box-shadow: 0 12px 35px #172b4d18; }
        .brand { color: #4e73df; font-weight: 800; }
    </style>
</head>
<body>
    <main class="card login-card">
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 brand mb-2">Naon Weh Admin</h1>
            <p class="text-muted mb-4">Masuk untuk mengelola toko.</p>
            <?php if (isset($_GET['created'])) { ?><div class="alert alert-success">Akun admin berhasil dibuat. Silakan masuk.</div><?php } ?>
            <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo admin_escape($error); ?></div><?php } ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo admin_escape(admin_csrf_token()); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input class="form-control" id="username" name="username" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Masuk</button>
            </form>
        </div>
    </main>
</body>
</html>
