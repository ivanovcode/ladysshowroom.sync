<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
set_time_limit(0);
ini_set("display_errors",1);
error_reporting(E_ALL);

function push($data, $name, $die=false, $clear=false, $msg=''){
    if ($clear) unlink(dirname(__FILE__).'/'.$name.'.log');
    $fp = fopen(dirname(__FILE__).'/'.$name.'.log', 'a');
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

function clearOrders($db) {
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0;");
    mysqli_query($db, "TRUNCATE table orders;");
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1;");
    mysqli_query($db, "ALTER TABLE orders AUTO_INCREMENT = 100;");

}


function addCertificate($db, $row){
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
        (NULL ,  '" . $row['product_id'] . "',  '" . $row['size_id'] . "',  '" . $row['showroom_id'] . "',  NULL,  '" . $row['amount'] . "')
        ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);
    ";
    mysqli_query($db, $query);
    return mysqli_insert_id($db);
}

function getCertificatesFrom1C(){
    if (!_iscurl()) push('curl is disabled', 'error', true);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://cloud.itone.ru/LADYSSHOWROOM_UNF/hs/atnApi/Certificates",
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

$certificates = getCertificatesFrom1C();
if(empty($certificates)) push('response empty', 'error', true);
clearCertificates($db);

foreach ($certificates as $key => $certificate) {

}

disconnect($db);
/*$response = [];
echo json_encode($response, JSON_UNESCAPED_UNICODE );*/

?>