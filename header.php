<?php
require_once('config.php');
include('db.php');
?>


<!DOCTYPE html>
<html lang="en">

<meta charset="UTF-8">
<meta name="description" content="Naon Weh.">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Naon Weh.</title>

<!-- Google Fonts Used -->
<link href="https://fonts.googleapis.com/css?family=Muli:300,400,500,600,700,800,900&display=swap" rel="stylesheet">
<link href='https://fonts.googleapis.com/css?family=Sofia' rel='stylesheet'>

<!-- Tab Icon -->

<link rel="icon" href="img/icon.svg">

<!-- Css Styles -->
<link rel='stylesheet' href='css/bootstrap.min.css' type='text/css'>
<link rel='stylesheet' href='css/font-awesome.min.css' type='text/css'>
<link rel='stylesheet' href='css/themify-icons.css' type='text/css'>
<link rel='stylesheet' href='css/elegant-icons.css' type='text/css'>
<link rel='stylesheet' href='css/owl.carousel.min.css' type='text/css'>
<link rel='stylesheet' href='css/slicknav.min.css' type='text/css'>
<link rel='stylesheet' href='css/style.css' type='text/css'>


</head>

<body>

    <!-- Page Pre Load Section-->

    <div id="preload">
        <div class="load">
        </div>
    </div>

    <!-- Header Section-->

    <header class="header-section">
        <!-- Middle Bar -->

        <div class="container">
            <div class="inner-header">
                <div class="row">
                    <div class="col-md-3 logo">
                        <a href="index.php">
                            <span>Naon Weh.</span>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <form method="post">
                            <div class="input-group">
                                <input type="text" name="search" placeholder="Search our Store" required>
                                <button type="submit" name="submit"><i class="ti-search"></i></button>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-3 text-right">
                        <ul class="nav-right">
                            <li class="user-icon">
                                <a class="login-panel" href="<?php echo $_SESSION['customer_email'] == 'unset' ? 'login.php' : 'account.php?orders'; ?>" aria-label="Profile">
                                    <i class="fa fa-user" aria-hidden="true"></i>
                                </a>
                                <div class="login-hover">
                                    <?php if ($_SESSION['customer_email'] == 'unset') { ?>
                                        <div class="insidelog">
                                            <a href="login.php" class="btn logbtn">Login</a>
                                        </div>
                                        <div class="insidelog">
                                            <span class="small">Belum punya akun? </span><a href="register.php" class="small">Daftar</a>
                                        </div>
                                    <?php } else { ?>
                                        <div class="insidelog">
                                            <a href="account.php?orders" class="btn logbtn">Akun Saya</a>
                                        </div>
                                        <div class="insidelog">
                                            <a href="logout.php" class="small">Keluar</a>
                                        </div>
                                    <?php } ?>
                                </div>
                            </li>
                            <li class="cart-icon">
                                <a href="shopping-cart.php">
                                    <i class="icon_bag_alt"></i>
                                    <span><?php items(); ?></span>
                                </a>
                                <div class="cart-hover">
                                    <div class="select-items">
                                        <table>
                                            <tbody>

                                                <?php cart_icon_prod(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="select-total">
                                        <span>total:</span>
                                        <h5><?php total_price(); ?></h5>
                                    </div>
                                    <div class="select-button">
                                        <a href="shopping-cart.php" class="primary-btn view-card">VIEW ALL ITEMS</a>
                                        <a href="check-out.php" class="primary-btn checkout-btn">CHECK OUT</a>
                                    </div>
                                </div>
                            </li>
                            <li class="cart-price"><?php total_price(); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>


        <!-- Lower Bar -->


        <div class="nav-item">
            <div class="container">
                <div class="nav-depart">
                    <div class="depart-btn">
                        <i class="ti-menu"></i>
                        <span>All Categories</span>
                        <ul class="depart-hover">

                            <?php
                            getProdCat();
                            ?>

                        </ul>
                    </div>
                </div>
                <nav class="nav-menu mobile-menu">
                    <ul>
                        <li class="<?php if ($active == 'Home') echo "active" ?>"><a href="index.php">Home</a></li>
                        <li class="<?php if ($active == 'Shop') echo "active" ?>"><a href="shop.php">Shop</a></li>
                        <li class="<?php if ($active == 'Contact') echo "active" ?>"><a href="contact.php">Contact</a></li>

                    </ul>
                </nav>
                <div id="mobile-menu-wrap"></div>
            </div>
        </div>
    </header>
    <!-- Header End -->


    <?php
    if (isset($_GET['delcart'])) {
        $p_id = filter_var($_GET['delcart'], FILTER_VALIDATE_INT);
        if ($p_id !== false && $p_id > 0 && $_SESSION['customer_email'] !== 'unset') {
            $delete_cart = mysqli_prepare($con, 'DELETE FROM cart WHERE products_id = ? AND c_id = ?');
            if ($delete_cart) {
                $customer_email = $_SESSION['customer_email'];
                mysqli_stmt_bind_param($delete_cart, 'is', $p_id, $customer_email);
                mysqli_stmt_execute($delete_cart);
                mysqli_stmt_close($delete_cart);
            } else {
                error_log('Unable to prepare header cart item deletion: ' . mysqli_error($con));
            }
        }
        echo "<script>window.open('index.php','_self')</script>";
    }


    if (isset($_POST['submit'])) {

        $item = $_POST["search"];

        $get_product = "select * from products where product_title LIKE '%$item%' LIMIT 0,1";

        $run_product = mysqli_query($con, $get_product);

        $count = mysqli_num_rows($run_product);

        if ($count > 0) {

            $row_product = mysqli_fetch_array($run_product);

            $products_id = $row_product['products_id'];



            echo "<script>window.open('product.php?product_id=$products_id','_self')</script>";
        } else {

            echo "
            <script>
                    bootbox.alert({
                        message: 'No product found',
                        backdrop: true
                    });
            </script>";

        }
    }
    ?>