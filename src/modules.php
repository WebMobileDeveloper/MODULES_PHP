<?php
//require_once 'db-connect.php';
function create_table($mysql,$table){

    try {
        $create_sql = "DROP TABLE IF EXISTS $table;
        CREATE TABLE  $table (
          id INT(10) unsigned NOT NULL AUTO_INCREMENT,
          name VARCHAR(200) DEFAULT NULL,
          postcode VARCHAR(5) DEFAULT NULL,
          numero VARCHAR(20) DEFAULT NULL,
          lat100 VARCHAR(10) DEFAULT NULL,
          lon100 VARCHAR(10) DEFAULT NULL,
          lat300 VARCHAR(10) DEFAULT NULL,
          lon300 VARCHAR(10) DEFAULT NULL,
          lat500 VARCHAR(10) DEFAULT NULL,
          lon500 VARCHAR(10) DEFAULT NULL,
          lat1000 VARCHAR(10) DEFAULT NULL,
          lon1000 VARCHAR(10) DEFAULT NULL,
          lat2000 VARCHAR(10) DEFAULT NULL,
          lon2000 VARCHAR(10) DEFAULT NULL,
          ville VARCHAR(90) DEFAULT NULL,
          du datetime NOT NULL,
          villeid INT(10) NOT NULL,
          used TINYINT(1) NOT NULL DEFAULT 0,
          ref INT(10) NOT NULL,
          updated_check tinyint(1) NOT NULL DEFAULT 0,
          idcoord INT(10) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB AUTO_INCREMENT=870 DEFAULT CHARSET=utf8;";
        $mysql->exec($create_sql);
        return true;
    } catch(PDOException $e) {
        //$result = $e->getMessage();//Remove or change message in production code
        return false;
    }

}



