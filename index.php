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

    $proxy = 'de360.nordvpn.com:80';
    $proxyauth = 'development@ivanov.site:ivan0vv0va';

    push("http://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c/sendMessage?chat_id=".$request['chat_id']."&parse_mode=html&text=Hi", 'access');
    $fp = fopen('./curl.log', 'w');


    /*if($ch = curl_init()) {
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c/".$method."?chat_id=".$request['chat_id']."&parse_mode=html&text=Hi");
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_STDERR, $fp);
        $data = curl_exec($ch); $error = curl_error($ch);
        curl_close($ch);
    }*/

    $website="https://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c";
    $chatId=$request['chat_id'];  //Receiver Chat Id
    $params=[
        'chat_id'=>$chatID,
        'text'=>'hi',
    ];
    $ch = curl_init($website . '/sendMessage');
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);


    /*$params=[
        'chat_id'=>$request['chat_id'],
        'text'=>'hi',
    ];
    $ch = curl_init("https://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c/".$method);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_STDERR, $fp);*/


    /*$ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => "https://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c/".$method,
        CURLOPT_PROXY => $proxy,
        CURLOPT_PROXYUSERPWD => $proxyauth,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "UTF-8",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 500,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\"chat_id\": \"".$request['chat_id']."\",\"text\": \"hi\"}",
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_VERBOSE => 1,
        CURLOPT_STDERR => $fp
    ));*/
    $data = curl_exec($ch); $error = curl_error($ch); curl_close($ch);
    if ($error) push('curl request failed: ' . $error, 'error');
    return json_decode($data, true);
}

$request = [];
$request['chat_id'] = '190049461';

$response = getTelegram('sendMessage', $request);
file_put_contents('response.json', json_encode($rows['message']));
die();

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
file_put_contents('response.json', json_encode($rows['message']));
die();


$response = getTelegram($rows['message']['chat']['id']);
file_put_contents('input.json', json_encode($rows['message']));

?>