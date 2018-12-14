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
function getSizes($db){
    $query = "
        SELECT
        s.type,
        s.id,
        s.value
        FROM (
              SELECT id, rus value, 'rus' type FROM sizes
              union all
              SELECT id, eur value, 'eur' type FROM sizes
              union all
              SELECT id, usa value, 'usa' type FROM sizes
        ) AS s
        WHERE s.value IS NOT NULL
    ";
    $rows = mysqli_query($db, $query);
    if(!$rows) push('getSizes(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}
function getColors($db){
    $query = "
        SELECT       
        colors.id,
        colors.title,
        colors.hex
        FROM
        colors
    ";
    $rows = mysqli_query($db, $query);
    if(!$rows) push('getColors(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}
function getCategories($db){
    $query = "
        SELECT       
        categories.id,
        categories.title
        FROM
        categories
    ";
    $rows = mysqli_query($db, $query);
    if(!$rows) push('getCategories(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}
function getProducts($db){
    $query = "        
        SELECT
        products.id,
        products.title,
        products.article,
        products.id as sorting,
        '' as barcode,
        products.hide as status,
        IF(brands.id IS NULL, NULL, CONCAT('[{\"id\":\"',brands.id,'\", \"title\":\"',brands.title,'\"}]')) as brand,
        IF(groups.id IS NULL, NULL, CONCAT('[{\"id\":\"',groups.id,'\", \"title\":\"',groups.title,'\"}]')) as `group`,
        CONCAT('[', GROUP_CONCAT(CONCAT('{\"id\":\"',categories.id,'\", \"title\":\"',categories.title,'\"}')), ']') as categories,
        IF(products.type_size_id IS NULL, NULL, CONCAT('[{\"type\":\"',
          CASE products.type_size_id
              WHEN '1' then 'rus'
              WHEN '2' then 'eur'
              WHEN '3' then 'usa'
          END
        ,'\", \"type_id\":\"',products.type_size_id,'\"}]')) as size,
        IF(products.place IS NULL, NULL, CONCAT('[{\"title\":\"Центральный\", \"code\":\"',products.place,'\"}]')) as place,
        IF(products.price IS NULL, NULL, CONCAT('[{\"purchase\":\"',products.price_purchase,'\", \"retail\":\"',products.price,'\", \"discount\":\"',products.price,'\"}]')) as price,
        IF(colors.id IS NULL, NULL, CONCAT('[{\"id\":\"',colors.id,'\", \"title\":\"',colors.title,'\", \"hex\":\"',colors.hex,'\"}]')) as color,
        products.consist as consist,
        products.discription,
        IF(products.thumbnail IS NULL, NULL, CONCAT('[{\"type\":\"primary\", \"url\":\"http://admin.ladysshowroom.ru/uploads/products/',products.thumbnail,'\"}]')) as thumbnails
        FROM
        products
        LEFT JOIN products_categories as pc ON pc.product_id = products.id
        LEFT JOIN categories ON categories.id = pc.category_id
        LEFT JOIN groups ON groups.id = products.group_id
        LEFT JOIN brands ON brands.id = products.brand_id
        LEFT JOIN colors ON colors.id = products.color_id
          WHERE
        products.`delete` = 0
        GROUP BY  products.id
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


$rows = getSizes($db);
$sizes = [];
foreach ($rows as $key => $row) {
    if(!is_array($sizes[$row['type']])) $sizes[$row['type']] = [];
    array_push($sizes[$row['type']], array('id' => $row['id'], 'label' => $row['value'],  'type' => ($row['type']=='rus' ? 1 : ($row['type']=='eur' ? 2 : ($row['type']=='usa' ? 3 : 0))) ));
}
unset($rows);




$rows = getProducts($db);
$products = [];
foreach ($rows as $key => $row) {

    $row['brand'] = json_decode($row['brand'], JSON_UNESCAPED_SLASHES);
    $row['group'] = json_decode($row['group'], JSON_UNESCAPED_SLASHES);
    $row['categories'] = json_decode($row['categories'], JSON_UNESCAPED_SLASHES);
    $row['size'] = json_decode($row['size'], JSON_UNESCAPED_SLASHES);
    $row['place'] = json_decode($row['place'], JSON_UNESCAPED_SLASHES);
    $row['price'] = json_decode($row['price'], JSON_UNESCAPED_SLASHES);
    $row['color'] = json_decode($row['color'], JSON_UNESCAPED_SLASHES);
    $row['thumbnails'] = json_decode($row['thumbnails'], JSON_UNESCAPED_SLASHES);
    $products[md5($row['id'])] = $row;
}
unset($rows);

$rows = getColors($db);
$colors = [];
foreach ($rows as $key => $row) {
    if(!is_array($colors[$row['id']])) $colors[$row['id']] = [];
    array_push($colors[$row['id']], array('id' => $row['id'], 'title' => $row['title'], 'hex' => $row['hex']));
}
unset($rows);

$rows = getCategories($db);
$categories = [];
foreach ($rows as $key => $row) {
    if(!is_array($categories[$row['id']])) $categories[$row['id']] = [];
    array_push($categories[$row['id']], array('id' => $row['id'], 'title' => $row['title']));
}
unset($rows);



$response['collection']['wallets']['1']['id'] = '1';
$response['collection']['wallets']['1']['title'] = 'Сбербанк Михаил';
$response['collection']['wallets']['2']['id'] = '2';
$response['collection']['wallets']['2']['title'] = 'Сбербанк Анастасия';
$response['collection']['wallets']['3']['id'] = '3';
$response['collection']['wallets']['3']['title'] = 'Альфа';
$response['collection']['wallets']['4']['id'] = '4';
$response['collection']['wallets']['4']['title'] = 'PayPal';
$response['collection']['wallets']['5']['id'] = '5';
$response['collection']['wallets']['5']['title'] = 'Сбербанк Анна';
$response['collection']['wallets']['6']['id'] = '6';
$response['collection']['wallets']['6']['title'] = 'Тинькоф';
$response['collection']['wallets']['7']['id'] = '7';
$response['collection']['wallets']['7']['title'] = 'Пронина';
$response['collection']['wallets']['8']['id'] = '8';
$response['collection']['wallets']['8']['title'] = 'Курьеру на руки';

$response['collection']['deliveries']['1']['id'] = '1';
$response['collection']['deliveries']['1']['title'] = 'Курьерская';
$response['collection']['deliveries']['1']['price'] = 350.00;
$response['collection']['deliveries']['2']['id'] = '2';
$response['collection']['deliveries']['2']['title'] = 'Почта России';
$response['collection']['deliveries']['2']['price'] = 300.00;
$response['collection']['deliveries']['3']['id'] = '3';
$response['collection']['deliveries']['3']['title'] = 'СДЭК';
$response['collection']['deliveries']['3']['price'] = 0.00;
$response['collection']['deliveries']['4']['id'] = '4';
$response['collection']['deliveries']['4']['title'] = 'Самовывоз';
$response['collection']['deliveries']['4']['price'] = 0.00;
$response['collection']['deliveries']['5']['id'] = '5';
$response['collection']['deliveries']['5']['title'] = 'EMS';
$response['collection']['deliveries']['5']['price'] = 0.00;


$response['collection']['payments']['1']['id'] = '1';
$response['collection']['payments']['1']['title'] = 'Банковская карта';
$response['collection']['payments']['2']['id'] = '2';
$response['collection']['payments']['2']['title'] = 'Наличные';
$response['collection']['payments']['4']['id'] = '4';
$response['collection']['payments']['4']['title'] = 'Сертификат';
$response['collection']['payments']['6']['id'] = '6';
$response['collection']['payments']['6']['title'] = 'Эквайринг';

$response['collection']['stocks']['5']['id'] = '5';
$response['collection']['stocks']['5']['title'] = 'Центральный';
$response['collection']['categories'] = $categories;
$response['collection']['colors'] = $colors;
$response['collection']['sizes'] = $sizes;
$response['collection']['products'] = $products;
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
disconnect($db);
?>