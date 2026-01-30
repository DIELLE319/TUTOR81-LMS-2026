<?php
require_once 'T81API.class.php';
// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    //error_log('http_origin non esiste. server_name = ' . $_SERVER['SERVER_NAME']);
    $_SERVER['HTTP_ORIGIN'] = 'https://' . $_SERVER['SERVER_NAME'];
} //else error_log ('origin: ' . parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST));

try {
    $API = new T81API($_REQUEST['request'], parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST));
    echo $API->processAPI();
} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}