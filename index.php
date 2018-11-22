<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);


/*parse_str(file_get_contents("php://input"),$data1);
$data2 = (array) json_decode(file_get_contents('php://input'), TRUE);
$data3 = file_get_contents("php://input");
$data4 = json_encode($_POST, JSON_UNESCAPED_UNICODE);*/

function push($data, $name, $die=false, $clear=false, $msg=''){
    if ($clear) unlink($name.'.log');
    $fp = fopen($name.'.log', 'a');
    fwrite($fp, date("d.m.y").' '.date("H:i:s").' | '.$data . PHP_EOL);
    fclose($fp);
    if ($die) die($msg);
}

function isValidJSON($data){
   return true;
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

$POST = file_get_contents('php://input');

if(empty($_POST)) push('no data in request', 'error', true);
if(!isValidJSON($POST)) push('not valid json in request', 'error', true);

file_put_contents('data.json', $POST);

$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);
disconnect($db);

$quantities = [];
array_push($quantities, array('updated' => 'success'));

$response = [];
$response['quantities'] = $quantities;
echo json_encode($response, JSON_UNESCAPED_UNICODE );
?>