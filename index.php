<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

/*$request = [];
$request['chat_id'] = '111';
$request['text'] = 'Привет';



print_r($request);
die();*/

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
    $fp = fopen('./curl.log', 'w');
    $ch = curl_init('https://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c/'.$method);
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($request));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_STDERR, $fp);
    $data = curl_exec($ch); $error = curl_error($ch); curl_close($ch);
    if ($error) push('curl request failed: ' . $error, 'error');
    return json_decode($data, true);
}

if ($_GET['auth'] != 'd41d8cd98f00b204e9800998ecf8427e') push('access denied', 'error', true);
$POST = file_get_contents('php://input');
if(empty($POST)) push('no data in request', 'error', true);
file_put_contents('response.json', $POST);

$rows = json_decode($POST, true);
if(!isValidJSON($POST) || $rows === null) push('not valid json in request', 'error', true);
if(empty($rows['message']['chat']['id']) || empty($rows['message']['chat']['first_name']) || empty($rows['message']['text'])) push('no require value in request', 'error', true);

$request = [];
$request['chat_id'] = $rows['message']['chat']['id'];
$request['parse_mode'] = 'html';
push('chat id: '.$rows['message']['chat']['id'].' command: '.$rows['message']['text'], 'access');
switch ($rows['message']['text']) {
    case '/start':
        $request['text'] = 'Привет, <b>'.$rows['message']['chat']['first_name'].'</b>!';
        $request['text'] .= " \n ";
        $request['text'] .= '<i>Воспользуйтесь командами для управления Финансами</i>';
        $request['reply_markup'] = json_encode(array('inline_keyboard' => array(
            array(
                array('text'=>'✅ Добавить Расход','callback_data'=>'add_decrease'),
                array('text'=>'✅ Удалить Расход','callback_data'=>'del_decrease')
            )
        )));
        break;
    case '/add_decrease':
        $request['text'] = 'Расход добавлен!';
        break;
    case '/del_decrease':
        $request['text'] = 'Расход удален!';
        break;
    default:
}
$response = getTelegram('sendMessage', $request);
/*file_put_contents('response.json', json_encode($response, JSON_UNESCAPED_UNICODE));
file_put_contents('request.json', json_encode($request));*/

?>