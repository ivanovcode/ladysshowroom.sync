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

function getTelegram($chat_id) {
    if (!_iscurl()) push('curl is disabled', 'error', true);

    $proxy = 'de360.nordvpn.com:80';
    $proxyauth = 'development@ivanov.site:ivan0vv0va';

    if($ch = curl_init()) {
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c/sendMessage?chat_id=".$chat_id."&parse_mode=html&text=Hi");
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $data = curl_exec($ch); $error = curl_error($ch);
        curl_close($ch);
    }

    if ($error) push('curl request failed: ' . json_encode($error, JSON_UNESCAPED_UNICODE), 'error', true);
    return json_decode($data, true);

    /*if (!_iscurl()) push('curl is disabled', 'error', true);
    $website="https://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c";
    $params=[
        'chat_id'=>$chat_id,
        'text'=>'hi',
    ];
    $ch = curl_init($website . '/sendMessage');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, fopen('php://stderr', 'w'));

    $data = curl_exec($ch); $info = curl_getinfo($ch); $error = curl_error($ch); curl_close($ch);
    if ($error) push('curl request failed: ' . var_dump($info), 'error', true);
    return json_decode($data, true);*/

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

push(json_encode($request, JSON_UNESCAPED_UNICODE), 'access');

$response = getTelegram($rows['message']['chat']['id']);
file_put_contents('input.json', json_encode($rows['message']));

?>