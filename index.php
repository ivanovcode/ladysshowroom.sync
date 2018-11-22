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


$passwords = array ("admin" => "huj2ov4f"); $users = array_keys($passwords);
$validated = (in_array($_SERVER['PHP_AUTH_USER'], $users)) && ($_SERVER['PHP_AUTH_PW'] == $passwords[$_SERVER['PHP_AUTH_USER']]);

if (!$validated) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    die ("Not authorized");
}

push('set quantities method:'.$_SERVER['REQUEST_METHOD'], 'access');
if ($_SERVER['REQUEST_METHOD'] === 'GET') push('GET method access denied', 'error', true);

$POST = file_get_contents('php://input');
if(empty($POST)) push('no data in request', 'error', true);
file_put_contents('data.json', $POST);

$rows = json_decode($POST, true);
if(!isValidJSON($POST) || $rows === null) push('not valid json in request', 'error', true);

$quantities = [];
foreach ($rows as $key => $row) {
    array_push($quantities, array(
        'updated' => 'success',
        'id_product' => $row[0]['id_product'],
        'id_size' => $row[0]['id_size'],
        'id_stock' => $row[0]['id_stock'],
        'amount' => $row[0]['amount'],
        'response' => array('before' => $row[0]['amount'], 'after' => $row[0]['amount'])
    ));
}
unset($rows);

/*$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);
disconnect($db);*/

$response = [];
$response['quantities'] = $quantities;
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>