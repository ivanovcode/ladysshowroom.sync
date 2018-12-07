<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);


file_put_contents('post.json', json_encode($_POST));
file_put_contents('input.json', file_get_contents('php://input'));


?>