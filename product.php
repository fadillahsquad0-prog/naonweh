<?php
$active = "Product";
require_once("config.php");
include("db.php");
include("functions.php");
include('header.php');
?>
<div style="overflow: hidden;">
    <!-- Breadcrumb Section Begin -->
    <div class="breacrumb-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-text product-more">
                        <a href="index.php"><i class="fa fa-home"></i> Home</a>
                        <a href="shop.php">Shop</a>
                        <span>Details</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb Section Begin -->

    <!-- Product Shop Section Begin -->
    <section class="product-shop spad page-details">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="filter-widget">
                        <h4 class="fw-title">Categories</h4>
                        <ul class="filter-catagories">
                            <?php

                            getCat();

                            ?>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="row">
                        <?php

                        getProd();
                        addCart();

                        ?>

                        <?php if (isset($_SESSION['checkout_error'])) { ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($_SESSION['checkout_error'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <?php unset($_SESSION['checkout_error']); ?>
                        <?php } ?>

                        <form action='product.php?add_cart=<?php if (isset($_GET['product_id'])) {
                                                                $product_id = $_GET['product_id'];
                                                                echo $product_id;
                                                            } ?>' method='post'>
                            <input type="hidden" name="product_id" value="<?php echo isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0; ?>">

                            <div class="form-group">
                                <!-- form-group Begin -->
                                <div class='quantity'>
                                    <div class='pro-qty'>
                                        <input type='text' value='1' name="product_qty">
                                    </div>
                                </div>
                            </div><!-- form-group Finish -->

                            <div class="form-group">
                                <!-- form-group Begin -->
                                <div class='pd-size-choose'>
                                    <div class='sc-item'>
                                        <input type='radio' id='sm-size' class="form-control" name='size' value="Small" required novalidate>
                                        <label for='sm-size'>s</label>
                                    </div>
                                    <div class='sc-item'>
                                        <input type='radio' id='md-size' class="form-control" name='size' value="Medium">
                                        <label for='md-size'>m</label>
                                    </div>
                                    <div class='sc-item'>
                                        <input type='radio' id='lg-size' class="form-control" name='size' value="Large">
                                        <label for='lg-size'>l</label>
                                    </div>
                                    <div class='sc-item'>
                                        <input type='radio' id='xl-size' class="form-control" name='size' value="XL">
                                        <label for='xl-size'>xl</label>
                                    </div>
                                </div>
                                <p id="msg"></p>
                            </div><!-- form-group Finish -->
                            <?php if ($_SESSION['customer_email'] == 'unset') { ?>
                                <a href="login.php" class="btn primary-btn pd-cart product-action-btn">Login untuk membeli</a>
                            <?php } else { ?>
                                <div class="product-actions">
                                    <button type="submit" class="btn primary-btn pd-cart product-action-btn" id="cartbtn">Tambah ke Keranjang</button>
                                    <button type="submit" formaction="check-out.php?buy_now=1" class="btn primary-btn buy-now-btn product-action-btn" name="buy_now" value="1">Beli Sekarang</button>
                                </div>
                            <?php }
                            ?>
                        </form>

                    </div>
                </div>
            </div>
        </div>
</div>

</section>


<div class="related-products spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <h2>More like this</h2>
                </div>
            </div>
        </div>

        <div class="row">

            <?php

            relatedProducts();
            ?>

        </div>
    </div>
</div>
</div>

<?php
include('footer.php');
?>

<script>
    $("#cartbtn").on('click', function() {
        var atLeastOneChecked = false;
        if (!$("input[name='size']").is(':checked')) {

            $("#msg").html(
                "<span class='alert alert-danger'>" +
                "Please Choose Size </span>");
        } else {
            return;
        }

    });
</script>

</body>

</html>