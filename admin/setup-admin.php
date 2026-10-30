<?php
require_once('admin-common.php');

mysqli_query($con, "CREATE TABLE IF NOT EXISTS admin_users (
    admin_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$admin_count = mysqli_query($con, "SELECT COUNT(*) AS total FROM admin_users");
$admin_exists = $admin_count && (int) mysqli_fetch_assoc($admin_count)['total'] > 0;

if ($admin_exists) {
    admin_redirect('login.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (strlen($username) < 3 || strlen($username) > 100 || strlen($password) < 10) {
        $error = 'Username minimal 3 karakter dan password minimal 10 karakter.';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($con, 'INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
        if (!$stmt) {
            error_log('Unable to prepare initial admin insert: ' . mysqli_error($con));
            $error = 'Admin tidak dapat dibuat. Periksa log server.';
        } else {
            mysqli_stmt_bind_param($stmt, 'ss', $username, $password_hash);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                admin_redirect('login.php?created=1');
            }
            error_log('Unable to create initial admin: ' . mysqli_stmt_error($stmt));
            $error = 'Admin tidak dapat dibuat. Username mungkin sudah digunakan.';
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buat Admin - Naon Weh.</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: #f3f5f9; }
        .setup-card { width: min(440px, calc(100% - 32px)); border: 0; border-radius: 12px; box-shadow: 0 12px 35px #172b4d18; }
        .brand { color: #4e73df; font-weight: 800; }
    </style>
</head>
<body>
    <main class="card setup-card">
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 brand mb-2">Buat akun admin</h1>
            <p class="text-muted mb-4">Form ini hanya tersedia sebelum admin pertama dibuat.</p>
            <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo admin_escape($error); ?></div><?php } ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo admin_escape(admin_csrf_token()); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input class="form-control" id="username" name="username" minlength="3" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="password">Password (minimal 10 karakter)</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="10" required>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Buat admin</button>
            </form>
        </div>
    </main>
</body>
</html>
