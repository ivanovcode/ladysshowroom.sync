<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
set_time_limit(0);
ini_set("display_errors",1);
error_reporting(E_ALL);

function push($data, $name, $die=false, $clear=false, $msg=''){
    if ($clear) unlink(dirname(__FILE__).'/'.$name.'.log');
    $fp = fopen(dirname(__FILE__).'/'.$name.'.log', 'a');
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

function clearOrders($db) {
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0;");
    mysqli_query($db, "TRUNCATE table orders;");
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1;");
    mysqli_query($db, "ALTER TABLE orders AUTO_INCREMENT = 100;");

}

function addGroup($db, $row){

    $query = "
        INSERT IGNORE INTO  `1c_groups`
        (
            `id` ,
            `name`
        )
        VALUES
        ('" . $row['id'] . "',  '" . $row['name'] . "')
        ON DUPLICATE KEY UPDATE `1c_groups`.id =  '" . $row['id'] . "', `1c_groups`.name =  '" . $row['name'] . "';
    ";
    mysqli_query($db, $query);
    return mysqli_insert_id($db);
}


function clearParent($db){
    $query = "
        TRUNCATE 1c_staffs.groups
    ";
    mysqli_query($db, $query);
}
function addParent($db, $row){

    $query = "
        INSERT IGNORE INTO  `1c_staffs.groups`
        (
            `id_1c_staff` ,
            `id_group`
        )
        VALUES
        ('" . $row['id_1c_staff'] . "',  '" . $row['id_group'] . "')
        ON DUPLICATE KEY UPDATE `1c_staffs.groups`.id_1c_staff =  '" . $row['id_1c_staff'] . "', `1c_staffs.groups`.id_group =  '" . $row['id_group'] . "';
    ";
    mysqli_query($db, $query);
    return mysqli_insert_id($db);
}

function addStaff($db, $row){

    $query = "
        INSERT IGNORE INTO  `1c_staffs`
        (
            `id` ,
            `name` ,
            `firstname`
        )
        VALUES
        ('" . $row['id'] . "',  '" . $row['name'] . "',  '" . $row['firstname'] . "')
        ON DUPLICATE KEY UPDATE `1c_staffs`.id =  '" . $row['id'] . "', `1c_staffs`.name =  '" . $row['name'] . "';
    ";
    mysqli_query($db, $query);
    return mysqli_insert_id($db);
}

function deleteStaffs($db, $ids){
    $ids = implode (", ", $ids);
    $query = "
        UPDATE `1c_staffs` s SET s.delete = 1 WHERE s.id NOT IN (".$ids.");
    ";
    echo $query;
    mysqli_query($db, $query);
}

function getStaffsFrom1C(){
    if (!_iscurl()) push('curl is disabled', 'error', true);
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://cloud.itone.ru/LADYSSHOWROOM_UNF/hs/atnApi/StaffList",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "UTF-8",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 500,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        //CURLOPT_POSTFIELDS => "{\"product\": \"\"}",
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

function firstname($name) {
    $n = explode(' ',trim($name));
    return ((!isset($n[1]) || empty($n))?$n[0]:$n[1]);
}

$config = parse_ini_file('config.ini', true);
$db =  connect('production', $config);
mysqli_select_db($db, $config['production']['dbname']);

$rows = getStaffsFrom1C();
//if(empty($staffs)) push('staffs no records', 'error', true);


$groups = [];
$parents = [];
$staffs = [];
foreach ($rows as $key => $row) {
    if(!empty($row['staff']) && !empty($row['id'])) {
        $staffs[$row['id']] = array("id"=>$row['id'], "name"=>$row['staff']);
    }
    if(!empty($row['parent'])) {
        $groups[$row['parent']['id']] = array("id"=>$row['parent']['id'], "name"=>$row['parent']['name']);
        $parents[$row['id']] = array("id_1c_staff"=>$row['id'], "id_group"=>$row['parent']['id']);
    }
}

foreach ($groups as $key => $group) {
    addGroup($db, $group);
}

clearParent($db);
foreach ($parents as $key => $parent) {
    addParent($db, $parent);
}

$staff_inserts = [];
foreach ($staffs as $key => $staff) {

    $staff['firstname'] = firstname($staff['name'])."-".$staff['id'];
    addStaff($db, $staff);
    array_push($staff_inserts, $staff['id']);
}
deleteStaffs($staff_inserts);







/*print_r($groups);
print_r($parents);
print_r($staffs);*/

disconnect($db);  /*$response = []; echo json_encode($response, JSON_UNESCAPED_UNICODE );*/

?>