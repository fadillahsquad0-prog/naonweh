<?php
$active = "Account";
require_once("config.php");
include("db.php");
include("functions.php");

if ($_SESSION['customer_email'] == 'unset') {
    header("Location: login.php");
    exit;
}

$account_notice = '';
$account_notice_type = 'success';
if (isset($_SESSION['order_success'])) {
    $account_notice = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_details'])) {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals(customerCsrfToken(), $csrf_token)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba lagi.');
    }

    $customer_name = trim(isset($_POST['customer_name']) ? $_POST['customer_name'] : '');
    $customer_contact = trim(isset($_POST['customer_contact']) ? $_POST['customer_contact'] : '');
    $customer_address = trim(isset($_POST['customer_address']) ? $_POST['customer_address'] : '');
    $new_password = isset($_POST['customer_pass']) ? $_POST['customer_pass'] : '';
    $image_name = '';
    $upload_error = '';

    if ($customer_name === '' || strlen($customer_name) > 255 || $customer_contact === '' || strlen($customer_contact) > 50 || $customer_address === '' || strlen($customer_address) > 400) {
        $upload_error = 'Mohon isi nama, nomor telepon, dan alamat dengan benar.';
    } elseif ($new_password !== '' && (strlen($new_password) < 8 || strlen($new_password) > 50)) {
        $upload_error = 'Password baru harus antara 8 sampai 50 karakter.';
    } elseif (isset($_FILES['customer_image']) && $_FILES['customer_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $image = $_FILES['customer_image'];
        if ($image['error'] !== UPLOAD_ERR_OK || $image['size'] > 2 * 1024 * 1024) {
            $upload_error = 'Foto gagal diunggah atau ukurannya melebihi 2 MB.';
        } else {
            $image_info = @getimagesize($image['tmp_name']);
            $image_type = $image_info ? $image_info[2] : 0;
            $image_extensions = array(
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG => 'png',
                IMAGETYPE_WEBP => 'webp'
            );
            if (!isset($image_extensions[$image_type])) {
                $upload_error = 'Foto harus berformat JPG, PNG, atau WEBP.';
            } else {
                $image_name = bin2hex(random_bytes(16)) . '.' . $image_extensions[$image_type];
                $upload_path = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . $image_name;
                if (!move_uploaded_file($image['tmp_name'], $upload_path)) {
                    $upload_error = 'Foto profil tidak dapat disimpan.';
                }
            }
        }
    }

    if ($upload_error !== '') {
        $account_notice = $upload_error;
        $account_notice_type = 'danger';
    } else {
        $sql = 'UPDATE customer SET customer_name = ?, customer_contact = ?, customer_address = ?';
        $types = 'sss';
        $values = array($customer_name, $customer_contact, $customer_address);

        if ($new_password !== '') {
            $sql .= ', customer_pass = ?';
            $types .= 's';
            $values[] = $new_password;
        }
        if ($image_name !== '') {
            $sql .= ', customer_image = ?';
            $types .= 's';
            $values[] = $image_name;
        }
        $sql .= ' WHERE customer_email = ?';
        $types .= 's';
        $values[] = $_SESSION['customer_email'];

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            error_log('Unable to prepare customer profile update: ' . mysqli_error($con));
            $account_notice = 'Perubahan akun tidak dapat disimpan saat ini.';
            $account_notice_type = 'danger';
        } else {
            $bind_values = array(&$stmt, &$types);
            foreach ($values as $index => $value) {
                $bind_values[] = &$values[$index];
            }
            call_user_func_array('mysqli_stmt_bind_param', $bind_values);
            $saved = mysqli_stmt_execute($stmt);
            if (!$saved) {
                error_log('Unable to save customer profile: ' . mysqli_stmt_error($stmt));
                $account_notice = 'Perubahan akun tidak dapat disimpan saat ini.';
                $account_notice_type = 'danger';
            } else {
                $account_notice = 'Detail akun berhasil diperbarui.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

include("header.php");
?>

<!-- Breadcrumb Section Begin -->
<div class="breacrumb-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-text product-more">
                    <a href="index.php"><i class="fa fa-home"></i> Home</a>
                    <span>Account</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- #content Begin -->

<div class="container">
    <div class="insider row">
        <div class="col-md-3 col-8" style="padding: 20px 0;">
            <?php

            include("sidebar.php");

            ?>

        </div>

        <div class="col-md-8 col-10" style="padding:20px 0">
            <?php if ($account_notice !== '') { ?>
                <div class="alert alert-<?php echo htmlspecialchars($account_notice_type, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                    <?php echo htmlspecialchars($account_notice, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php } ?>

            <?php

            if (isset($_GET['orders'])) {
                echo " <h4 class='card' style='text-align: center; margin: 0 0 30px 0;font-weight:600;padding:10px 0 '>My Orders</h4>";

                include("orders.php");
            }

            if (isset($_GET['details'])) {
                echo " <h4 class='card' style='text-align: center; margin: 0 0 30px 0;font-weight:600;padding:10px 0 '>Account Details </h4>";
            
                include("details.php");
            }
            ?>


        </div>
    </div>
</div>






<?php
include('footer.php');
?>

</body>

</html>