<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

$response = json_encode($_POST);
if(empty($response)) echo 'response no POST in request';
file_put_contents('data.json', $response);

$response = file_get_contents('php://input');
if(empty($response)) echo 'response no php://input in request';
file_put_contents('data.json', $response);

?>