<?php

set_time_limit(0);
ignore_user_abort(true);
error_reporting(0);

require_once 'db-connect.php';

/* |===========================================|
   |    START:  create new table               |
   |===========================================|*/
require_once 'modules.php';
$table = "coords_merge";
if(create_table($mysql,$table)){
    $result = 'Created '.$table.' Table.';
}else{
    $result = $table." Table can't create.";
    echo json_encode(array('error' => ''));
    exit();
}
/* |-------------------------------------|
   |    END: create table                |
   |-------------------------------------|*/


/* |========================================================================|
   |    START:  Fetch all of the refs in the listings table                 |
   |========================================================================|*/

$sth = $mysql->query("SELECT ref FROM listings");
$refs = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
//var_dump($refs);

/* |------------------------------------------------------------------------|
   |    END:  Fetch all of the refs in the listings table                   |
   |------------------------------------------------------------------------|*/




/* |========================================================================|
   |    START:  Get all merged data from coords tables                      |
   |             And insert to coords_merge tabe                            |
   |========================================================================|*/

$queryStr = "SELECT coords100.name AS name, 
                    coords100.postcode AS postcode,
                    coords100.numero AS numero,
                    coords100.lat AS lat100,
                    coords100.lon AS lon100,
                    coords300.lat AS lat300,
                    coords300.lon AS lon300, 
                    coords500.lat AS lat500, 
                    coords500.lon AS lon500, 
                    coords1000.lat AS lat1000, 
                    coords1000.lon AS lon1000, 
                    coords2000.lat AS lat2000, 
                    coords2000.lon AS lon2000, 
                    coords100.ville AS ville, 
                    coords100.du AS du, 
                    coords100.villeid AS villeid, 
                    coords100.used AS used, 
                    coords100.ref AS ref, 
                    coords100.updated_check AS updated_check, 
                    coords100.idcoord AS idcoord FROM coords100 
                    JOIN coords300 ON coords100.ref = coords300.ref 
                    JOIN coords500 ON coords100.ref = coords500.ref 
                    JOIN coords1000 ON coords100.ref = coords1000.ref 
                    JOIN coords2000 ON coords100.ref = coords2000.ref 
                    WHERE coords100.ref IN(" . implode(',', $refs) . ")";

$select_results = $mysql->query($queryStr);

$insert_result = $mysql->prepare(
    "INSERT INTO $table (name, postcode, numero, lat100, lon100, lat300, lon300, lat500, lon500, lat1000, lon1000, lat2000, lon2000, ville, du, villeid, used, ref, updated_check, idcoord) 
               VALUES (:name, :postcode, :numero, :lat100, :lon100, :lat300, :lon300, :lat500, :lon500, :lat1000, :lon1000, :lat2000, :lon2000, :ville, :du, :villeid, :used, :ref, :updated_check, :idcoord)");

while ($row = $select_results->fetch(PDO::FETCH_ASSOC)) {
    $insert_result->execute($row);
}
exit(json_encode(array('result' => 'Operation completed successfully!')));
/* |------------------------------------------------------------------------|
   |    END:    Get all merged data from coords tables                      |
   |             And insert to coords_merge tabe                            |
   |------------------------------------------------------------------------|*/


