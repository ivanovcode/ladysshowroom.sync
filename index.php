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
$response['collection']['sizes'] = $sizes;
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
disconnect($db);
?>