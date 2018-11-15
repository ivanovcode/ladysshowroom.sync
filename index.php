<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
function push($data, $name, $die=false, $clear=false, $msg=''){
    if ($clear) unlink($name.'.log');
    $fp = fopen($name.'.log', 'a');
    fwrite($fp, date("d.m.y").' '.date("H:i:s").' | '.$data . PHP_EOL);
    fclose($fp);
    if ($die) die($msg);
}
function connect($db, $p) {
    $connect = mysqli_connect($p[$db]['host'], $p[$db]['user'], $p[$db]['password']) or push('no connection to the database', 'error');
    mysqli_query($connect, "set names utf8");
    mysqli_query($connect, "SET sql_mode = ''");
    return $connect;
}
function disconnect($db){
    mysqli_close($db);
}
function getProducts($db){
    $query = "
        SELECT
        products.id,
        products.title
        FROM
        products
        LEFT JOIN products_categories as pc ON pc.product_id = products.id
        WHERE pc.category_id = 4 and products.delete = 0
    ";
    $rows = mysqli_query($db, $query);
    if(!$rows) push('getProducts(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}


$response = [];
$response['collection'] = [];
$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);


    $rows = getProducts($db);
    foreach ($rows as $key => $row) {
        print_r($row);
        mysqli_query($db, "UPDATE `products` SET `type_size_id` = '2', `brand_id` = '3' WHERE `products`.`id` = " . $row['id']);
    }
$products = $rows;
unset($rows);

$response['collection']['products'] = $products;
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
disconnect($db);
?>