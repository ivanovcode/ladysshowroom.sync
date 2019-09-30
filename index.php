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
    echo $query;
    die();
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

$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);
disablePJAllProducts($db);
$rows = getQuantitiesFrom1C();
$products = $rows['products'];
$_results = [];
$showroom_id = '5';
foreach ($products as $product_key => $product) {
echo "id: ".$product['id']." ";  
  $_sizes = [];
    $sizes = $product['sizes'][0]['values'];
    foreach ($sizes as $size_key => $size) {
        $_size = $size['onhand'];
        /* filter showroom */
        $_size = array_filter($_size, function ($var) use ($showroom_id) {
            return ($var['warehouse_id'] == $showroom_id);
        });
        $qty = array_sum(array_column($_size, 'qty'));
        if($qty>0) array_push($_sizes, $size['title']."::".$qty);
    }
    if(!empty($product['id']) && !empty($_sizes)) {
        echo implode ("||", $_sizes)."\n";
        $results = updatePJQuantity($db, $product['id'], implode ("||", $_sizes));
        array_push($_results, $results);
        enablePJProduct($db, $product['id']);
        //print_r($product);

        updatePJPrice($db, $product['id'], $product['price'][0]['oldprice']);
        updatePJTitle($db, $product['id'], $product['title']);
        updatePJOptPrice($db, $product['id'], $product['price'][0]['oldprice']);
    }
    if(!empty($product['article']))  {
        updatePJArticle($db, $product['id'], $product['article']);
    }
    unset($_sizes);
}


/*$message  = 'Результат обновления сайта iampijama.ru с 1С:';
$message .= " \n ";
$message .= '✔ Всего записей: <b>'.array_sum(array_column($_results, 'matched')).'</b>  🔃 Обновлено: <b>'.array_sum(array_column($_results, 'changed')).'</b>  ✖ Ошибки: <b>'.array_sum(array_column($_results, 'warnings')).'</b>';
sendTelegramMessage('-283140968', $message);*/

/*$message = '⚠ <b>Тестирование уведомлений!</b> В синхронизации <i>1С и iampijama.ru</i> обнаружена ошибка.';
sendTelegramMessage('-283140968', $message);*/

disconnect($db);
/*$response = [];l
echo json_encode($response, JSON_UNESCAPED_UNICODE );sd*/



/*$rows = array_map('str_getcsv', file('1C.iampijama.csv'));
foreach ($rows as $key => $row) {
    addSync($db, $row);
}*/

/*$file = fopen("iampijama.csv","w");
$products = $rows['products'];
foreach ($products as $key => $product) {
    $_product = [];
    $_product['id'] = $product['id'];
    $_product['title'] = $product['title'];
    $_product['article'] = $product['article'];
    if($product['brand'][0]['id']=='2') {
        fputcsv($file, $_product);
    }
}
fclose($file);
die();*/
?>
