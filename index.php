<?php
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL ^ E_NOTICE);

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

function clear($db) {
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0;");
    mysqli_query($db, "TRUNCATE table products;");
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1;");
}
function enablePJProduct($db, $id){
    $query = "
        UPDATE modx_site_content modx
        LEFT OUTER JOIN `sync.modx_site_content` sync 
            ON modx.id = sync.id_modx 
        SET modx.`published` = '1'
        WHERE sync.id_1c = ".$id.";
    ";
    mysqli_query($db, $query);
    return true;
}

function disablePJAllProducts($db){
    $query = "
         UPDATE `modx_site_content` SET `published` = '0' WHERE  `modx_site_content`.`parent` IN (11,12,13,14);
    ";
    $result = mysqli_query($db, $query);
    $info = mysqli_info($db);
    preg_match('/^\D+(\d+)\D+(\d+)\D+(\d+)$/',$info,$matches);
    $_matches = [];
    $_matches['matched'] = $matches[1]; $_matches['changed'] = $matches[2]; $_matches['warnings'] = $matches[3];
    return $_matches;
}
function updatePJQuantity($db, $id, $quantities){
    $query = "
        UPDATE modx_site_tmplvar_contentvalues modx
        LEFT OUTER JOIN `sync.modx_site_content` sync 
            ON modx.contentid = sync.id_modx AND modx.tmplvarid = 27
        SET modx.`value` = '".$quantities."'
        WHERE sync.id_1c = ".$id.";
    ";
    $result = mysqli_query($db, $query);
    $info = mysqli_info($db);
    preg_match('/^\D+(\d+)\D+(\d+)\D+(\d+)$/',$info,$matches);
    $_matches = [];
    $_matches['matched'] = $matches[1]; $_matches['changed'] = $matches[2]; $_matches['warnings'] = $matches[3];
    return $_matches;
}

function updatePJTitle($db, $id, $value){
    if(!empty($value)) {
        $query = "
            UPDATE modx_site_content modx
            LEFT OUTER JOIN `sync.modx_site_content` sync 
                ON modx.id = sync.id_modx 
            SET modx.`pagetitle` = '" . $value . "'
            WHERE sync.id_1c = " . $id . ";
        ";
        $result = mysqli_query($db, $query);
        $info = mysqli_info($db);
        preg_match('/^\D+(\d+)\D+(\d+)\D+(\d+)$/', $info, $matches);
        $_matches = [];
        $_matches['matched'] = $matches[1];
        $_matches['changed'] = $matches[2];
        $_matches['warnings'] = $matches[3];
        return $_matches;
    }
}

function updatePJPrice($db, $id, $price){
    if(!empty($price)) {
        $query = "
            UPDATE modx_site_tmplvar_contentvalues modx
            LEFT OUTER JOIN `sync.modx_site_content` sync 
                ON modx.contentid = sync.id_modx AND modx.tmplvarid = 9
            SET modx.`value` = '" . $price . "'
            WHERE sync.id_1c = " . $id . ";
        ";
        $result = mysqli_query($db, $query);
        $info = mysqli_info($db);
        preg_match('/^\D+(\d+)\D+(\d+)\D+(\d+)$/', $info, $matches);
        $_matches = [];
        $_matches['matched'] = $matches[1];
        $_matches['changed'] = $matches[2];
        $_matches['warnings'] = $matches[3];
        return $_matches;
    }
}

function updatePJOptPrice($db, $id, $price){
    if(!empty($price)) {
        $query = "
            UPDATE modx_site_tmplvar_contentvalues modx
            LEFT OUTER JOIN `sync.modx_site_content` sync 
                ON modx.contentid = sync.id_modx AND modx.tmplvarid = 10
            SET modx.`value` = '" . $price . "'
            WHERE sync.id_1c = " . $id . ";
        ";
        $result = mysqli_query($db, $query);
        $info = mysqli_info($db);
        preg_match('/^\D+(\d+)\D+(\d+)\D+(\d+)$/', $info, $matches);
        $_matches = [];
        $_matches['matched'] = $matches[1];
        $_matches['changed'] = $matches[2];
        $_matches['warnings'] = $matches[3];
        return $_matches;
    }
}

function updatePJArticle($db, $id, $article){
    $query = "
        UPDATE modx_site_tmplvar_contentvalues modx
        LEFT OUTER JOIN `sync.modx_site_content` sync 
            ON modx.contentid = sync.id_modx AND modx.tmplvarid = 15
        SET modx.`value` = '".$article."'
        WHERE sync.id_1c = ".$id.";
    ";
    $result = mysqli_query($db, $query);
    $info = mysqli_info($db);
    preg_match('/^\D+(\d+)\D+(\d+)\D+(\d+)$/',$info,$matches);
    $_matches = [];
    $_matches['matched'] = $matches[1]; $_matches['changed'] = $matches[2]; $_matches['warnings'] = $matches[3];
    return $_matches;
}
function addSync($db, $row){
    $query = "
         INSERT IGNORE INTO `sync.modx_site_content` (`id_1C`, `id_modx`) VALUES ('".$row[0]."', '".$row[1]."');
    ";
    mysqli_query($db, $query);
    return mysqli_insert_id($db);
}
function sendTelegramMessage($chat_id=NULL, $message=NULL) {
    if (!empty($chat_id) && !empty($message)) {
        $response = [];
        $response['chat_id'] = $chat_id;
        $response['parse_mode'] = 'html';
        $response['text'] = $message;
    }
    if (!_iscurl()) push('curl is disabled', 'error', true);
    $proxy = 'de360.nordvpn.com:80';
    $proxyauth = 'development@ivanov.site:ivan0vv0va';
    $fp = fopen('./curl.log', 'w');
    $ch = curl_init('https://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c/sendMessage');
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($response?($response):($GLOBALS['response'])));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_STDERR, $fp);
    $data = curl_exec($ch); $error = curl_error($ch); curl_close($ch);
    if ($error) push('curl request failed: ' . $error, 'error');
    unset($GLOBALS['response']);
    return json_decode($data, true);
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


function readList(){
    $query = "
        SELECT
        content.pagetitle,
        sync.id_1C,
        sync.id_modx
        FROM `sync.modx_site_content` as sync
        LEFT JOIN modx_site_content as content ON content.id = sync.id_modx
    ";
    $rows = mysqli_query($GLOBALS['db'], $query);
    if(!$rows) push('readList(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}

$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);

$list = readList();
$ip = [];
foreach ($list as $key => $item) {
    $ip[$item['id_1C']] = [];
    $ip[$item['id_1C']]['id'] = $item['id_1C'];
    $ip[$item['id_1C']]['title'] = $item['pagetitle'];
    $ip[$item['id_1C']]['modx'] = $item['id_modx'];
}


$rows = getQuantitiesFrom1C();
$list = $rows['products'];
$c1 = [];
foreach ($list as $key => $item) {
    $c1[$item['id']] = [];
    $c1[$item['id']]['id'] = $item['id'];
    $c1[$item['id']]['title'] = $item['title'];
}


$i=0;
echo "
<!DOCTYPE html>
<html>

<head>
    <title>Список товаров Сайта и 1С</title>
</head>

<body>";

echo "<table>";
echo "<tr style='background:lightgray;font-weight: bold'>";
echo "<td colspan='2'>1C</td>";
echo "<td colspan='2'>Таблица соответствия</td>";
echo "<td colspan='1'>Сайт</td>";
echo "</tr>";

    echo "<tr style='background:lightgray;font-weight: bold'>";
    echo "<td>№</td>";
    echo "<td>Название</td>";
    echo "<td>ID из 1C</td>";
    echo "<td>ID с Сайта</td>";
    echo "<td>Название товара на Сайте</td>";
    echo "</tr>";
foreach ($c1 as $key => $row) {
    echo "<tr style='".(!empty($ip[$row['id']]['modx']) && !empty($ip[$row['id']]['title'])?"background:lightgreen;":(!empty($ip[$row['id']]['modx'])?"background:lightred;":""))."'>";
    $i++;
    echo "<td>".$i."</td>";
    echo "<td>".$row['title']."</td>";
    echo "<td>".$row['id']."</td>";
    echo "<td>".$ip[$row['id']]['modx']."</td>";
    echo "<td>".$ip[$row['id']]['title']."</td>";
    echo "</tr>";
}
echo "</table>";

echo "
</body>

</html>";

disconnect($db);
?>
