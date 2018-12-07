<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);


if(empty($POST)) die('no POST in request');
file_put_contents('data.json', $POST);
$POST = file_get_contents('php://input');
if(empty($POST)) die('no php://input in request');
file_put_contents('data.json', $POST);

?>