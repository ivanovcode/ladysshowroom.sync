<?php
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=utf-8');
    error_reporting(0);
    set_time_limit(0);
    ini_set("display_errors",1);
    error_reporting(E_ALL);

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

    /*function dropProducts($db) {
        mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0;");
        mysqli_query($db, "TRUNCATE table products;");
        mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1;");
    }*/
    function getTransfers($db, $cash=0)  {
        $query = "
            SELECT
            tt.id,
            tw2.id_1c_till as till_to,
            tw1.id_1c_till as till_from,
            DATE_FORMAT(tt.created,\"%Y-%m-%dT%T\") as `date`,
            tt.amount as sum,
            tt.dds_id as dds,
            ss.id_1c_staff as staff,
            tt.comment
            FROM
            `1c_trans.tills` tt
            LEFT JOIN `1c_requests.trans` r ON r.id_trans = tt.id
            LEFT JOIN `1c_tills.bot_wallets` tw1 ON tw1.id_bot_wallet = tt.till_from
            LEFT JOIN `1c_tills.bot_wallets` tw2 ON tw2.id_bot_wallet = tt.till_to
            LEFT JOIN `1c_staffs.staffs` ss ON ss.id_staff = tt.staff_id
            LEFT JOIN wallets w ON w.id = tw1.id_bot_wallet
            WHERE tt.full > 0 AND w.cash = ".strval($cash)." AND tt.amount <= w.balance
            AND r.id_trans IS NULL
            ORDER BY tt.created ASC
        ";
        $rows = mysqli_query($db, $query);
        $results = [];
        while ($row = mysqli_fetch_array($rows, MYSQLI_ASSOC)) {
            array_push($results, $row);
        }
        return $results;
    }

    function getFinances($db)  {
        $query = "
            SELECT
            f.id,            
            ty.name as `type`,
            tw.id_1c_till as `till`,
            f.amount as `sum`,
            ss.id_1c_staff as `staff`,
            DATE_FORMAT(f.created,\"%Y-%m-%dT%T\") as `date`,
            e.id as expense,
            REPLACE(f.comment, \"\n\", \" \") as comment,
            d.id as dds
            FROM
            finances f
            LEFT JOIN `wallets` w ON w.id = f.wallet
            LEFT JOIN `staffs` s ON s.id = f.staff_id
            LEFT JOIN `1c_requests.finances` r ON r.id_finance = f.id
            LEFT JOIN `1c_dds.categories` dc ON dc.id_category = f.subcategory
            LEFT JOIN `1c_dds` d ON d.id = dc.id_dds
            LEFT JOIN `1c_expenses.categories` ec ON ec.id_category = f.subcategory
            LEFT JOIN `1c_expenses`e ON e.id = ec.id_expense
            LEFT JOIN `1c_types.categories` tyc ON tyc.id_category = f.subcategory
            LEFT JOIN `1c_types` ty ON ty.id = tyc.id_type
            LEFT JOIN `1c_tills.bot_wallets` tw ON tw.id_bot_wallet = w.id
            LEFT JOIN `1c_tills` t ON t.id = tw.id_1c_till
            LEFT JOIN `1c_staffs.staffs` ss ON ss.id_staff = f.staff_id
            WHERE f.category REGEXP '^[0-9]+$' AND f.full > 0 AND w.cash = 0 AND f.created >= '2019-02-01 00:00:00' AND f.amount <= t.amount 
            AND r.id_request IS NULL
            ORDER BY f.created ASC 
        ";
        $rows = mysqli_query($db, $query);
        $results = [];
        while ($row = mysqli_fetch_array($rows, MYSQLI_ASSOC)) {
            array_push($results, $row);
        }
        return $results;
    }
    class Store {
        var $list;
        var $url = "http://store.ladysshowroom.ru/";
        var $data;
        function set(){
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->url.$this->list.'/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "UTF-8",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                //CURLOPT_POSTFIELDS => "{\"product\": \"\"}",
                CURLOPT_POSTFIELDS => $this->data,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic " . base64_encode("guest" . ":" . "Y1l1I3h7"),
                    "cache-control: no-cache",
                    "content-type: application/text"
                ),
            ));
            $data = curl_exec($curl); $error = curl_error($curl); curl_close($curl);
            return $data;
        }

    }

    function setRequest($db, $id_finance, $id_request, $id_response, $status=0, $message='') {
        $query = "
             INSERT IGNORE INTO `1c_requests.finances` (`id_finance`, `id_request`, `id_response`, `status`, `created`, `message`) VALUES ('".$id_finance."', ".(!empty($id_request)?'\''.$id_request.'\'':'NULL').", ".(!empty($id_response)?'\''.$id_response.'\'':'NULL').", '".$status."', CURRENT_TIME(), ".(!empty($message)?'\''.$message.'\'':'NULL').")
             ON DUPLICATE KEY UPDATE `id_finance`=LAST_INSERT_ID(id_finance), `id_request`=".(!empty($id_request)?'\''.$id_request.'\'':'NULL').", `id_response`=".(!empty($id_response)?'\''.$id_response.'\'':'NULL').", `message`=".(!empty($message)?'\''.$message.'\'':'NULL');

        mysqli_query($db, $query);
        return mysqli_insert_id($db);

    }
    function setRequestTrans($db, $id_finance, $id_request, $id_response, $status=0, $message='') {
        $query = "
                 INSERT IGNORE INTO `1c_requests.trans` (`id_trans`, `id_request`, `id_response`, `status`, `created`, `message`) VALUES ('".$id_finance."', ".(!empty($id_request)?'\''.$id_request.'\'':'NULL').", ".(!empty($id_response)?'\''.$id_response.'\'':'NULL').", '".$status."', CURRENT_TIME(), ".(!empty($message)?'\''.$message.'\'':'NULL').")
                 ON DUPLICATE KEY UPDATE `id_trans`=LAST_INSERT_ID(id_trans), `id_request`=".(!empty($id_request)?'\''.$id_request.'\'':'NULL').", `id_response`=".(!empty($id_response)?'\''.$id_response.'\'':'NULL').", `message`=".(!empty($message)?'\''.$message.'\'':'NULL');

        mysqli_query($db, $query);
        return mysqli_insert_id($db);

    }

    class Get1C {
        var $list;
        var $url = "http://cloud.itone.ru/LADYSSHOWROOM_UNF/hs/atnApi/";
        var $data;
        function getList(){
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->url.$this->list,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "UTF-8",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 500,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "{}",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic " . base64_encode("itone" . ":" . "itone"),
                    "cache-control: no-cache",
                    "content-type: application/json"
                ),
            ));
            $data = curl_exec($curl); $error = curl_error($curl); curl_close($curl);
            return json_decode($data, true);
        }

        function sendItem(){
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->url.$this->list,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "UTF-8",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 500,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                //CURLOPT_POSTFIELDS => "{\"product\": \"\"}",
                CURLOPT_POSTFIELDS => $this->data,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic " . base64_encode("itone" . ":" . "itone"),
                    "cache-control: no-cache",
                    "content-type: application/json"
                ),
            ));
            $data = curl_exec($curl); $error = curl_error($curl); curl_close($curl);
            return $data;
        }

    }
    function setRows($db, $request, $list){
        $request->list = key($list);
        $rows = $request->getList();
        foreach($rows as $key => $row)   {
            setRow($db, current($list), $row);
        }
    }
    function setRow($db, $tbl, $row){
        $query = "
            INSERT IGNORE INTO  `".$tbl."` 
            (
                `id` ,
                `name`
            )
            VALUES
            ('".$row['id']."' ,  '".$row['name']."')
            ON DUPLICATE KEY UPDATE `name` = '".$row['name']."';
        ";
        mysqli_query($db, $query);
        return mysqli_insert_id($db);
    }

    function setTills($db, $request, $list){
        $request->list = key($list);
        $rows = $request->getList();
        foreach($rows as $key => $row)   {
            setTill($db, current($list), $row);
        }
    }
    function setTill($db, $tbl, $row){
        $query = "
                INSERT IGNORE INTO  `".$tbl."` 
                (
                    `id` ,
                    `name` ,
                    `amount`
                )
                VALUES
                ('".$row['id']."' ,  '".$row['name']."',  '".(!empty($row['balance'])?$row['balance']:0)."')
                ON DUPLICATE KEY UPDATE `name` = '".$row['name']."',  `amount` = '".(!empty($row['balance'])?$row['balance']:0)."';
            ";
        mysqli_query($db, $query);
        return mysqli_insert_id($db);
    }

    function setMoneys($db, $request, $list){
        $request->list = key($list);
        $rows = $request->getList();
        foreach($rows as $key => $row)   {
            setMoney($db, current($list), $row);
        }
    }
    function setMoney($db, $tbl, $row){
        $query = "
                    INSERT IGNORE INTO  `".$tbl."` 
                    (
                        `id_staff`, 
                        `took`, 
                        `gave`, 
                        `reported`, 
                        `remained`
                    )
                    VALUES
                    ('".$row['id']."' ,  '".$row['took']."',  '".$row['gave']."',  '".$row['reported']."',  '".$row['remained']."')
                    ON DUPLICATE KEY UPDATE `took` = '".$row['took']."',  `gave` = '".$row['gave']."',  `reported` = '".$row['reported']."',  `remained` = '".$row['remained']."';
                ";
        mysqli_query($db, $query);
        return mysqli_insert_id($db);
    }



    function setNumberFinance($db, $id, $number) {
        $q = "
                UPDATE `finances` f
                SET f.`number` = '".$number."'
                WHERE f.id = ".$id;
        mysqli_query($db, $q);
    }
    function setNumberTrans($db, $id, $number) {
        $q = "
                    UPDATE `1c_trans.tills` t
                    SET t.`number` = '".$number."'
                    WHERE t.id = ".$id;
        mysqli_query($db, $q);
    }

    /*Синхронизация названий мест хранения денег из 1С при наличии их в промежуточной таблице*/
    function updateWallets($db) {
        $q = "
            UPDATE `wallets` w
            LEFT JOIN `1c_tills.bot_wallets` tw ON tw.id_bot_wallet = w.id
            LEFT JOIN `1c_tills` t ON t.id = tw.id_1c_till
            LEFT JOIN `1c_cash.wallets` cw ON cw.id_wallet = w.id
            LEFT JOIN `1c_cash` ca ON ca.id_staff = cw.id_1c_staff
            LEFT JOIN `1c_staffs` st ON st.id = cw.id_1c_staff
            SET w.title = CASE WHEN t.id IS NULL THEN CONCAT('Наличные ', st.firstname) ELSE t.`name` END, w.balance = CASE WHEN t.id IS NULL THEN ca.remained ELSE t.amount END
            WHERE (tw.`update` = 1 OR cw.`update` = 1) AND (ca.remained IS NOT NULL OR t.amount IS NOT NULL)
        ";
        mysqli_query($db, $q);
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

    $config = parse_ini_file('config.ini', true);
    $db =  connect('development', $config); mysqli_select_db($db, $config['development']['dbname']);
    $buh = new Get1C;
    $api = new Get1C;
    $store = new Store;

    $lists = array(array('ExpenseList'=>'1c_expenses'), array('DDSList'=>'1c_dds'));
    foreach($lists as $key => $list) setRows($db, $buh, $list);

    $lists = array(array('TillList'=>'1c_tills'));
    foreach($lists as $key => $list) setTills($db, $buh, $list);

    $lists = array(array('GetMoneyStaff'=>'1c_cash'));
    foreach($lists as $key => $list) setMoneys($db, $buh, $list);

    updateWallets($db);

    /*Перенос средств с безналичных счетов*/
    $transfers = getTransfers($db);
    foreach($transfers as $key => $transfer)   {
        $id = $transfer['id'];
        unset($transfer['id']);
        $api->list = 'MoveMoney';
        $api->data = json_encode($transfer, JSON_UNESCAPED_UNICODE);
        $response = $api->sendItem();
        $response = str_replace(array('\t','\n'),'',$response);

        $store->list = 'rest';
        $store->data = $api->data;
        $request = $store->set();

        /*echo "Запрос:"."\n";
        echo $request;
        echo "\n";
        echo "\n";*/
        $request = json_decode($request, true);

        $store->data = $response;
        $response = $store->set();

        /*echo "Ответ:"."\n";
        echo $response;
        echo "\n";
        echo "\n";*/
        $response = json_decode($response, true);


        $data = json_decode($store->data, true);

        $message = '';
        if(!isset($request['hash_before']) || !isset($response['hash_before'])) $message .= '<br>Ошибка записи лога;';
        if($data === null) $message .= '<br>В ответе нет информации;';

        $status = -1;
        if($data !== null && isset($data['info'])) $status = ($data['info']['state']=='Проведен'?1:$status);
        /*if(isset($request['hash_before']) || isset($response['hash_before']))*/ setRequestTrans($db, $transfers[$key]['id'], $request['hash_before'], $response['hash_before'], $status, $message);
        if ($data !== null && isset($data['info']['id'])) setNumberTrans($db, $id, $data['info']['id']);


    }

    $finances = getFinances($db);
    foreach($finances as $key => $finance)   {
        $id = $finance['id'];
        unset($finance['id']);
        $buh->list = 'CashFlow';
        $buh->data = json_encode($finance, JSON_UNESCAPED_UNICODE);
        $response = $buh->sendItem();
        $response = str_replace(array('\t','\n'),'',$response);





        $store->list = 'rest';

        $store->data = $buh->data;
        $request = $store->set();
        echo "Запрос:"."\n";
        echo $request;
        echo "\n";
        echo "\n";
        $request = json_decode($request, true);
        //print_r($request);

        $store->data = $response;
        $response = $store->set();

        echo "Ответ:"."\n";
        echo $response;
        echo "\n";
        echo "\n";
        $response = json_decode($response, true);



        //print_r($response);

        $data = json_decode($store->data, true);
        $message = '';
        if(!isset($request['hash_before']) || !isset($response['hash_before'])) $message .= '<br>Ошибка записи лога;';
        if(isset($data['msg']) && strpos($data['msg'], 'Не хватает денежных средств')!== false) $message .= '<br>Не хватает денежных средств;';
        if($data === null) $message .= '<br>В ответе нет информации;';

        $status = -1;
        if($data !== null && isset($data['info'])) $status = ($data['info']['state']=='Проведен'?1:$status);
        /*if(isset($request['hash_before']) || isset($response['hash_before']))*/ setRequest($db, $finances[$key]['id'], $request['hash_before'], $response['hash_before'], $status, $message);
        if ($data !== null && isset($data['info']['id'])) setNumberFinance($db, $id, $data['info']['id']);


    }
?>