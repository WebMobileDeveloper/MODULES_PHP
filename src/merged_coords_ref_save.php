<?php

set_time_limit(0);
ignore_user_abort(true);
error_reporting(0);

require_once 'db-connect.php';

$dd = json_decode(file_get_contents("php://input"));

$ref = $dd->ref;

$tablename = [100, 300, 500, 1000, 2000];

// Program to insert new coordinates for a reference not saved in Coords Tables.
$cursor = $mysql->query("SELECT ref FROM Listings WHERE `nu` <> 0 and ref = $ref");
// For each result INSERT line in Coords1000

$ref = (int)$cursor->fetchColumn();

if (!$ref) {
  exit(json_encode(array('error' => 'invalid ref')));
}

// Check if reference exists in Coords Table
$resul = $mysql->query("SELECT id,ref FROM Coords WHERE ref = $ref");
$rowr = $resul->fetch(PDO::FETCH_ASSOC);

if (!$rowr) {  // si reference existe pas alors on va faire l'insertion dans coords

  $cursor = $mysql->query("SELECT id,rue,nu,villeid FROM Listings WHERE ref = $ref");
  $row = $cursor->fetch(PDO::FETCH_ASSOC);

  $villeid = $row['villeid'];
  $name = $row['rue'];
  $numero = (int)$row['nu'];

  if ($numero > 0) {
    $sqlb = "SELECT lat,lon FROM Coords Where name = :name and numero = $numero";
    $statemen = $mysql->prepare($sqlb);
    $statemen->execute(array(':name' => $name));
    $rownew = $statemen->fetch(PDO::FETCH_ASSOC);
    $lat = $rownew['lat'];
    $lon = $rownew['lon'];
    // replace later with proper address - it is quick fix for not found addresses
    if ($lat == null) {
      $sqlbc = "SELECT lat,lon FROM Villes Where id = $villeid";
      $resxc = $mysql->query($sqlbc);
      $rownewc = $resxc->fetch(PDO::FETCH_ASSOC);
      $lat = $rownewc['lat'];
      $lon = $rownewc['lon'];
      $numero = 0;
      $name = 0;
    }
  } else {
    $sqlbc = "SELECT lat,lon FROM Villes Where id = $villeid";
    $resxc = $mysql->query($sqlbc);
    $rownewc = $resxc->fetch(PDO::FETCH_ASSOC);
    $lat = $rownewc['lat'];
    $lon = $rownewc['lon'];
    $numero = 0;
    $name = 0;
  }

  //foreach ($tablename as $precision) {
    // check if already exists
    $resul = $mysql->query("SELECT id,ref FROM coords_merge WHERE ref = $ref");
    $rowr = $resul->fetch(PDO::FETCH_ASSOC);

    if (!$rowr) {
        $lats=[];
        $lons=[];
        foreach ($tablename as $precision) {
            $main_range = $precision; // the max allowed random range
            $mini_range = $precision / 2; // the min allowed range
            do {
                $res = scale_lat_lng($lat, $lon, $main_range, $mini_range);
                // $diii = haversineGreatCircleDistance($lat,$lon,$res['lat'],$res['lon'],6371000);
                $dii = getaddress($res['lat'], $res['lon']);
            } while ($dii == false);
            $lats['lat'.$precision] = $res['lat'];
            $lons['lon'.$precision] = $res['lon'];
        }


      $statement = $mysql->prepare("INSERT INTO coords_merge (lat100, lon100, lat300, lon300, lat500, lon500, lat1000, lon1000, lat2000, lon2000,name,numero,villeid,ref)
                VALUES(:lat100, :lon100, :lat300, :lon300, :lat500, :lon500, :lat1000, :lon1000, :lat2000, :lon2000, :name, :numero, :villeid, :ref)");
      $statement->execute(array(
        ':lat100' => $lats['lat100'],
        ':lon100' => $lons['lon100'],
        ':lat300' => $lats['lat300'],
        ':lon300' => $lons['lon300'],
        ':lat500' => $lats['lat500'],
        ':lon500' => $lons['lon500'],
        ':lat1000' => $lats['lat1000'],
        ':lon1000' => $lons['lon1000'],
        ':lat2000' => $lats['lat2000'],
        ':lon2000' => $lons['lon2000'],
        ':name' => $name,
        ':numero' => $numero,
        ':villeid' => $villeid,
        ':ref' => $ref
      ));
    }
}

function getaddress($lat, $lng)
{
  $url = 'https://maps.googleapis.com/maps/api/elevation/json?locations=' . trim($lat) . ',' . trim($lng) . '&key=AIzaSyCK_5OfbNXGT0Wknv0VNM1Rj5IkHQYzA9o';
  $json = file_get_contents($url);
  $data = json_decode($json);
  $elevation = $data->results[0]->elevation;
  if ($elevation > 2) {
    return $data->results[0]->location;
  } else {
    return false;
  }
}

function getdistance($lt1, $ln1, $lt2, $ln2)
{

  $dis = (($lt1 - $lt2) * ($lt1 - $lt2)) + (($ln1 - $ln2) * ($ln1 - $ln2));

  $dis = sqrt($dis);
  $dis = $dis / 0.000010475563373917;
  return $dis;
}

function scale_lat_lng($lat, $lng, $range, $mini_range)
{

  //$rand = rand(0,$range);
  $rand = rand($mini_range, $range);

  $ratio = $rand / 102059.82251083;

  $x1 = rand(0, 1);
  if ($x1 == 0) $x1 = -1;

  $x2 = rand(0, 1);
  if ($x2 == 0) $x2 = -1;

  $angle1 = rand(1, 99);
  $angle1 = $angle1 / 100;
  $angle2 = rand(1, 99);
  $angle2 = $angle2 / 100;
//	$lat2 =  $lat + 0.75*$x1;
//	$lng2 =  $lng + 0.75*$x2;
  $lat2 = $lat + $angle1 * $x1;
  $lng2 = $lng + $angle2 * $x2;

  $newLat = $lat + (($lat2 - $lat) * $ratio);
  $newLng = $lng + (($lng2 - $lng) * $ratio);

  $res['lat'] = $newLat;
  $res['lon'] = $newLng;

  return $res;

  $m100 = 104;

  $range1 = $range;

  $scale1 = ($range1 / 100) * $m100;
  $scale1_2 = $scale1 / 1.2;

  $rand1 = rand(-$scale1_2, $scale1_2) / 100000;
  $res['lat'] = $lat + $rand1;

  $rand2 = rand(-$scale1_2, $scale1_2) / 100000;
  $res['lon'] = $lon + $rand2;

  return $res;

}

function haversineGreatCircleDistance(
  $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
{
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $latDelta = $latTo - $latFrom;
  $lonDelta = $lonTo - $lonFrom;

  $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
      cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
  return $angle * $earthRadius;
}

echo json_encode(array('error' => ''));
