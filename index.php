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

function getProducts($db){
    $query = "        
        SELECT
        products.id,
        products.title,
        products.article,
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
        ,'\"}]')) as size,
        IF(products.place IS NULL, NULL, CONCAT('[{\"title\":\"Центральный\", \"code\":\"',products.place,'\"}]')) as place,
        IF(products.price IS NULL, NULL, CONCAT('[{\"purchase\":\"',products.price,'\", \"retail\":\"',products.price,'\", \"discount\":\"',products.price,'\"}]')) as price,
        IF(colors.id IS NULL, NULL, CONCAT('[{\"id\":\"',colors.id,'\", \"title\":\"',colors.title,'\", \"hex\":\"',colors.hex,'\"}]')) as color,
        '' as consist,
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
    array_push($sizes[$row['type']], array('id' => $row['id'], 'label' => $row['value']));
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

$response['collection']['sizes'] = $sizes;
$response['collection']['products'] = $products;
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
disconnect($db);
?>