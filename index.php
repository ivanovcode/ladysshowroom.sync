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

function getCertificate($db){
    $query = "
    SELECT
        c.id,
        c.code,
        c.created_at as created,
        c.expiry_at as expiry,
        o.created_at as sales,
        c.deposit,
        IF(c.order_id>0,1,0) AS is_sales,
        CONCAT('продавец ', u.user_name) as note        
        FROM
        certificates as c
        LEFT JOIN orders o on c.order_id = o.id
      left join users u on u.user_id = o.user_id
    ORDER BY  c.created_at desc
    ";
    $rows = mysqli_query($db, $query);
    if(!$rows) push('getCertificate(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}


$response = [];
$response['collection'] = [];
$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);



$rows = getCertificate($db);
$certificates = [];
foreach ($rows as $key => $row) {
    if(!is_array($certificates[$row['id']])) $certificates[$row['id']] = [];
    array_push($certificates[$row['id']], array('id' => $row['id'], 'code' => $row['code'], 'created' => $row['created'], 'expiry' => $row['expiry'], 'sales' => $row['sales'], 'deposit' => $row['deposit'], 'is_sales' => $row['is_sales'], 'note' => $row['note']));
}
unset($rows);




$response['collection']['certificates'] = $certificates;

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
disconnect($db);
?>