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

function getOrders($db){
    $query = "
        SELECT
        o.id,
        IF(o.number IS NULL,'', o.number) as number,
        o.created_at as created,
        '' as updated,
        o.amount as price,
        o.discount,
        o.amount-(o.amount/100)*o.discount as sum,
        o.amount-(o.amount/100)*o.discount + d.price as total_sum,
        'true' as overwrite,
        0 as is_paid,
        o.discription as comment,
        IF(r.id IS NULL, NULL, CONCAT('[{\"id\":\"',p.id,'\",\"size\": {\"id\": \"',r.size_id,'\", \"type_id\": \"',p.type_size_id,'\"},\"quantity\":\"',r.quantity,'\",\"price\":\"',p.price,'\",\"discount\":\"',IF(r.discount IS NULL,0,r.discount),'\", \"sum\":\"',(p.price*r.quantity)-(((p.price*r.quantity)/100)*IF(r.discount IS NULL,0,r.discount)),'\"}]')) as products,
        IF(o.delivery_id IS NULL, NULL, CONCAT('[{\"id\":\"',d.id,'\",\"title\":\"',d.title,'\",\"address\":\"',o.address,'\",\"price\":\"',d.price,'\"}]')) as delivery,
        IF(o.client_id IS NULL, NULL, CONCAT('[{\"id\":\"',c.id,'\",\"name\":\"',c.name,'\",\"phone\":\"',c.phone,'\",\"email\":\"\",\"overwrite\":\"true\"}]')) as client,
        o.payments,
        cert.id as cert_id,
        cert.code as cert_code,
        cert.deposit as cert_deposit,
        cert.expiry_at as cert_expiry
        FROM
        orders as o
        LEFT JOIN reserve as r ON r.order_id = o.id
        LEFT JOIN products p on p.id = r.product_id
        LEFT JOIN types_deliveries d on d.id = o.delivery_id
        LEFT JOIN clients c on c.id = o.client_id
        LEFT JOIN certificates cert on cert.order_id = o.id
        WHERE (o.request = 0 OR (o.request = 1 AND o.payments != \"\")) AND o.status <> 6
        GROUP BY o.id
    ";
    $rows = mysqli_query($db, $query);
    if(!$rows) push('getOrders(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}

function getColors($db){
    $query = "
        SELECT       
        colors.id,
        colors.title,
        colors.hex
        FROM
        colors
    ";
    $rows = mysqli_query($db, $query);
    if(!$rows) push('getColors(): no records', 'error');
    return mysqli_fetch_all($rows,MYSQLI_ASSOC);
}

function setQuantitiesIn1C($response){
    if (!_iscurl()) push('curl is disabled', 'error', true);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://cloud.itone.ru/LADYSSHOWROOM_UNF/hs/atnApi/Order",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "UTF-8",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 500,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        /*CURLOPT_POSTFIELDS => "[\r\n  {\r\n    \"id\": \"adr 1\",\r\n    \"original-address\": \"".$_POST['original_address']."\"\r\n  }\r\n]",*/
        /*CURLOPT_POSTFIELDS => "{\"product\": \"\"}",*/
        CURLOPT_POSTFIELDS => $response,
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

$config = parse_ini_file('config.ini', true);
$db =  connect('development', $config);
mysqli_select_db($db, $config['development']['dbname']);

$rows = getOrders($db);
$orders = [];

foreach ($rows as $key => $row) {

    $row['price'] = number_format(stripos($row['price'], '.')?strstr( $row['price'], '.', true):$row['price'],2, '.', '');
    $row['sum'] = number_format(stripos($row['sum'], '.')?strstr( $row['sum'], '.', true):$row['sum'],2, '.', '');
    $row['total_sum'] = number_format(stripos($row['total_sum'], '.')?strstr( $row['total_sum'], '.', true):$row['total_sum'],2, '.', '');
    $row['products'] = json_decode($row['products'], JSON_UNESCAPED_SLASHES);
    $row['delivery'] = json_decode($row['delivery'], JSON_UNESCAPED_SLASHES);
    $row['client'] = json_decode($row['client'], JSON_UNESCAPED_SLASHES);
    $row['payments'] = json_decode($row['payments'], JSON_UNESCAPED_SLASHES);
    if($row['products']) {
        foreach ($row['products'] as $id_product => $product) {
            $row['products'][$id_product]['price'] = number_format(stripos($product['price'], '.') ? strstr($product['price'], '.', true) : $product['price'], 2, '.', '');
            $row['products'][$id_product]['sum'] = number_format(stripos($product['sum'], '.') ? strstr($product['sum'], '.', true) : $product['sum'], 2, '.', '');
        }
    } else {
        $row['products'] = [];
    }

    if($row['delivery']) {
        foreach ($row['delivery'] as $id_delivery => $delivery) {
            $row['delivery'][$id_delivery]['price'] = number_format(stripos($delivery['price'], '.') ? strstr($delivery['price'], '.', true) : $delivery['price'], 2, '.', '');
        }
    } else {
        $row['delivery'] = [];
    }

    if($row['payments']) {
        foreach ($row['payments'] as $id_payment => $payment) {
            if($payment['id']==4 || $payment['id']==1) {
                $payment['id'] = 1;
                $payment['title'] = "Банковский перевод";
            }
            if($payment['id']==3) {
                $payment['id'] = 4;
                $payment['title'] = "Сертификат";

            }
            if($payment['id']==2) {
                $payment['id'] = 2;
                $payment['title'] = "Наличные";
            }
            $row['payments'][$id_payment]['id'] = $payment['id'];
            $row['payments'][$id_payment]['title'] = $payment['title'];
            $row['payments'][$id_payment]['amount'] = floatval(number_format(stripos($payment['amount'], '.') ? strstr($payment['amount'], '.', true) : $payment['amount'], 2, '.', ''));
            if(!empty($row['cert_id']) && $payment['id']==4) {
                $certificate = [];
                $certificate["id"] =$row['cert_id'];
                $certificate["code"]= $row['cert_code'];
                $certificate["deposit"]= $row['cert_deposit'];
                $certificate["expiry"]= $row['cert_expiry'];
                $row['payments'][$id_payment]['certificate'] = $certificate;
            }
            if($payment['id']==1) {
                $row['payments'][$id_payment]['wallet_id'] = 1;
            }
        }
    } else {
        $row['payments'] = [];
    }
    unset($row['cert_id']);
    unset($row['cert_code']);
    unset($row['cert_deposit']);
    unset($row['cert_expiry']);
    $orders[$row['id']] = $row;
}
unset($rows);



if($orders) {

    $now = date('Y-m-d H:i:s', mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y")));
    $ids =  implode (", ", array_values(array_column($orders, 'id')));
    $response = [];
    $response['collection']['orders'] = $orders;
    file_put_contents($now.'_request.json', json_encode($response, JSON_UNESCAPED_UNICODE));
    $response = setQuantitiesIn1C(json_encode($response, JSON_UNESCAPED_UNICODE));
    $response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    file_put_contents($now.'_response.json', json_encode($response, JSON_UNESCAPED_UNICODE));
    push('orders send success ids: '.$ids, 'access');
    if(!empty($ids)) mysqli_query($db, "UPDATE orders  SET request = request + 1 WHERE id IN (".$ids.")");
}
disconnect($db);
?>