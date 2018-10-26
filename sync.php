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

function createGroup($db, $row){
    $result = mysqli_query($db, "
        INSERT INTO groups (id, title) VALUES (NULL, '$row[1]') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);
	");
    return mysqli_insert_id($db);
}
function updateProduct($db, $row, $value){
    $result = mysqli_query($db, "
        UPDATE `products` SET `group_id` = '$value' WHERE `products`.`title` = '$row[0]';
	");
}

$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);
$rows = array_map('str_getcsv', file('db.csv'));
foreach ($rows as $key => $row) {
    print_r($row);
    /*$group_id = createGroup($db, $row);
    updateProduct($db, $row, $group_id);*/
}
disconnect($db);
?>