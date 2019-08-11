<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
set_time_limit(0);
ini_set("display_errors",1);
error_reporting(E_ALL);

function push($data, $name, $die=false, $clear=false, $msg=''){
    if ($clear) unlink($name.'.log');
    $fp = fopen($name.'.log', 'a');
    fwrite($fp, /*date("d.m.y").' '.date("H:i:s").' | '.*/$data . PHP_EOL);
    fclose($fp);
    if ($die) die($msg);
}

function _isCurl(){
    return function_exists('curl_version');
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

function sendTelegramMessage($chat_id=NULL, $message=NULL) {
    if (!empty($chat_id) && !empty($message)) {
        $response = [];
        $response['chat_id'] = $chat_id;
        $response['parse_mode'] = 'html';
        $response['text'] = $message;
    }
    if (!_iscurl()) push('curl is disabled', 'error', true);
    $proxy = 'de360.nordvpn.com:80';
    $proxyauth = 'development@ivanov.site:ivan0vv0va';
    $fp = fopen('./curl.log', 'w');
    $ch = curl_init('https://api.telegram.org/bot735731689:AAHEZzTKNBUJcURAxOtG6ikj6kNwc7h064c/sendMessage');
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($response?($response):($GLOBALS['response'])));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_STDERR, $fp);
    $data = curl_exec($ch); $error = curl_error($ch); curl_close($ch);
    if ($error) push('curl request failed: ' . $error, 'error');
    unset($GLOBALS['response']);
    return json_decode($data, true);
}

function formatPhoneNumber($phoneNumber) {
    $phoneNumber = preg_replace('/[^0-9]/','',$phoneNumber);

    if(strlen($phoneNumber) > 10) {
        $countryCode = substr($phoneNumber, 0, strlen($phoneNumber)-10);
        $areaCode = substr($phoneNumber, -10, 3);
        $nextThree = substr($phoneNumber, -7, 3);
        $lastFour = substr($phoneNumber, -4, 4);

        $phoneNumber = '+'.$countryCode.' ('.$areaCode.') '.$nextThree.'-'.$lastFour;
    }
    else if(strlen($phoneNumber) == 10) {
        $areaCode = substr($phoneNumber, 0, 3);
        $nextThree = substr($phoneNumber, 3, 3);
        $lastFour = substr($phoneNumber, 6, 4);

        $phoneNumber = '('.$areaCode.') '.$nextThree.'-'.$lastFour;
    }
    else if(strlen($phoneNumber) == 7) {
        $nextThree = substr($phoneNumber, 0, 3);
        $lastFour = substr($phoneNumber, 3, 4);

        $phoneNumber = $nextThree.'-'.$lastFour;
    }

    return $phoneNumber;
}

/**
 * @desc Получение новых заказов из базы ModX за исключением уже тех которые выгрузили
 * @param object $db - подключение к базе данных
 * @return object - массив данных полученных из 1С
 */
function read_shopkeeper(){
    $query = "
        SELECT
        s.*,
        so.number
        FROM
        modx_manager_shopkeeper as s
        LEFT JOIN `sync.orders` so ON so.id_shopkeeper = s.id
        WHERE s.id > 0
        AND so.number IS NULL
        AND s.date > '2019-08-10 17:01:30'
    ";
    $rows = mysqli_query($GLOBALS['db'], $query);
    if(!$rows) push('read_orders(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}

function read_product_sync($id){
    $query = "
        SELECT
        sc.id_1C
        FROM
        `sync.modx_site_content` sc  
        WHERE sc.id_modx = '".$id."'
    ";
    $rows = mysqli_query($GLOBALS['db'], $query);
    if(!$rows) push('read_product_sync(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}

function get_product_id($row){
    $id = read_product_sync($row[0]);
    return $id[0]['id_1C'];
}

function get_order_total_sum($order, $products){
    $order['short_txt'] = unserialize($order['short_txt']);

    $total_sum = 0;
    foreach ($products as $key => $row) {
        $total_sum = $total_sum + ($row['quantity']*$row['price']);
    }
    return $total_sum + intval($order['short_txt']['delivery_cost']);
}

function get_json_product($row){
    $response = [];
    $response['id'] = $row['id'];
    $response['size'] = [];
    $response['size']['id'] = $row['size_id'];
    $response['size']['type_id'] = $row['size_type_id'];
    $response['quantity'] = $row['quantity'];
    $response['price'] = $row['price'];
    $response['discount'] = "0";
    $response['sum'] = intval($row['price'])*intval($row['quantity']);
    return $response;
}

function get_json_products($row){
    $response = [];
    $rows = unserialize($row['content']);


    foreach ($rows as $key => $row) {
        $size = read_size_sync($row['tv_add']['size'])[0];

        array_push($response, get_json_product(array(
            'id'=>get_product_id($row),
            'size_id'=>$size['id'],
            'size_type_id'=>$size['lineid'],
            'quantity'=>$row[1],
            'price'=>$row[2]
        )));
    }
    return $response;
}

function get_json_order($row){
    $response = [];
    $response['discount'] = $row['discount'];
    $response['total_sum'] = $row['total_sum'];
    $response['overwrite'] = "false";
    $response['comment'] = $row['comment'];
    $response['products'] = $row['products'];
    $response['delivery'] = $row['delivery'];
    $response['client'] = $row['client'];
    $response['staff'] = $row['staff'];
    $response['payments']  = $row['payments'];
    $response['warehouse'] = "5";
    $response['roleid'] ="0";
    return $response;
}

function get_json_delivery($row){
    $response = [];
    $response['id'] = "1";
    $response['title'] = "Курьерская";
    $response['address'] = $row['address'];
    $response['price'] = $row['price'];
    $response['datefrom'] = date('Y-m-d H:i:s', mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y")));
    $response['dateto'] = date('Y-m-d 19:00:00', strtotime(date('Y-m-d H:i:s', mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y"))). ' + 1 days'));
    return $response;
}

function get_json_payment($row){
    $response = [];
    $response['id'] = "2";
    $response['amount'] = $row['amount'];
    $response['title'] = "Наличные";
    $response['wallet_id'] = "1";
    return $response;
}

function get_json_payments($row){
    $response = [];
    $products = get_json_products($row);
    $total_sum = get_order_total_sum($row, $products);
    array_push($response, get_json_payment(array(
        'amount'=>$total_sum
    )));
    return $response;
}

function get_json_client($row){
    $response = [];
    $response['name'] = $row['name'];
    $response['phone'] = $row['phone'];
    $response['overwrite'] = "false";
    return $response;
}

function get_json_staff(){
    $response = [];
    $response['id'] = "20";
    $response['name'] = "Иванов Владимир Викторович";
    return $response;
}

function get_json_clients($row){
    $response = [];
    $row['short_txt'] = unserialize($row['short_txt']);
    array_push($response, get_json_client(array(
        'name'=>$row['short_txt']['name'],
        'phone'=>ltrim(preg_replace('/\D/', '', $row['short_txt']['phone']), '7')
    )));
    return $response;
}

function get_json_deliveries($row){
    $response = [];
    $row['short_txt'] = unserialize($row['short_txt']);
    array_push($response, get_json_delivery(array(
        'address'=>$row['short_txt']['city']." ".$row['short_txt']['address'],
        'price'=>$row['short_txt']['delivery_cost']
    )));
    return $response;
}

function get_json_orders($row){
    $response = [];
    $response['orders'] = [];

        $products = get_json_products($row);
        $delivery = get_json_deliveries($row);
        $payments = get_json_payments($row);
        $staff = get_json_staff();
        $total_sum = get_order_total_sum($row, $products);
        $client = get_json_clients($row);
        $response['orders'] = array(""=>get_json_order(array(
            'discount'=>"0",
            'total_sum'=>$total_sum,
            'comment'=>"Тестовый заказ, нужно удалить!",
            'products'=>$products,
            'delivery'=>$delivery,
            'client'=>$client,
            'staff'=>$staff,
            'payments'=>$payments

        )));

    return $response;
}

function get_json_collection($row){
    $response = [];
    $response['collection'] = get_json_orders($row);
    return $response;
}


function create_sizes($rows){
    foreach ($rows as $key => $row) {
        $query = "
            INSERT IGNORE INTO `sync.sizes1C` (`id`, `title`, `lineid`, `line`) VALUES ('".$row['id']."', '".$row['title']."', '".$row['lineid']."', '".$row['line']."') ON DUPLICATE KEY UPDATE `title` = '".$row['title']."', `line` = '".$row['line']."'
        ";
        mysqli_query($GLOBALS['db'], $query);
    }
}


function send_orders() {
    $rows = read_shopkeeper();
    foreach ($rows as $key => $row) {
        $result = send_order(json_encode(get_json_collection($row), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $arr= json_decode($json, true);

        /*echo '\n';
        echo '\n';
        echo 'Исходник: \n';
        $row['short_txt'] = unserialize($row['short_txt']);
        $row['content'] = unserialize($row['content']);
        $row['addit'] = unserialize($row['addit']);
        print_r($row);*/
        $query = "
            INSERT IGNORE INTO `sync.orders` (`id_shopkeeper`, `number`, `last_response`) VALUES ('".$row['id']."', ".(!empty($arr['']['Номер'])?'\''.$arr['']['Номер'].'\'':'NULL').", ".(empty($arr['']['Номер'])?'\''.$json.'\'':'NULL').") ON DUPLICATE KEY UPDATE `number` = ".(!empty($arr['']['Номер'])?'\''.$arr['']['Номер'].'\'':'NULL').", `last_response` = ".(empty($arr['']['Номер'])?'\''.$json.'\'':'NULL')."
        ";
        mysqli_query($GLOBALS['db'], $query);
        echo $query.PHP_EOL;
    }
}


function send_order($json){
    if (!_iscurl()) push('curl is disabled', 'error', true);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $GLOBALS['config']['development']['api']."/hs/atnApi/Order",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "UTF-8",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 500,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $json,
        /*CURLOPT_POSTFIELDS => "{\"product\": \"\"}",*/
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic " . base64_encode("itone" . ":" . "itone"),
            "cache-control: no-cache",
            "content-type: application/json"
        ),
    ));
    $data = curl_exec($curl); $error = curl_error($curl); curl_close($curl);
    if ($error) push('request failed: '.var_dump($error), 'error', true);
    //return $data;
    return json_decode($data, true);
}

function sizes_sync(){
    if (!_iscurl()) push('curl is disabled', 'error', true);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $GLOBALS['config']['development']['api']."/hs/atnApi/Sizes",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic " . base64_encode("itone" . ":" . "itone"),
            "cache-control: no-cache",
            "content-type: application/json"
        ),
    ));
    $data = curl_exec($curl); $error = curl_error($curl); curl_close($curl);
    if ($error) push('request failed: '.var_dump($error), 'error', true);
    return json_decode($data, true);
}

function read_size_sync($title){
    $query = "
        SELECT * FROM `sync.sizes1C` WHERE `title` = '".$title."'
    ";
    $rows = mysqli_query($GLOBALS['db'], $query);
    if(!$rows) push('read_size_sync(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}

$GLOBALS['config'] = parse_ini_file('config.ini', true);
$GLOBALS['db'] =  connect('development', $GLOBALS['config']);
mysqli_select_db($GLOBALS['db'], $GLOBALS['config']['development']['dbname']);

$sizes = sizes_sync();
create_sizes($sizes);
send_orders();

/* Hook*/
/*$result = str_replace('{"collection":{"orders":[', '{"collection":{"orders":{', $result);
$result = str_replace(']}}', '}}}', $result);*/

disconnect($db);

?>
