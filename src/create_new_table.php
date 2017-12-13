<?php

set_time_limit(0);
ignore_user_abort(true);
error_reporting(0);

require_once 'db-connect.php';
require_once 'modules.php';
$table = "coords_merge";
if(create_table($mysql,$table)){
    $result = 'Created '.$table.' Table.';
    echo json_encode(array('result' => $result));
}else{
    $result = $table." Table can't create.";
    echo json_encode(array('error' => ''));
}
exit();



