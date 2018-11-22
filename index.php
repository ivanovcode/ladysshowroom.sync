<?php


    header('Access-Control-Allow-Origin: http://admin.example.com');
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');

    error_reporting(E_ALL | E_STRICT);


/*header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);*/
parse_str(file_get_contents("php://input"),$data1);
$data2 = (array) json_decode(file_get_contents('php://input'), TRUE);
$data3 = file_get_contents("php://input");
$data4 = json_encode($_POST, JSON_UNESCAPED_UNICODE);
$data5 = $_POST;



print_r($data1);
print_r($data2);
print_r($data3);
print_r($data4);
print_r($data5);
var_dump($_POST);
var_dump(file_get_contents("php://input"));

?>