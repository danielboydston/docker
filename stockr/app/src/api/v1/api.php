<?php
require_once "STOCKR_API.class.php";
error_log("api.php has been hit");
if(!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {
    $API = new STOCKR_API($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
    echo $API->processAPI();
} catch(Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}
 ?>
