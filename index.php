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
function getGroup($db, $title_group){
    $query = "
        SELECT
        groups.id
        FROM
        groups
        WHERE groups.title = '".$title_group."'";

    $row = mysqli_query($db, $query);
    if(!$row) push('getGroup(): no records', 'error');
    $row = mysqli_fetch_array($row,MYSQLI_ASSOC);
    return $row['id'];
}
function updateProduct($db, $id_group, $title_product){
    if(!empty($title_product)) {
        $query = "
          UPDATE `products` SET `products`.`group_id` = '$id_group' WHERE `title` = '$title_product';
	    ";
        $result = mysqli_query($db, $query);
        echo $id_group;
    }
}

$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);
$rows = array_map('str_getcsv', file('db.csv'));
foreach ($rows as $key => $row) {
    $id_group = getGroup($db, $row[1]);
    if(!empty($id_group)) updateProduct($db, $id_group, $row[0]);
}
disconnect($db);
?>