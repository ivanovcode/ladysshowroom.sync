<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

$POST = file_get_contents('php://input');
file_put_contents('data.json', $POST);

?>