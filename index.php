<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
set_time_limit(0);
ini_set("display_errors",1);
error_reporting(E_ALL);

function push($data, $name, $die=false, $clear=false, $msg=''){
    if ($clear) unlink($name.'.log');
    $fp = fopen($name.'.log', 'a');
    fwrite($fp, /*date("d.m.y").' '.date("H:i:s").' | '.*/$data . PHP_EOL);
    fclose($fp);
    if ($die) die($msg);
}

function _isCurl(){
    return function_exists('curl_version');
}

function connect($db, $p) {
    $connect = mysqli_connect($p[$db]['host'], $p[$db]['user'], $p[$db]['password']) or push('no connection to the database', 'error', true);
    mysqli_query($connect, "set names utf8");
    mysqli_query($connect, "SET sql_mode = ''");
    return $connect;
}

function disconnect($db){
    mysqli_close($db);
}


function validTxt($value, $target) {
    if(!empty($value[$target])) return true;
    push($target.': not valid   '.'['.(!empty($value['product_id'])?$value['product_id']:'-- no id --').'] '.(!empty($value['product_title'])?$value['product_title']:'-- no title --'), 'validation');
    return false;
}

function validNum($value, $target) {
    if(preg_match("/^[0-9]+$/i", $value[$target])) return true;
    push($target.': not valid   '.'['.(!empty($value['product_id'])?$value['product_id']:'-- no id --').'] '.(!empty($value['product_title'])?$value['product_title']:'-- no title --'), 'validation');
    return false;
}

/*$str = 'проверяемая строка';php in
if (preg_match("#^[aA-zZ0-9\-_]+$#", $value)) {
    echo "Все верно";
} else {
    echo "Есть недопустимые символы";
}*/

function convertSize($size) { /*Преобразование - Модификация*/
    $_size = [];
    $_size['product_id'] = (isset($size['product_id'])?$size['product_id']:'');
    $_size['product_title'] = (isset($size['product_title'])?$size['product_title']:'');
    $_size['size_id'] = (isset($size['id'])?$size['id']:'');
    $_size['onhand'] = $size['onhand'];
    return $_size;
}
function validateSize($size) { /*Валидация*/
    if (
    validNum($size, 'size_id')
    ) return true;
    return false;
}

function convertGroup($group) { /*Преобразование - Модификация*/
    $_group = [];
    $_group['product_id'] = (isset($group['product_id'])?$group['product_id']:'');
    $_group['product_title'] = (isset($group['product_title'])?$group['product_title']:'');
    $_group['group_id'] = (isset($group['id'])?$group['id']:'');
    $_group['group_title'] = (isset($group['title'])?$group['title']:'');
    return $_group;
}
function validateGroup($group) { /*Валидация*/
    if (
    validNum($group, 'group_id') &&
    validTxt($group, 'group_title')
    ) return true;
    return false;
}


function convertQuantity($quantity) { /*Преобразование - Модификация*/
    $_quantity = [];
    $_quantity['product_id'] = (isset($quantity['product_id'])?$quantity['product_id']:'');
    $_quantity['product_title'] = (isset($quantity['product_title'])?$quantity['product_title']:'');
    $_quantity['showroom_id'] = (isset($quantity['warehouse_id'])?$quantity['warehouse_id']:'');
    $_quantity['showroom_title'] = (isset($quantity['warehouse'])?trim(str_replace(array('Шоурум', 'склад'), '', $quantity['warehouse'])):'');
    $_quantity['amount'] = (isset($quantity['qty'])?$quantity['qty']:'');
    return $_quantity;
}
function validateQuantity($quantity) { /*Валидация*/
    if (
        validNum($quantity, 'showroom_id')&&
        validNum($quantity, 'amount')
    ) return true;
    return false;
}

function convertProduct($product) { /*Преобразование - Модификация*/
    $_product = [];
    $_product['product_id'] = (isset($product['id'])?$product['id']:'');
    $_product['product_title'] = (isset($product['title'])?$product['title']:'');
    $_product['product_article'] = (isset($product['article'])?$product['article']:'');
    $_product['color_id'] = (isset($product['color'][0]['id'])?$product['color'][0]['id']:'');
    $_product['product_price'] = (isset($product['price'][0]['retail'])?$product['price'][0]['retail']:'');
    $_product['sizes_type_id'] = (isset($product['sizes'][0]['type_id'])?$product['sizes'][0]['type_id']:'');
    $_product['place_code'] = (isset($product['place'][0]['code'])?$product['place'][0]['code']:'');
    $_product['brand_id'] = (isset($product['brand'][0]['id'])?$product['brand'][0]['id']:'');
    $_product['group'] = (isset($product['group'][0])?$product['group'][0]:'');
    return $_product;
}
function validateProduct($product) { /*Валидация*/
    if (
        validNum($product, 'product_id') &&
        validTxt($product, 'product_title') &&
        validTxt($product, 'product_article') &&
        validNum($product, 'color_id') &&
        validNum($product, 'product_price') &&
        validNum($product, 'sizes_type_id') &&
        validNum($product, 'brand_id')
    ) return true;
    return false;
}
function clearProducts($db) {
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0;");
    mysqli_query($db, "TRUNCATE table products;");
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1;");
}
function clearShowrooms($db) {
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0;");
    mysqli_query($db, "TRUNCATE table showrooms;");
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1;");
}
function clearCatalog($db) {
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0;");
    mysqli_query($db, "TRUNCATE table catalog;");
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1;");
}
function clearGroups($db) {
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0;");
    mysqli_query($db, "TRUNCATE table groups;");
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1;");
}
function clearReserve($db) {
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0;");
    mysqli_query($db, "TRUNCATE table reserve;");
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1;");
}
function clearOrders($db) {
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0;");
    mysqli_query($db, "TRUNCATE table orders;");
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1;");
}
function cancelOrders($db) {
    $query = "
        UPDATE orders o
        SET
        o.status = 6,
        o.discription = CONCAT(o.discription, ' Не оплатили, истек срок ожидания')
        WHERE
        o.created_at <= (CURDATE() - INTERVAL 1 DAY)
        AND (o.payments IS NULL OR o.payments = '[]')
        AND o.discount < 100
    ";
    mysqli_query($db, $query);
}



function addShowroom($db, $showroom){
    $query = "
          INSERT IGNORE INTO `showrooms` (`id`, `title`, `stock`, `general`)
          VALUES (".$showroom['showroom_id'].", '".$showroom['showroom_title']."', NULL, NULL) 
          ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);
    ";
    mysqli_query($db, $query);
    //file_put_contents('queries.json', $query);
    return mysqli_insert_id($db);
}

function addGroup($db, $group){
    $query = "
          INSERT IGNORE INTO `groups` (`id`, `title`) 
          VALUES (".$group['group_id'].", '".$group['group_title']."') 
          ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);
    ";
    mysqli_query($db, $query);
    //file_put_contents('queries.json', $query);
    return mysqli_insert_id($db);
}

function addProduct($db, $product){
    $query = "
          INSERT IGNORE INTO `products` (`id`, `title`, `article`, `color_id`, `price`, `type_size_id`, `thumbnail`, `place`, `delete`, `hide`, `thumbnail_id`, `brand_id`, `discription`, `group_id`, `price_purchase`, `consist`) 
          VALUES (".$product['product_id'].", '".$product['product_title']."', '".$product['product_article']."', '".$product['color_id']."', '".$product['product_price']."', '".$product['sizes_type_id']."', '', '".$product['place_code']."', '0', '0', NULL, '".$product['brand_id']."', '', '".$product['group']['id']."', '".$product['product_price']."', '') 
          ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);
    ";
    mysqli_query($db, $query);
    //file_put_contents('queries.json', $query);
    return mysqli_insert_id($db);
}

function addCatalog($db, $quantity){
    $query = "
        INSERT IGNORE INTO  `catalog`
        (
            `id` ,
            `product_id` ,
            `size_id` ,
            `showroom_id` ,
            `place_id` ,
            `quantity`
        )
        VALUES
        (NULL ,  '" . $quantity['product_id'] . "',  '" . $quantity['size_id'] . "',  '" . $quantity['showroom_id'] . "',  NULL,  '" . $quantity['amount'] . "')
        ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);
    ";
    mysqli_query($db, $query);
    //file_put_contents('queries.json', $query);
    return mysqli_insert_id($db);
}


/*$query = "
        INSERT IGNORE INTO  `catalog`
        (
            `id` ,
            `product_id` ,
            `size_id` ,
            `showroom_id` ,
            `place_id` ,
            `quantity`
        )
        VALUES
        (NULL ,  '" . $product['id'] . "',  '" . $quantity['id'] . "',  '" . $product['stock'] . "',  NULL,  '" . $quantity['onhand'][0]['qty'] . "')
        ON DUPLICATE KEY UPDATE  quantity=" . $quantity['onhand'][0]['qty']
;
$result = mysqli_query($db, $query);
$id = mysqli_insert_id($db);*/


function getProducts($db){
    $query = "
        SELECT
        p.id
        FROM
        products p
        WHERE
        p.delete = 0
    ";
    $rows = mysqli_query($db, $query);
    $results = [];
    while ($row = mysqli_fetch_array($rows, MYSQLI_ASSOC)) {
        array_push($results, $row);
    }
    return $results;
}
function getQuantitiesFrom1C(){
    if (!_iscurl()) push('curl is disabled', 'error', true);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://cloud.itone.ru/LADYSSHOWROOM_UNF/hs/atnApi/ProductInfo",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "UTF-8",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 500,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\"product\": \"\"}",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic " . base64_encode("itone" . ":" . "itone"),
            "cache-control: no-cache",
            "content-type: application/json"
        ),
    ));
    $data = curl_exec($curl); $error = curl_error($curl); curl_close($curl);
    if ($error) push('request failed: '.var_dump($error), 'error', true);
    return json_decode($data, true);
}

$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);

$rows = getQuantitiesFrom1C();
$products =  getProducts($db);

//print_r(array_diff(array_column($rows['products'], 'id'), array_column($products, 'id')));


if(empty($rows)) push('response empty', 'error', true);
clearProducts($db);
clearCatalog($db);
clearShowrooms($db);
clearGroups($db);
cancelOrders($db);
clearOrders($db);
clearReserve($db);
$products = $rows['products'];
$showrooms = [];
foreach ($products as $key => $product) {
    $sizes = $product['sizes'][0]['values'];


    $product = convertProduct($product); if(validateProduct($product)) {
        $group = $product['group'];
        $group['product_id'] = $product['product_id'];
        $group['product_title'] = $product['product_title'];
        $group = convertGroup($group); if(validateGroup($group)) {
            foreach ($sizes as $key => $size) {
                $quantities = $size['onhand'];
                $size['product_id'] = $product['product_id'];
                $size['product_title'] = $product['product_title'];
                $size = convertSize($size); if(validateSize($size)) {
                    foreach ($quantities as $key => $quantity) {
                        $quantity['product_id'] = $product['product_id'];
                        $quantity['product_title'] = $product['product_title'];
                        $quantity = convertQuantity($quantity); if(validateQuantity($quantity)) {
                            $quantity['size_id'] = $size['size_id'];
                            addGroup($db,$group);
                            addShowroom($db,$quantity);
                            addProduct($db, $product);
                            addCatalog($db, $quantity);

                            //$showrooms[$quantity['showroom_id']]=$quantity['showroom_title'];
                        }
                    }
                }
            }
        }
    }
}
print_r($showrooms);
disconnect($db);
$response = [];
/*iconv(mb_detect_encoding($data, mb_detect_order(), true), "UTF-8", $data);*/
echo json_encode($response, JSON_UNESCAPED_UNICODE );

?>