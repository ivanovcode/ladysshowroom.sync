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

function updateProduct($db, $row){

    $result = mysqli_query($db, "
        UPDATE `products` SET `products`.`type_size_id` = '$row[1]' WHERE `title` = '$row[0]';
	");
}

$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);
$rows = array_map('str_getcsv', file('db.csv'));
foreach ($rows as $key => $row) {
    switch ($row[1]) {
        case "rus":
            $row[1] = "1";
            break;
        case "eur":
            $row[1] = "2";
            break;
        case "usa":
            $row[1] = "3";
            break;
        default:
            $row[1] = "NULL";
    }
    print_r($row);
    updateProduct($db, $row);
}
disconnect($db);
?>