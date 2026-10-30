<?php
if (!ensureOrderItemsTable()) {
    echo '<div class="alert alert-danger">Riwayat pesanan belum dapat dimuat saat ini.</div>';
    return;
}

$customer_stmt = mysqli_prepare($con, 'SELECT customer_id FROM customer WHERE customer_email = ? LIMIT 1');
if (!$customer_stmt) {
    error_log('Unable to prepare order history customer lookup: ' . mysqli_error($con));
    echo '<div class="alert alert-danger">Riwayat pesanan belum dapat dimuat saat ini.</div>';
    return;
}

$customer_email = $_SESSION['customer_email'];
mysqli_stmt_bind_param($customer_stmt, 's', $customer_email);
mysqli_stmt_execute($customer_stmt);
mysqli_stmt_bind_result($customer_stmt, $customer_id);
$customer_found = mysqli_stmt_fetch($customer_stmt);
mysqli_stmt_close($customer_stmt);

if (!$customer_found) {
    echo '<div class="alert alert-warning">Data akun tidak ditemukan. Silakan masuk kembali.</div>';
    return;
}

$order_stmt = mysqli_prepare($con, 'SELECT order_id, order_qty, order_price, date FROM orders WHERE c_id = ? ORDER BY date DESC');
if (!$order_stmt) {
    error_log('Unable to prepare customer orders query: ' . mysqli_error($con));
    echo '<div class="alert alert-danger">Riwayat pesanan belum dapat dimuat saat ini.</div>';
    return;
}

mysqli_stmt_bind_param($order_stmt, 'i', $customer_id);
mysqli_stmt_execute($order_stmt);
mysqli_stmt_store_result($order_stmt);
mysqli_stmt_bind_result($order_stmt, $order_id, $order_qty, $order_price, $order_date);
$has_orders = false;
?>
<div class="customer-orders">
    <?php while (mysqli_stmt_fetch($order_stmt)) {
        $has_orders = true;
        $items_stmt = mysqli_prepare($con, 'SELECT product_title, product_image, unit_price, quantity, size FROM order_items WHERE order_id = ?');
        if (!$items_stmt) {
            error_log('Unable to prepare order items query: ' . mysqli_error($con));
            continue;
        }
        mysqli_stmt_bind_param($items_stmt, 'i', $order_id);
        mysqli_stmt_execute($items_stmt);
        mysqli_stmt_bind_result($items_stmt, $item_title, $item_image, $item_price, $item_quantity, $item_size);
        ?>
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between">
                <strong>Pesanan #<?php echo (int) $order_id; ?></strong>
                <span class="text-muted"><?php echo htmlspecialchars($order_date, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="card-body">
                <?php
                $has_order_items = false;
                while (mysqli_stmt_fetch($items_stmt)) {
                    $has_order_items = true;
                    $safe_title = htmlspecialchars($item_title, ENT_QUOTES, 'UTF-8');
                    $safe_image = rawurlencode(basename($item_image));
                    ?>
                    <div class="d-flex align-items-center border-bottom py-3">
                        <img src="img/products/<?php echo $safe_image; ?>" alt="<?php echo $safe_title; ?>" style="width:76px;height:76px;object-fit:cover;border-radius:8px;margin-right:16px">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo $safe_title; ?></h6>
                            <small class="text-muted">
                                <?php echo (int) $item_quantity; ?> item
                                <?php if ($item_size !== '') { ?> · Ukuran <?php echo htmlspecialchars($item_size, ENT_QUOTES, 'UTF-8'); ?><?php } ?>
                            </small>
                        </div>
                        <strong><?php echo formatRupiah((int) $item_price * (int) $item_quantity); ?></strong>
                    </div>
                <?php } ?>
                <?php if (!$has_order_items) { ?>
                    <p class="text-muted mb-0 py-2">Rincian produk dan gambar tidak tersedia untuk pesanan lama ini karena data item belum dicatat oleh sistem.</p>
                <?php } ?>
                <?php mysqli_stmt_close($items_stmt); ?>
                <div class="d-flex justify-content-between pt-3">
                    <span><?php echo (int) $order_qty; ?> item</span>
                    <strong>Total: <?php echo formatRupiah($order_price); ?></strong>
                </div>
            </div>
        </section>
    <?php } ?>
</div>
<?php
mysqli_stmt_close($order_stmt);
if (!$has_orders) {
    echo '<div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">Belum ada pesanan.</div></div>';
}
?>