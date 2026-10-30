<?php
$customer_email = $_SESSION['customer_email'];
$customer_stmt = mysqli_prepare($con, 'SELECT customer_name, customer_email, customer_address, customer_contact, customer_image FROM customer WHERE customer_email = ? LIMIT 1');
if (!$customer_stmt) {
    error_log('Unable to prepare customer details query: ' . mysqli_error($con));
    echo '<div class="alert alert-danger">Detail akun tidak dapat dimuat saat ini.</div>';
    return;
}

mysqli_stmt_bind_param($customer_stmt, 's', $customer_email);
mysqli_stmt_execute($customer_stmt);
mysqli_stmt_bind_result($customer_stmt, $customer_name, $stored_email, $customer_address, $customer_contact, $customer_image);
$customer_found = mysqli_stmt_fetch($customer_stmt);
mysqli_stmt_close($customer_stmt);

if (!$customer_found) {
    echo '<div class="alert alert-warning">Data akun tidak ditemukan. Silakan masuk kembali.</div>';
    return;
}
?>
<form action="account.php?details" method="post" enctype="multipart/form-data" class="card border-0 shadow-sm p-4">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(customerCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="text-center mb-4">
        <img src="img/customer/<?php echo rawurlencode(basename($customer_image)); ?>" alt="Foto profil" class="rounded-circle" style="width:110px;height:110px;object-fit:cover">
    </div>
    <div class="form-group">
        <label for="customer_name">Nama</label>
        <input class="form-control" type="text" id="customer_name" name="customer_name" maxlength="255" value="<?php echo htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    <div class="form-group">
        <label for="customer_email">Email</label>
        <input class="form-control" type="email" id="customer_email" value="<?php echo htmlspecialchars($stored_email, ENT_QUOTES, 'UTF-8'); ?>" readonly>
        <small class="form-text text-muted">Email login tidak bisa diubah dari halaman ini.</small>
    </div>
    <div class="form-group">
        <label for="customer_contact">Nomor telepon</label>
        <input class="form-control" type="text" id="customer_contact" name="customer_contact" maxlength="50" value="<?php echo htmlspecialchars($customer_contact, ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    <div class="form-group">
        <label for="customer_address">Alamat</label>
        <textarea class="form-control" id="customer_address" name="customer_address" rows="3" maxlength="400" required><?php echo htmlspecialchars($customer_address, ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>
    <div class="form-group">
        <label for="customer_image">Foto profil baru (opsional)</label>
        <input class="form-control-file" type="file" id="customer_image" name="customer_image" accept=".jpg,.jpeg,.png,.webp">
        <small class="form-text text-muted">Format JPG, PNG, atau WEBP, maksimal 2 MB.</small>
    </div>
    <div class="form-group">
        <label for="customer_pass">Password baru (opsional)</label>
        <input class="form-control" type="password" id="customer_pass" name="customer_pass" minlength="8" maxlength="50" autocomplete="new-password">
        <small class="form-text text-muted">Kosongkan jika tidak ingin mengganti password.</small>
    </div>
    <button class="btn btn-primary" type="submit" name="save_details" value="1">Simpan perubahan</button>
</form>
