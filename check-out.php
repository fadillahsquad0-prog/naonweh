<?php
$active = "Checkout";
require_once('config.php');
include('db.php');
include("functions.php");

if (isset($_GET['cancel_buy_now'])) {
    unset($_SESSION['buy_now']);
    header("Location: shopping-cart.php");
    exit;
}

if (isset($_POST['buy_now'])) {
    if ($_SESSION['customer_email'] == 'unset') {
        header("Location: login.php");
        exit;
    }

    $product_id = isset($_POST['product_id']) ? filter_var($_POST['product_id'], FILTER_VALIDATE_INT) : false;
    $quantity = isset($_POST['product_qty']) ? filter_var($_POST['product_qty'], FILTER_VALIDATE_INT) : false;
    $size = isset($_POST['size']) ? $_POST['size'] : '';
    $allowed_sizes = array('Small', 'Medium', 'Large', 'XL');

    if ($product_id === false || $product_id < 1 || $quantity === false || $quantity < 1 || $quantity > 99 || !in_array($size, $allowed_sizes, true)) {
        $_SESSION['checkout_error'] = 'Pilih ukuran dan jumlah produk yang valid.';
        header("Location: product.php?product_id=" . (int) $product_id);
        exit;
    }

    $_SESSION['buy_now'] = array(
        'product_id' => $product_id,
        'quantity' => $quantity,
        'size' => $size
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals(customerCsrfToken(), $csrf_token)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba lagi.');
    }

    if ($_SESSION['customer_email'] == 'unset') {
        header("Location: login.php");
        exit;
    }

    if (!ensureOrderItemsTable()) {
        $_SESSION['checkout_error'] = 'Sistem pesanan belum siap. Silakan coba lagi.';
        header("Location: check-out.php");
        exit;
    }

    $customer_query = mysqli_prepare($db, "SELECT customer_id FROM customer WHERE customer_email = ?");
    if (!$customer_query) {
        error_log("Unable to prepare customer lookup for order: " . mysqli_error($db));
        $_SESSION['checkout_error'] = 'Pesanan belum dapat diproses. Silakan coba lagi.';
        header("Location: check-out.php");
        exit;
    }

    $customer_email = $_SESSION['customer_email'];
    mysqli_stmt_bind_param($customer_query, "s", $customer_email);
    mysqli_stmt_execute($customer_query);
    mysqli_stmt_bind_result($customer_query, $customer_id);
    $customer_found = mysqli_stmt_fetch($customer_query);
    mysqli_stmt_close($customer_query);

    if (!$customer_found) {
        $_SESSION['checkout_error'] = 'Akun pelanggan tidak ditemukan. Silakan masuk kembali.';
        header("Location: check-out.php");
        exit;
    }

    $order_items = array();
    $placing_buy_now = isset($_POST['buy_now_order']) && isset($_SESSION['buy_now']);
    if ($placing_buy_now) {
        $buy_now_product = getBuyNowProduct();
        if ($buy_now_product) {
            $order_items[] = array(
                'product_id' => (int) $buy_now_product['products_id'],
                'title' => $buy_now_product['product_title'],
                'image' => $buy_now_product['product_img1'],
                'price' => (int) $buy_now_product['product_price'],
                'quantity' => (int) $buy_now_product['quantity'],
                'size' => $buy_now_product['size']
            );
        }
    } else {
        $cart_query = mysqli_prepare($db, 'SELECT p.products_id, p.product_title, p.product_img1, p.product_price, c.qty, c.size FROM cart c INNER JOIN products p ON p.products_id = c.products_id WHERE c.c_id = ?');
        if ($cart_query) {
            $customer_email = $_SESSION['customer_email'];
            mysqli_stmt_bind_param($cart_query, 's', $customer_email);
            mysqli_stmt_execute($cart_query);
            mysqli_stmt_bind_result($cart_query, $cart_product_id, $cart_title, $cart_image, $cart_price, $cart_quantity, $cart_size);
            mysqli_stmt_store_result($cart_query);
            while (mysqli_stmt_fetch($cart_query)) {
                $order_items[] = array(
                    'product_id' => (int) $cart_product_id,
                    'title' => $cart_title,
                    'image' => $cart_image,
                    'price' => (int) $cart_price,
                    'quantity' => (int) $cart_quantity,
                    'size' => $cart_size
                );
            }
            mysqli_stmt_close($cart_query);
        } else {
            error_log('Unable to prepare cart order query: ' . mysqli_error($db));
        }
    }

    if (!$order_items) {
        $_SESSION['checkout_error'] = $placing_buy_now ? 'Produk yang ingin dibeli sudah tidak tersedia.' : 'Keranjang Anda masih kosong.';
        unset($_SESSION['buy_now']);
        header("Location: check-out.php");
        exit;
    }

    $order_quantity = 0;
    $order_price = 0;
    foreach ($order_items as $item) {
        $order_quantity += $item['quantity'];
        $order_price += $item['price'] * $item['quantity'];
    }

    if (!mysqli_begin_transaction($db)) {
        error_log('Unable to start order transaction: ' . mysqli_error($db));
        $_SESSION['checkout_error'] = 'Pesanan belum dapat diproses. Silakan coba lagi.';
        header("Location: check-out.php");
        exit;
    }

    $order_query = mysqli_prepare($db, "INSERT INTO orders (order_qty, order_price, c_id, date) VALUES (?, ?, ?, NOW())");
    $order_saved = false;
    if ($order_query) {
        mysqli_stmt_bind_param($order_query, "iii", $order_quantity, $order_price, $customer_id);
        $order_saved = mysqli_stmt_execute($order_query);
        if (!$order_saved) {
            error_log("Unable to save order: " . mysqli_stmt_error($order_query));
        }
        mysqli_stmt_close($order_query);
    } else {
        error_log("Unable to prepare order insert: " . mysqli_error($db));
    }

    $order_id = $order_saved ? mysqli_insert_id($db) : 0;
    $item_query = $order_saved ? mysqli_prepare($db, 'INSERT INTO order_items (order_id, product_id, product_title, product_image, unit_price, quantity, size) VALUES (?, ?, ?, ?, ?, ?, ?)') : false;
    if (!$item_query) {
        error_log('Unable to prepare order item insert: ' . mysqli_error($db));
        $order_saved = false;
    } else {
        foreach ($order_items as $item) {
            $item_product_id = $item['product_id'];
            $item_title = $item['title'];
            $item_image = $item['image'];
            $item_price = $item['price'];
            $item_quantity = $item['quantity'];
            $item_size = $item['size'];
            mysqli_stmt_bind_param($item_query, 'iissiis', $order_id, $item_product_id, $item_title, $item_image, $item_price, $item_quantity, $item_size);
            if (!mysqli_stmt_execute($item_query)) {
                error_log('Unable to save order item: ' . mysqli_stmt_error($item_query));
                $order_saved = false;
                break;
            }
        }
        mysqli_stmt_close($item_query);
    }

    if ($order_saved && !$placing_buy_now) {
        $clear_cart = mysqli_prepare($db, 'DELETE FROM cart WHERE c_id = ?');
        if (!$clear_cart) {
            error_log('Unable to prepare cart clear after order: ' . mysqli_error($db));
            $order_saved = false;
        } else {
            $customer_email = $_SESSION['customer_email'];
            mysqli_stmt_bind_param($clear_cart, 's', $customer_email);
            $order_saved = mysqli_stmt_execute($clear_cart);
            if (!$order_saved) {
                error_log('Unable to clear cart after order: ' . mysqli_stmt_error($clear_cart));
            }
            mysqli_stmt_close($clear_cart);
        }
    }

    if (!$order_saved) {
        mysqli_rollback($db);
        $_SESSION['checkout_error'] = 'Pesanan belum dapat diproses. Silakan coba lagi.';
        header("Location: check-out.php" . ($placing_buy_now ? '?buy_now=1' : ''));
        exit;
    }

    if (!mysqli_commit($db)) {
        error_log('Unable to commit order transaction: ' . mysqli_error($db));
        mysqli_rollback($db);
        $_SESSION['checkout_error'] = 'Pesanan belum dapat diproses. Silakan coba lagi.';
        header("Location: check-out.php" . ($placing_buy_now ? '?buy_now=1' : ''));
        exit;
    }

    if ($placing_buy_now) {
        unset($_SESSION['buy_now']);
    }
    $_SESSION['order_success'] = 'Pesanan berhasil dibuat. Terima kasih sudah berbelanja.';
    header("Location: account.php?orders");
    exit;
}

$is_buy_now = isset($_GET['buy_now']) && isset($_SESSION['buy_now']);
$buy_now_product = $is_buy_now ? getBuyNowProduct() : null;
include("header.php");
?>


<!-- Breadcrumb Section Begin -->
<div class="breacrumb-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-text product-more">
                    <a href="index.php"><i class="fa fa-home"></i> Home</a>
                    <a href="shop.php">Shop</a>
                    <span>Check Out</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Breadcrumb Section End -->

<!-- Shopping Cart Section Begin -->
<section class="checkout-section spad">
    <div class="container">
        <div class="checkout-form">
            <div class="row">

                <div class="col-lg-6" <?php if (!($_SESSION['customer_email'] == 'unset')) {
                                            echo "style = 'margin: 0 auto'";
                                        } ?>>
                    <?php
                    if (isset($_SESSION['checkout_error'])) {
                        echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['checkout_error'], ENT_QUOTES, 'UTF-8') . "</div>";
                        unset($_SESSION['checkout_error']);
                    }
                    ?>
                    <div class="checkout-content">
                        <a href="shop.php" class="content-btn">Continue Shopping</a>
                        <?php if ($is_buy_now) { ?>
                            <a href="check-out.php?cancel_buy_now=1" class="content-btn">Kembali ke Keranjang</a>
                        <?php } ?>
                    </div>
                    <div class="place-order">
                        <h4>Your Order</h4>
                        <div class="order-total">
                            <ul class="order-table">
                                <li>Products <span>Total</span></li>
                                <?php if ($is_buy_now && $buy_now_product) { ?>
                                    <li>
                                        <?php echo htmlspecialchars($buy_now_product['product_title'], ENT_QUOTES, 'UTF-8'); ?>
                                        x <?php echo $buy_now_product['quantity']; ?>
                                        (<?php echo htmlspecialchars($buy_now_product['size'], ENT_QUOTES, 'UTF-8'); ?>)
                                        <span><?php echo formatRupiah($buy_now_product['subtotal']); ?></span>
                                    </li>
                                <?php } elseif ($is_buy_now) { ?>
                                    <li>Produk tidak tersedia <span>-</span></li>
                                <?php } else {
                                    checkoutProds();
                                } ?>

                                <li class="fw-normal">Subtotal <span><?php
                                    if ($is_buy_now) {
                                        echo $buy_now_product ? formatRupiah($buy_now_product['subtotal']) : formatRupiah(0);
                                    } else {
                                        total_price();
                                    }
                                ?></span></li>
                                <li class="total-price">Total <span><?php
                                    if ($is_buy_now) {
                                        echo $buy_now_product ? formatRupiah($buy_now_product['subtotal']) : formatRupiah(0);
                                    } else {
                                        total_price();
                                    }
                                ?></span></li>
                            </ul>
                            <?php if (!$is_buy_now || $buy_now_product) { ?>
                                <form action="check-out.php<?php echo $is_buy_now ? '?buy_now=1' : ''; ?>" method="post" class="order-btn">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(customerCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if ($is_buy_now) { ?><input type="hidden" name="buy_now_order" value="1"><?php } ?>
                                    <button type="submit" name="place_order" value="1" class="site-btn place-btn">Buat Pesanan</button>
                                </form>
                            <?php } ?>
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