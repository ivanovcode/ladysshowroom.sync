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
    function unicodeString($str, $encoding=null) {
        if (is_null($encoding)) $encoding = ini_get('mbstring.internal_encoding');
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/u', function($match) use ($encoding) {
            return mb_convert_encoding(pack('H*', $match[1]), $encoding, 'UTF-16BE');
        }, $str);
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
        curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");
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
    //$POST = '{"update_id":46165624,"message":{"message_id":206,"from":{"id":190049461,"is_bot":false,"first_name":"\u0412\u043b\u0430\u0434\u0438\u043c\u0438\u0440","username":"seavalley","language_code":"ru"},"chat":{"id":190049461,"first_name":"\u0412\u043b\u0430\u0434\u0438\u043c\u0438\u0440","username":"seavalley","type":"private"},"date":1544279830,"text":"\ud83d\udcb0 \u041a\u0430\u0441\u0441\u0430"}}';
    if(empty($POST)) push('no data in request', 'error', true);


    //json_encode($POST,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
    file_put_contents('response.json', $POST);

    $rows = json_decode($POST, true);

    if(!isValidJSON($POST) || $rows === null) push('not valid json in request', 'error', true);

    if(!empty($rows['message']['chat']['id'])) { $chat_id = $rows['message']['chat']['id']; } else { $chat_id = $rows['callback_query']['message']['chat']['id']; }
    if(!empty($rows['message']['text'])) { $command = $rows['message']['text']; } else { $command = $rows['callback_query']['data']; }
    if(empty($chat_id) || empty($command)) { push('chat id or command undefined', 'error', true); }

    //push('chat_id:'.$chat_id.' command:'.$command, 'access');

    $command = mb_strtolower(preg_replace('/[^\\/a-zA-Zа-яА-Я0-9]/ui', '', $command));

    switch ($command) {
        case '/start':
            $request = [];
            $request['chat_id'] = $chat_id;
            $request['parse_mode'] = 'html';
            $request['text'] = '✌ Привет, '.$rows['message']['chat']['first_name'].'!';
            $request['reply_markup'] = json_encode(array('keyboard' => array(
                array(
                    array('text'=>'💰 Касса','callback_data'=>'finance'),
                    array('text'=>"Send your visit card", 'request_contact'=>true)
                )
            )));
            $response = getTelegram('sendMessage', $request);

            $request = [];
            $request['chat_id'] = $chat_id;
            $request['parse_mode'] = 'html';
            $request['text'] .= 'Сейчас мы находимся в:';
            $request['text'] .= " \n ";
            $request['text'] .= '<i>/ Главное меню /</i>';
            $request['text'] .= " \n ";
            $request['text'] .= " \n ";
            $request['text'] .= '<b>Выбери нужный раздел</b> 👇';
            $response = getTelegram('sendMessage', $request);
            break;
        case 'add_decrease':
            $request['text'] = 'Расход добавлен!';
            $response = getTelegram('sendMessage', $request);
            break;
        case 'del_decrease':
            $request['text'] = 'Расход удален!';
            $response = getTelegram('sendMessage', $request);
        case 'касса':
            $request = [];
            $request['chat_id'] = $chat_id;
            $request['parse_mode'] = 'html';
            $request['text'] .= 'Сейчас мы находимся в:';
            $request['text'] .= " \n ";
            $request['text'] .= '<i>/ Касса /</i>';
            $request['text'] .= " \n ";
            $request['text'] .= " \n ";
            $request['text'] .= '<b>Выбери нужный раздел</b> 👇';
            $request['reply_markup'] = json_encode(array('keyboard' => array(
                array('📗 Расходы'), array('◀ Вернуться')
            )));
            $response = getTelegram('sendMessage', $request);
            break;
        case 'расходы':
            $request = [];
            $request['chat_id'] = $chat_id;
            $request['parse_mode'] = 'html';
            $request['text'] .= 'Сейчас мы находимся в:';
            $request['text'] .= " \n ";
            $request['text'] .= '<i>/ Расходы /</i>';
            $request['text'] .= " \n ";
            $request['text'] .= " \n ";
            $request['text'] .= '<b>Выбери нужный раздел</b> 👇';
            $request['reply_markup'] = json_encode(array('keyboard' => array(
                array('✅ Создать Расход', '❎ Удалить Расход'), array('◀ Вернуться')
            )));
            $response = getTelegram('sendMessage', $request);
            break;
        case 'вернуться':
            $request = [];
            $request['chat_id'] = $chat_id;
            $request['parse_mode'] = 'html';
            $request['text'] = 'Сейчас мы находимся в:';
            $request['text'] .= " \n ";
            $request['text'] .= '<i>/ Главное меню /</i>';
            $request['text'] .= " \n ";
            $request['text'] .= " \n ";
            $request['text'] .= '<b>Выбери нужный раздел</b> 👇';
            $request['reply_markup'] = json_encode(array('keyboard' => array(
                array(
                    array('text'=>'💰 Касса','callback_data'=>'finance')
                )
            )));
            $response = getTelegram('sendMessage', $request);
            break;
        case 'создатьрасход':
            $request = [];
            $request['chat_id'] = $chat_id;
            $request['parse_mode'] = 'html';
            $request['text'] .= 'Сейчас мы находимся в:';
            $request['text'] .= " \n ";
            $request['text'] .= '<i>/ Создание нового Расхода /</i>';
            $request['text'] .= " \n ";
            $request['text'] .= " \n ";
            $request['text'] .= '<b>Расход на какую сумму?</b> 👇';
            $request['reply_markup'] = json_encode(array('keyboard' => array(
                array('▶ Далее [Р2]'), array('◀ Вернуться')
            )));
            $response = getTelegram('sendMessage', $request);
            break;
        case 'далеер2':
            $request = [];
            $request['chat_id'] = $chat_id;
            $request['parse_mode'] = 'html';
            $request['text'] .= 'Сейчас мы находимся в:';
            $request['text'] .= " \n ";
            $request['text'] .= '<i>/ Создание нового Расхода /</i>';
            $request['text'] .= " \n ";
            $request['text'] .= " \n ";
            $request['text'] .= '<b>К какой категории относиться расход?</b> 👇';
            /*$request['reply_markup'] = json_encode(array(
                'inline_keyboard' => array(
                    array('text'=>'A', 'callback_data'=>'A1')
                )
            ));*/
            $response = getTelegram('sendMessage', $request);
            break;
        default:
            break;
    }
?>


