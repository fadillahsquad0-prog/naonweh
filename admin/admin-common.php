<?php
require_once('../config.php');
require_once('../db.php');

if (!$con) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database.');
}

$admin_tables = array(
    'products',
    'category',
    'product_categories',
    'customer',
    'orders',
    'order_items',
    'cart',
    'slider'
);

function admin_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_require_login()
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function admin_csrf_token()
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

function admin_verify_csrf()
{
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals(admin_csrf_token(), $token)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba lagi.');
    }
}

function admin_table_columns($table)
{
    global $con, $admin_tables;

    if (!in_array($table, $admin_tables, true)) {
        return array();
    }

    $result = mysqli_query($con, "SHOW COLUMNS FROM `" . $table . "`");
    if (!$result) {
        error_log('Unable to inspect admin table ' . $table . ': ' . mysqli_error($con));
        return array();
    }

    $columns = array();
    while ($column = mysqli_fetch_assoc($result)) {
        $columns[] = $column;
    }
    return $columns;
}

function admin_primary_key($columns)
{
    foreach ($columns as $column) {
        if ($column['Key'] === 'PRI') {
            return $column['Field'];
        }
    }
    return null;
}

function admin_flash($message, $type)
{
    $_SESSION['admin_flash'] = array('message' => $message, 'type' => $type);
}

function admin_redirect($url)
{
    header('Location: ' . $url);
    exit;
}
