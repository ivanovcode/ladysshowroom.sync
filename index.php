<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

function _isCurl(){
    return function_exists('curl_version');
}
function isValidJSON($data){
    return true;
}
function push($data, $name, $die=false, $clear=false, $msg=''){
    if ($clear) unlink($name.'.log');
    $fp = fopen($name.'.log', 'a');
    fwrite($fp, date("d.m.y").' '.date("H:i:s").' | '.$data . PHP_EOL);
    fclose($fp);
    if ($die) die($msg);
}

function getTelegram($method, $request) {
    if (!_iscurl()) push('curl is disabled', 'error', true);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c/".$method,
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => ($request),
        CURLOPT_SSL_VERIFYPEER => false
    ));
    $data = curl_exec($curl); $error = curl_error($curl); curl_close($curl);
    if ($error) push('curl request failed: ' . var_dump($error), 'error', true);
    return json_decode($data, true);
}

if ($_GET['auth'] != 'd41d8cd98f00b204e9800998ecf8427e') push('access denied', 'error', true);
$POST = file_get_contents('php://input');
if(empty($POST)) push('no data in request', 'error', true);

$rows = json_decode($POST, true);
if(!isValidJSON($POST) || $rows === null) push('not valid json in request', 'error', true);
if(empty($rows['message']['chat']['id']) || empty($rows['message']['chat']['first_name']) || empty($rows['message']['text'])) push('no require value in request', 'error', true);

$request = [];
$request['chat_id'] = $rows['message']['chat']['id'];
$request['text'] = 'Привет, '.$rows['message']['chat']['first_name'].'!';
$response = getTelegram('sendMessage', $request);

push(json_encode($response, JSON_UNESCAPED_UNICODE), 'access');
file_put_contents('input.json', json_encode($rows['message']));

?>