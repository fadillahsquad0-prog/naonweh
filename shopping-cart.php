<?php
$active = "Shopping Cart";
require_once("config.php");
include("db.php");
include("functions.php");

if ($_SESSION['customer_email'] == 'unset') {
    header("Location: login.php");
    exit;
}

$cart_notice = '';
$cart_notice_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_action'])) {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals(customerCsrfToken(), $csrf_token)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba lagi.');
    }

    $product_id = isset($_POST['product_id']) ? filter_var($_POST['product_id'], FILTER_VALIDATE_INT) : false;
    $cart_action = $_POST['cart_action'];
    if ($product_id === false || $product_id < 1) {
        $cart_notice = 'Produk keranjang tidak valid.';
        $cart_notice_type = 'danger';
    } elseif ($cart_action === 'update') {
        $quantity = isset($_POST['quantity']) ? filter_var($_POST['quantity'], FILTER_VALIDATE_INT) : false;
        $size = isset($_POST['size']) ? $_POST['size'] : '';
        $allowed_sizes = array('Small', 'Medium', 'Large', 'XL');
        if ($quantity === false || $quantity < 1 || $quantity > 99 || !in_array($size, $allowed_sizes, true)) {
            $cart_notice = 'Jumlah atau ukuran produk tidak valid.';
            $cart_notice_type = 'danger';
        } else {
            $stmt = mysqli_prepare($db, 'UPDATE cart SET qty = ?, size = ? WHERE products_id = ? AND c_id = ?');
            if (!$stmt) {
                error_log('Unable to prepare cart update: ' . mysqli_error($db));
                $cart_notice = 'Keranjang tidak dapat diperbarui saat ini.';
                $cart_notice_type = 'danger';
            } else {
                $cart_customer_email = $_SESSION['customer_email'];
                mysqli_stmt_bind_param($stmt, 'isis', $quantity, $size, $product_id, $cart_customer_email);
                $updated = mysqli_stmt_execute($stmt);
                if (!$updated) {
                    error_log('Unable to update cart item: ' . mysqli_stmt_error($stmt));
                    $cart_notice = 'Keranjang tidak dapat diperbarui saat ini.';
                    $cart_notice_type = 'danger';
                } elseif (mysqli_stmt_affected_rows($stmt) < 0) {
                    $cart_notice = 'Produk keranjang tidak ditemukan.';
                    $cart_notice_type = 'warning';
                } else {
                    $cart_notice = 'Jumlah dan ukuran produk berhasil diperbarui.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($cart_action === 'delete') {
        $stmt = mysqli_prepare($db, 'DELETE FROM cart WHERE products_id = ? AND c_id = ?');
        if (!$stmt) {
            error_log('Unable to prepare cart item delete: ' . mysqli_error($db));
            $cart_notice = 'Produk tidak dapat dihapus saat ini.';
            $cart_notice_type = 'danger';
        } else {
            $cart_customer_email = $_SESSION['customer_email'];
            mysqli_stmt_bind_param($stmt, 'is', $product_id, $cart_customer_email);
            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                $cart_notice = 'Produk berhasil dihapus dari keranjang.';
            } else {
                error_log('Unable to delete cart item: ' . mysqli_stmt_error($stmt));
                $cart_notice = 'Produk keranjang tidak ditemukan atau gagal dihapus.';
                $cart_notice_type = 'warning';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

include('header.php');
?>

<!-- Breadcrumb Section Begin -->
<div class="breacrumb-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-text product-more">
                    <a href="index.php"><i class="fa fa-home"></i> Home</a>
                    <a href="shop.php">Shop</a>
                    <span>Shopping Cart</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Breadcrumb Section Begin -->

<!-- Shopping Cart Section Begin -->
<section class="shopping-cart spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?php if ($cart_notice !== '') { ?>
                    <div class="alert alert-<?php echo htmlspecialchars($cart_notice_type, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                        <?php echo htmlspecialchars($cart_notice, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php } ?>
                <div class="cart-table" style="min-height: 150px;">
                    <table>
                        <tbody>
                            <?php cart_items(); ?>
                        </tbody>

                    </table>

                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="cart-buttons">

                            <a href="shop.php" class="primary-btn continue-shop">Continue shopping</a>
                        </div>

                    </div>
                    <div class="col-lg-4 offset-lg-4">
                        <div class="proceed-checkout">
                            <ul>
                                <li class="subtotal">Subtotal <span><?php total_price() ?></span></li>
                                <li class="cart-total">Total <span><?php total_price() ?></span></li>
                            </ul>
                            <a href="check-out.php" class="proceed-btn">PROCEED TO CHECK OUT</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Shopping Cart Section End -->

<?php
include('footer.php');
?>


</body>

</html>
