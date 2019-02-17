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

    function getTransfers($db)  {
        $query = "
            SELECT
            tt.id,
            w1.cash as cash_from,
            w2.cash as cash_to,
            IF(w1.cash=0 AND w2.cash=0, NULL, IF(w1.cash=1, 'ОтПодотчетника', 'Подотчетнику')) as type,
            cw.id_1c_staff as accountable,
            IF(w1.cash=0 AND w2.cash=0, tw1.id_1c_till, NULL) as till_from,
            IF(w1.cash=0 AND w2.cash=0, tw2.id_1c_till, NULL) as till_to,
            IF(w1.cash=0 AND w2.cash=0, NULL, IF(w1.cash=1, tw2.id_1c_till, tw1.id_1c_till)) as till,
            DATE_FORMAT(tt.created,\"%Y-%m-%dT%T\") as `date`,
            tt.amount as sum,
            'НФ-000001' as dds,
            '71.01' as expense,
            ss.id_1c_staff as staff,
            tt.comment
            FROM
            `1c_trans.tills` tt
            LEFT JOIN `1c_requests.trans` r ON r.id_trans = tt.id
            LEFT JOIN `1c_staffs.staffs` ss ON ss.id_staff = tt.staff_id
            LEFT JOIN `1c_tills.bot_wallets` tw1 ON tw1.id_bot_wallet = tt.till_from
            LEFT JOIN `1c_tills.bot_wallets` tw2 ON tw2.id_bot_wallet = tt.till_to
            LEFT JOIN wallets w1 ON w1.id = tt.till_from
            LEFT JOIN wallets w2 ON w2.id = tt.till_to
            LEFT JOIN `1c_cash.wallets` cw ON cw.id_wallet = IF(tw2.id_1c_till IS NOT NULL, tt.till_from, tt.till_to)
            WHERE tt.full > 0 AND tt.amount <= w1.balance
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
            w.cash,
            f.id,
            IF(w.cash=1, '1', NULL) as roleid,
            IF(w.cash=1, f.amount, NULL) as expensesum,
            IF(w.cash=1, cwa.id_1c_staff, NULL) as staffid,
            IF(w.cash=1, NULL, ty.name) as `type`,
            IF(w.cash=1, NULL, tw.id_1c_till) as `till`,
            IF(w.cash=1, NULL, f.amount) as `sum`,
            IF(w.cash=1, NULL, ss.id_1c_staff) as `staff`,
            DATE_FORMAT(f.created,\"%Y-%m-%dT%T\") as `date`,
            e.id as expense,
            REPLACE(f.comment, \"\n\", \" \") as comment,
            IF(w.cash=1, NULL, d.id) as dds    
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
            LEFT JOIN `1c_cash.wallets` cwa ON cwa.id_wallet = f.wallet
            WHERE f.category REGEXP '^[0-9]+$' AND f.full > 0 AND f.created >= '2019-02-15T17:32:50' AND f.amount <= w.balance
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
             ON DUPLICATE KEY UPDATE `id_finance`=LAST_INSERT_ID(id_finance), `id_request`=".(!empty($id_request)?'\''.$id_request.'\'':'NULL').", `id_response`=".(!empty($id_response)?'\''.$id_response.'\'':'NULL').", `status`= ".$status." ,`message`=".(!empty($message)?'\''.$message.'\'':'NULL');
        /*echo "\n";
        echo "Insert:";
        echo "\n";
        echo $query;
        echo "\n";*/
        mysqli_query($db, $query);
        return mysqli_insert_id($db);

    }

    function setRequestTrans($db, $id_finance, $id_request, $id_response, $status=0, $message='') {
        $query = "
                 INSERT IGNORE INTO `1c_requests.trans` (`id_trans`, `id_request`, `id_response`, `status`, `created`, `message`) VALUES ('".$id_finance."', ".(!empty($id_request)?'\''.$id_request.'\'':'NULL').", ".(!empty($id_response)?'\''.$id_response.'\'':'NULL').", '".$status."', CURRENT_TIME(), ".(!empty($message)?'\''.$message.'\'':'NULL').")
                 ON DUPLICATE KEY UPDATE `id_trans`=LAST_INSERT_ID(id_trans), `id_request`=".(!empty($id_request)?'\''.$id_request.'\'':'NULL').", `id_response`=".(!empty($id_response)?'\''.$id_response.'\'':'NULL').", `status`= ".$status.", `message`=".(!empty($message)?'\''.$message.'\'':'NULL');

        mysqli_query($db, $query);
        return mysqli_insert_id($db);
    }

    class Get1C {
        var $list;
        //var $url = "http://office.itone.ru/LADYSSHOWROOM_UNF_TEST/hs/atnApi/";
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
            sleep(3);
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

    function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
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

    function runFinance($db, $finance, $api, $store) {
        $id = $finance['id'];
        unset($finance['id']);
        if($finance['cash']) {
            unset($finance['type']);
            unset($finance['till']);
            unset($finance['sum']);
            unset($finance['staff']);
            unset($finance['dds']);
            $api->list = 'AdvanceReport';
        }
        if(!$finance['cash']) {
            unset($finance['roleid']);
            unset($finance['expensesum']);
            unset($finance['staffid']);
            $api->list = 'CashFlow';
        }

        unset($finance['cash']);


        $store->list = 'rest';

        $request = json_encode($finance, JSON_UNESCAPED_UNICODE);
        $api->data = $request;
        $response = str_replace(array('\t','\n'),'', $api->sendItem());

        $store->data = $request;
        $request_log = json_decode($store->set(), true);

        echo "\n";
        echo "Запрос: " . (!empty($request_log['hash_before'])?"http://store.ladysshowroom.ru/rest/?h=" . $request_log['hash_before']:'Нет ответа от Лог-сервера');
        /*echo $request;
        echo "\n";*/

        $store->data = $response;
        $response_log = json_decode($store->set(), true);

        echo "\n";
        echo "Ответ: " . (!empty($response_log['hash_before'])?"http://store.ladysshowroom.ru/rest/?h=" . $response_log['hash_before']:'Нет ответа от Лог-сервера');
        /*echo $response;
        echo "\n";*/

        echo "\n";
        echo "\n";
        echo "---";
        echo "\n";

        setRequest($db, $id, (!empty($request_log['hash_before'])?$request_log['hash_before']:NULL), (!empty($response_log['hash_before'])?$response_log['hash_before']:NULL));

        $msg = [];
        $msg['status'] = 1;
        $msg['error'] = [];
        $msg['info'] = [];
        $msg['warning'] = [];


        if(!isset($request_log['hash_before'])) array_push($msg['warning'], 'Ошибка записи Запроса в логи');
        if(!isset($response_log['hash_before'])) array_push($msg['warning'], 'Ошибка записи Ответа в логи');
        if(isJson($response)) {
            $response = json_decode($response, true);

            if ($response === null) {
                array_push($msg['error'], 'Ответ с 1С равен NULL');
            } else {
                if (isset($response['msg'])) {
                    $response['msg'] = str_replace("'", "", $response['msg']);
                    if (!empty($response['msg'])) array_push($msg['info'], $response['msg']);
                    if (strpos($response['msg'], 'Не хватает денежных средств') !== false) array_push($msg['error'], 'Не хватает денежных средств');
                    if (strpos($response['msg'], 'существует') !== false) array_push($msg['error'], 'Документ уже существует');
                    if (strpos($response['msg'], 'Не проведен') !== false) array_push($msg['error'], 'Не проведен');
                    if (strpos($response['msg'], 'не проведен') !== false) array_push($msg['error'], 'Не проведен');

                }
                if (isset($response['info']['id'])) {
                    setNumberFinance($db, $id, $response['info']['id']);
                }

            }
        } else {
            array_push($msg['error'], $response);
        }
        if (isset($msg['error'])) {
            if (count($msg['error']) > 0) {
                $msg['status'] = -1;
                $msg['error'] = array_unique($msg['error']);
            } else {
                unset($msg['error']);
            }
        } else {
            unset($msg['error']);
        }
        if (isset($msg['info'])) {
            if (count($msg['info']) > 0) {
                $msg['info'] = array_unique($msg['info']);
            } else {
                unset($msg['info']);
            }
        } else {
            unset($msg['info']);
        }

        setRequest($db, $id, (!empty($request_log['hash_before'])?$request_log['hash_before']:NULL), (!empty($response_log['hash_before'])?$response_log['hash_before']:NULL), $msg['status'], json_encode($msg, JSON_UNESCAPED_UNICODE));

    }

    function runTransfer($db, $transfer, $api, $store)   {
        $id = $transfer['id'];
        unset($transfer['id']);
        if ($transfer['cash_to']) {
            unset($transfer['till_to']);
            unset($transfer['till_from']);
            $api->list = 'CashFlow';
        }
        if ($transfer['cash_from']) {
            unset($transfer['till_to']);
            unset($transfer['till_from']);
            $api->list = 'CashReceipt';
        }
        if (!$transfer['cash_to'] && !$transfer['cash_from']) {
            unset($transfer['type']);
            unset($transfer['till']);
            unset($transfer['accountable']);
            unset($transfer['expense']);
            $api->list = 'MoveMoney';
        }
        unset($transfer['cash_to']);
        unset($transfer['cash_from']);
        $store->list = 'rest';

        $request = json_encode($transfer, JSON_UNESCAPED_UNICODE);
        $api->data = $request;
        $response = str_replace(array('\t', '\n'), '', $api->sendItem());

        $store->data = $request;
        $request_log = json_decode($store->set(), true);

        echo "\n";
        echo "Запрос: " . (!empty($request_log['hash_before'])?"http://store.ladysshowroom.ru/rest/?h=" . $request_log['hash_before']:'Нет ответа от Лог-сервера');
        /*echo $request;
        echo "\n";*/

        $store->data = $response;
        $response_log = json_decode($store->set(), true);

        echo "\n";
        echo "Ответ: " . (!empty($response_log['hash_before'])?"http://store.ladysshowroom.ru/rest/?h=" . $response_log['hash_before']:'Нет ответа от Лог-сервера');
        /*echo $response;
        echo "\n";*/

        echo "\n";
        echo "\n";
        echo "---";
        echo "\n";

        setRequestTrans($db, $id, (!empty($request_log['hash_before'])?$request_log['hash_before']:NULL), (!empty($response_log['hash_before'])?$response_log['hash_before']:NULL));

        $msg = [];
        $msg['status'] = 1;
        $msg['error'] = [];
        $msg['info'] = [];
        $msg['warning'] = [];

        if (!isset($request_log['hash_before'])) array_push($msg['warning'], 'Ошибка записи Запроса в логи');
        if (!isset($response_log['hash_before'])) array_push($msg['warning'], 'Ошибка записи Ответа в логи');
        if (isJson($response)) {
            $response = json_decode($response, true);

            if ($response === null) {
                array_push($msg['error'], 'Ответ с 1С равен NULL');
            } else {
                if (isset($response['msg'])) {
                    $response['msg'] = str_replace("'", "", $response['msg']);
                    if (!empty($response['msg'])) array_push($msg['info'], $response['msg']);
                    if (strpos($response['msg'], 'Не хватает денежных средств') !== false) array_push($msg['error'], 'Не хватает денежных средств');
                    if (strpos($response['msg'], 'существует') !== false) array_push($msg['error'], 'Документ уже существует');
                    if (strpos($response['msg'], 'Не проведен') !== false) array_push($msg['error'], 'Не проведен');
                    if (strpos($response['msg'], 'не проведен') !== false) array_push($msg['error'], 'Не проведен');
                }
                if (isset($response['info']['id'])) {
                    setNumberTrans($db, $id, $response['info']['id']);
                }

            }
        } else {
            array_push($msg['error'], $response);
        }
        if (isset($msg['error'])) {
            if (count($msg['error']) > 0) {
                $msg['status'] = -1;
                $msg['error'] = array_unique($msg['error']);
            } else {
                unset($msg['error']);
            }
        } else {
            unset($msg['error']);
        }
        if (isset($msg['info'])) {
            if (count($msg['info']) > 0) {
                $msg['info'] = array_unique($msg['info']);
            } else {
                unset($msg['info']);
            }
        } else {
            unset($msg['info']);
        }

        setRequestTrans($db, $id, (!empty($request_log['hash_before'])?$request_log['hash_before']:NULL), (!empty($response_log['hash_before'])?$response_log['hash_before']:NULL), $msg['status'], json_encode($msg, JSON_UNESCAPED_UNICODE));

    }

    function dateSort($a, $b) {
        if (strtotime($a['date']) > strtotime($b['date']))
            return 1;
        else if (strtotime($a['date']) < strtotime($b['date']))
            return -1;
        else
            return 0;
    }

    $config = parse_ini_file('config.ini', true);
    $db =  connect('development', $config); mysqli_select_db($db, $config['development']['dbname']);
    $api = new Get1C;
    $store = new Store;

    $lists = array(array('ExpenseList'=>'1c_expenses'), array('DDSList'=>'1c_dds'));
    foreach($lists as $key => $list) setRows($db, $api, $list);

    $lists = array(array('TillList'=>'1c_tills'));
    foreach($lists as $key => $list) setTills($db, $api, $list);

    $lists = array(array('GetMoneyStaff'=>'1c_cash'));
    foreach($lists as $key => $list) setMoneys($db, $api, $list);

    updateWallets($db);

    $items = [];
    $transfers = getTransfers($db);
    if(count($transfers)>0) {

        foreach($transfers as $key => $transfer)   {
            array_push($items, array('date'=>$transfer['date'], 'type'=>'transfer', 'data'=>$transfer));
        }
    }

    $finances = getFinances($db);
    if(count($finances)>0) {
        foreach($finances as $key => $finance)   {
            array_push($items, array('date'=>$finance['date'], 'type'=>'finance', 'data'=>$finance));
        }
    }

    uasort($items, 'dateSort');

    if(count($items)>0) {
        foreach ($items as $key => $item) {
            if($item['type']=='finance') { echo "\n"; echo "Расход от ".$item['date']; echo "\n"; runFinance($db, $item['data'], $api, $store); }
            if($item['type']=='transfer') { echo "\n"; echo "Перенос от ".$item['date']; echo "\n";  runTransfer($db, $item['data'], $api, $store); }
        }
    }
?>