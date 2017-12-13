<?php
require_once './db-connect.php';
require_once '../config-modules.php';


$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><realty-feed xmlns="http://webmaster.yandex.ru/schemas/feed/realty/2010-06"/>');
$csv = array_map('str_getcsv', file('data-multi.csv')); // Directory contains CSV-files
$resultFields = [];
$joins = [];
$fieldsForSelect = [];
$whereStr = '';
$whereValue = '';
$joinStr = '';
$selectStr = 'SELECT ';
$xmlFieldsArr = [];
$mainTableName = '';
//$dd = json_decode(file_get_contents("php://input"), true); // Datas from html form
$ids = IDS;
if (!empty($ids)) {
    foreach ($csv as $elementCsv) {
        $exists = true;
        $elementArr = explode(';', $elementCsv[0]);
        if (count($elementArr) >= 2) {
            $xmlFieldName = trim($elementArr[0]);

            $tableFieldName = explode(':', $elementArr[1]);
            if (array_key_exists(0, $tableFieldName) && array_key_exists(1, $tableFieldName)) {
                $tableName = trim($tableFieldName[0]);
                if ($mainTableName == '') {
                    $mainTableName = $tableName;
                }
                $fieldName = trim($tableFieldName[1]);
            } else {// Check empty fields
                $exists = false;
            }
        } else {
            $exists = false;
        }
        if ($exists) {//Check database fields
            $cheskingSql = "SHOW COLUMNS FROM $tableName LIKE :fieldName";
            $resChecikng = $mysql->prepare($cheskingSql);
            $resChecikng->bindParam(':fieldName', $fieldName);
            $resChecikng->execute();
            $exists = ($resChecikng->fetchColumn()) ? true : false;
			
        }


        if ($exists) {
            $xmlFieldsArr[$xmlFieldName] = $tableName . '.' . $fieldName;
            $xmlFieldsArr['id'] = $tableName . '.id';
            if (count($elementArr) > 2) {
                $parentIdTable = explode(':', $elementArr[1]);
                $joinIdTable = explode(':', $elementArr[2]);
                $parentTableName = trim($parentIdTable[0]);

                if (array_key_exists(0, $joinIdTable) && array_key_exists(1, $joinIdTable) && array_key_exists(2, $joinIdTable)) {
                    $joinParentFieldName = trim($joinIdTable[1]);
                    $joinFieldName = trim($joinIdTable[2]);
                } else {// Check empty fields
                    $exists = false;
                }
            }
        }
    }


    /** GENERATE SQL STRING * */
    $i = 0;
    $numItems = count($xmlFieldsArr);
    $selectStr = "SELECT ";
    foreach ($xmlFieldsArr as $xmlFieldName => $tableFieldData) {
        $selectStr .= $tableFieldData . ' AS ' . $xmlFieldName;;
        if (++$i !== $numItems) {
            $selectStr .= ',';
        }
    }
    $selectStr .= " FROM " . $mainTableName;
    if (!empty($parentTableName)) {
        $selectStr .= " LEFT JOIN $parentTableName ON $parentTableName.$joinFieldName = $mainTableName.$joinParentFieldName";
    }else{//Check PRIMARY key
        $parentTableName = 'listings';
        	
//        exit('Error: CSV-file doesn\'t contain primary table value! Check pls');
    }
	$selectStr .= ' WHERE listings.id IN('.implode(',', $ids).') ';
    $selectStr .= ";";
	$fp = fopen("json/SQLrequest.json", 'w');
	fwrite($fp, json_encode($selectStr));
	fclose($fp);
    $r = $mysql->prepare($selectStr);
    $r->execute();
    $resultAssoc = $r->fetchAll(PDO::FETCH_ASSOC);
	$fp = fopen("json/SQLResult.json", 'w');
	fwrite($fp, json_encode($resultAssoc));
	fclose($fp);
    /** GENERATE XML FROM DATA * */
    $xmlDate = date('c');
    $xml->addChild('generation-date', $xmlDate);
    foreach ($resultAssoc as $res) {
        $empty = true;

/*
        foreach ($xmlFieldsArr as $xmlFieldName => $tableFieldData) {
            if (is_null($res[$xmlFieldName])) {
                $empty = false;
            }
        }
*/		
        if ($empty) {
            $object = $xml->addChild('offer');
            foreach ($xmlFieldsArr as $xmlFieldName => $tableFieldData) {
                if (is_null($res[$xmlFieldName])) {
                    $empty = true;
                }
                if ($xmlFieldName == 'id') {
                    $object->addAttribute('internal-id', $res[$xmlFieldName]);
                } else {
                    $object->addChild($xmlFieldName, $res[$xmlFieldName]);
                }
            }
        }


    }
    $xmlFileName = 'data-' . date("Y-m-d-H-i-s") . '.xml';

    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    $dom->save('xml/' . $xmlFileName); //Save directory and name

    $data = array(
        'link' => $xmlFileName,
        'content' => file_get_contents('xml/' . $xmlFileName)//return the xml content for downloading immediately
    );
} else {
    $data = array(
        'error' => 'Basket is EMPTY!',
    );
}
exit(json_encode($data));


