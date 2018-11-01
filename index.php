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
function getProducts($db, $id_category){
    $query = "
        SELECT
        pc.product_id
        FROM
        products_categories as pc
        WHERE pc.category_id = ".$id_category;
    $rows = mysqli_query($db, $query);
    if(!$rows) push('getProducts(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}


$response = [];
$response['collection'] = [];
$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);

$links = array("6"=>"1", "7"=>"2", "8"=>"3", "9"=>"4");


foreach ($links as $id_category => $id_brand) {
    $rows = getProducts($db, $key);
    foreach ($rows as $key => $row) {
        print_r($row);
        mysqli_query($db, "UPDATE `products` SET `brand_id` = '".$id_brand."' WHERE `products`.`id` = " . $row['product_id']);
        mysqli_query($db, "DELETE FROM `products_categories` WHERE `products_categories`.`product_id` = " . $row['product_id'] . " AND `products_categories`.`category_id` = ".$id_category);
    }
}
unset($rows);

$response['collection']['products'] = $products;
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
disconnect($db);
?>