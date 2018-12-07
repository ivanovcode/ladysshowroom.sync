<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

if ($_GET['auth'] != 'd41d8cd98f00b204e9800998ecf8427e') die('access denied');
file_put_contents('post.json', json_encode($_POST));
file_put_contents('input.json', file_get_contents('php://input'));


?>