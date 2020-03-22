<?php

require_once 'db.inc.php';

try {
    $MDB = new PDO('mysql:host=' . MYSQL_HOST. ';dbname=' . MYSQL_MON_DB, MYSQL_USER, MYSQL_PASSWD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    $MDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    $MDB->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log('Unable to connect to monitoring DB:' . $e->getMessage());
}
// See https://auro-technologies-fr-doc.atlassian.net/wiki/display/ISP/Monitoring
// Data and event typemap section
$DATA_TYPETONAME = array (
    1 => 'PA_UNIT_TEMP',
    2 => 'PA_UNIT_FAN_FRONT',
    3 => 'PA_UNIT_FAN_BACK',
    4 => 'PA_MOD_01_TEMP',
    5 => 'PA_MOD_02_TEMP',
    6 => 'PA_MOD_03_TEMP',
    7 => 'PA_MOD_04_TEMP',
    8 => 'ISP_UNIT_TEMP',
    9 => 'PA_MOD_05_TEMP',
    10 => 'PA_MOD_06_TEMP',
    11 => 'PA_MOD_07_TEMP',
    12 => 'PA_MOD_08_TEMP',
    13 => 'ISP_UNIT_FAN',
    14 => 'PA_UNIT_TEMP_TH_MAX',
    15 => 'PA_MOD_TEMP_TH'
);

$DATA_NAMETOTYPE = array_flip($DATA_TYPETONAME);

function modelName($model, $brand)
{
    $MODEL_NAME = array (
        1 => '2ISP',
        2 => 'ISP',
        3 => 'PA8 ELITE',
        4 => 'PA8 ULTRA',
        5 => 'PA16'
    );
    $name = $MODEL_NAME[$model];
    // currently there is only one rebranded product
    if($brand == 'Bryston') {
        $name = 'SP4';
    }
    return $name;
}

function getDevices() {
    global $MDB;

    $req = $MDB->prepare('SELECT * from devices');
    $req->execute();

    $res = $req->fetchAll();
    return $res;
}


function getPaDevices() {
    global $MDB;

    $req = $MDB->prepare('SELECT * from devices WHERE model=3 OR model=4 OR model=5');
    $req->execute();

    $data = array();
    foreach($req->fetchAll() as $device) {
        $state = getDeviceState($device->id);
        $data[] = array(
                "id" => $device->id,
                "ipaddr" => $device->ipaddr,
                "name" => $device->name,
                "model" => $device->model,
                "monitored" => $device->monitored,
                "swVersion" => $device->swVersion,
                "cntSwVersion" => $device->cntSwVersion,
                "cntSerial" => $device->cntSerial,
                "isAlive" => ($state['UNIT_ALIVE'] == "1")
        );
    }
    return $data;
}

function setDeviceName($devId, $name) {
    global $MDB;

    $req = $MDB->prepare('UPDATE `devices` SET `name`=:name WHERE `id`=:id');
    $req->bindValue(':name', $name);
    $req->bindValue(':id', $devId);
    return $req->execute();
}

function getDeviceState($devId) {
    global $MDB;

    $req = $MDB->prepare('SELECT `key`,`value` from state WHERE deviceID=:devId');
    $req->bindValue(':devId',$devId);
    $req->execute();
    $res = $req->fetchAll();

    $state = array();
    foreach ($res as $line) {
        $state[$line->key] = $line->value;
    }
    return $state;
}

// return a list of the data available for a given device
function getAvailableDatas($devId) {
    global $MDB;
    global $DATA_TYPETONAME;

    $req = $MDB->prepare('SELECT type FROM datas WHERE deviceID=:devId');
    $req->bindValue(':devId',$devId);
    $req->execute();
    $dataList = array();
    foreach($req->fetchAll() as $type) {
        if(isset($DATA_TYPETONAME[$type->type])) {
            $dataList[] = $DATA_TYPETONAME[$type->type];
        }
    }
    return $dataList;
}

// get the rrd file patha for a given data
function getRrdPath($devId, $dataType)
{
    global $MDB;
    $sql = 'SELECT rrdName FROM datas WHERE deviceId=:devId AND type=:type';
    $req = $MDB->prepare($sql);
    $req->bindValue(':devId',$devId);
    $req->bindValue(':type',$dataType);
    $req->execute();
    $res = $req->fetch();
    if(!$res) {
        error_log("No data with type $dataType for device $devId", 0);
        return NULL;
    }

    $rrd = $res->rrdName;
    $state = getDeviceState(0);
    if(isset($state['RRD_BASE_DIR'])) {
        $rrd_base = $state['RRD_BASE_DIR'];
    } else {
        $rrd_base = '/tmp/';
    }

    $path = $rrd_base.'/'.$rrd;
    return $path;
}

// returns an array containing timestamp and value
function getCurrentValueByType($devId, $dataType)
{
    $path = getRrdPath($devId, $dataType);
    if(!$path) {
        return NULL;
    }
    $res = exec('/usr/bin/rrdtool lastupdate --daemon unix:/tmp/rrdcached.sock '.$path, $output, $status);
    if($status !==0){
        error_log($output[0], 0);
        return NULL;
    }
    //$res should contain a line like this 1234567: 789 where 1234567 is a
    // Unix timestamp, and 789 the value we are looking for
    $res = explode(':',$res);
    return array(
        'timestamp' => $res[0],
        'value' => $res[1]
    );
}

function getCurrentValue($devId, $dataName)
{
    global $DATA_NAMETOTYPE;
    $type = $DATA_NAMETOTYPE[$dataName];
    return getCurrentValueByType($devId, $type);
}

// [start,end]Unix are unix timestamp string, ie a number of seconds
// elapsed since january first 1970 UTC
function getGraphData($devId, $dataName, $startUnix, $endUnix)
{
    global $DATA_NAMETOTYPE;
    $type = $DATA_NAMETOTYPE[$dataName];
    $path = getRrdPath($devId, $type);
    $duration = $endUnix - $startUnix;
    $width = round($duration / 600);
    if($width < 1440) {
        $width = 1440;
    }
    $cmd = sprintf("rrdtool xport --daemon unix:/tmp/rrdcached.sock -s %s -e %s --json -m %d",
                   $startUnix, $endUnix,$width);
    $def = sprintf("DEF:ds0=%s:ds0:AVERAGE XPORT:ds0:%s",
                   $path, $dataName);
    $cmd = sprintf("/usr/bin/%s %s", $cmd, $def);
    $res = exec($cmd, $jsonData, $status);
    return implode($jsonData);
}


// Create a TCP socket with ZMQ with IP address {$pIpAddr} and port 5557
// and send message {$pMsg}
function sendSocketMsg($pIpAddr, $pMsg) {
    $lSocket = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_PUSH);
    $lSocket->connect("tcp://".$pIpAddr.":5557");
    $lSocket->send($pMsg);
    $lSocket->disconnect();

    return $pMsg;
}


// Call sendSocketMsg to send the message PA_CNT_SET_CHANNEL_{$pChannel}_MUTE {$pValue}
// to the IP address {$pIpAddr}
function sendCmdChannelMute($pIpAddr, $pChannel, $pValue) {
    return sendSocketMsg($pIpAddr, "PA_CNT_SET_CHANNEL_".str_pad($pChannel, 2, 0, STR_PAD_LEFT)."_MUTE ".$pValue);
}

// Call sendSocketMsg to send the message PA_CNT_SET_UNIT_STB_ON {$pValue}
// to the IP address {$pIpAddr}
function sendCmdSetUnitSTB($pIpAddr, $pValue) {
    return sendSocketMsg($pIpAddr, "PA_CNT_SET_UNIT_STB_ON ".$pValue);
}


// don't call following function from web code, it is intended
// for debug only. It can be called like this :
// php -r "require 'monitoring.inc.php';testBackend();"
function testBackend() {
    echo ("=== Devices ===\n");
    print_r(getDevices());
    foreach(getDevices() as $dev) {
        if ($dev->monitored == 1) {
            $testDev = $dev;
            break;
        }
    }
    echo("\n=== State ===\n");
    print_r(getDeviceState($testDev->id));
    echo("\n=== Available Datas ===\n");
    $monitoredDatas = getAvailableDatas($testDev->id);
    print_r($monitoredDatas);
    echo("\n=== Current Values ===\n");
    foreach($monitoredDatas as $d) {
        print_r(getCurrentValue($testDev->id, $d));
    }
    echo("\n=== Valid data serie ===\n");
    $end = new DateTime("now",timezone_open('UTC'));
    $end = $end->getTimestamp();
    $start = $end - 3600 * 24;

    foreach($monitoredDatas as $d) {
        $json = getGraphData($testDev->id, $d, $start, $end);
        $graphData = json_decode($json);
        if($graphData == NULL) {
            echo("Could not generate graph data for ".$d."\n");
        } else {
            echo("Valid graph data for ".$d."\n");
        }
    }

    sendSocketMsg($testDev->ipaddr, "PA_CNT_IS_ALIVE");
}

function getRooms() {
    global $MDB;

    $req = $MDB->prepare('SELECT * from rooms');
    $req->execute();

    $res = $req->fetchAll();
    return $res;
}

function getDevicesFromRoom($RoomId) {
    global $MDB;

    $req = $MDB->prepare('SELECT `devicesID` from rooms WHERE id=:RoomId');
    $req->bindValue(':RoomId', $RoomId);
    $req->execute();

    $res = current($req->fetch());
    return $res;
}

function setDevicesFromRoom($pRoomId, $pValue) {
    global $MDB;

    $req = $MDB->prepare('UPDATE `rooms` SET `devicesID`=:Value WHERE `id`=:RoomId');
    $req->bindValue(':RoomId', $pRoomId);
    $req->bindValue(':Value', $pValue);
    $req->execute();

    return true;
}

function getMaxEventId($devId) {
    global $MDB;

    $req = $MDB->prepare('SELECT `id` from events WHERE deviceID=:devId ORDER BY id DESC LIMIT 1');
    $req->bindValue(':devId',$devId);
    $req->execute();

    $res = $req->fetchAll();
    return $res;
}

function getEvents($devId, $eventId) {
    global $MDB;

    $req = $MDB->prepare('SELECT * from events WHERE deviceID=:devId AND id<:eventId ORDER BY id DESC LIMIT 100');
    $req->bindValue(':devId',$devId);
    $req->bindValue(':eventId',$eventId);
    $req->execute();

    $res = $req->fetchAll();
    return $res;
}

function getLastEvents($devId, $eventId) {
    global $MDB;

    $req = $MDB->prepare('SELECT * from events WHERE deviceID=:devId AND id>:eventId ORDER BY id DESC');
    $req->bindValue(':devId',$devId);
    $req->bindValue(':eventId',$eventId);
    $req->execute();

    $res = $req->fetchAll();
    return $res;
}

function getThresholds($devId) {
    global $MDB;
    $res = array();

    $req = $MDB->prepare('SELECT * from thresholds WHERE deviceID=:devId');
    $req->bindValue(':devId',$devId);
    $req->execute();
    $Thresholds = $req->fetchAll();

    $req = $MDB->prepare('SELECT * from thresholds WHERE deviceID=0');
    $req->execute();
    $ThresholdsDef = $req->fetchAll();

    foreach($ThresholdsDef as $ThresholdDef){
        $found = False;

        //Search if the threshold is redefined for this device
        foreach($Thresholds as $Threshold){
            if($Threshold->type == $ThresholdDef->type) {
                $res[] = array(
                        "type" => $Threshold->type,
                        "valueWarning" => $Threshold->valueWarning,
                        "valueCritical" => $Threshold->valueCritical
                );
                $found = True;
                break;
            }
        }

        if($found == False) {    //Not redefined => take the default value
            $res[] = array(
                    "type" => $ThresholdDef->type,
                    "valueWarning" => $ThresholdDef->valueWarning,
                    "valueCritical" => $ThresholdDef->valueCritical
            );
        }
    }

    return $res;
}

function setThreshold($devId, $type, $warningValue, $criticalValue) {
    global $MDB;

    $req = $MDB->prepare('SELECT * from thresholds WHERE deviceID=:devId AND type=:type');
    $req->bindValue(':devId',$devId);
    $req->bindValue(':type',$type);
    $req->execute();
    $threshold = $req->fetchAll();

    if(!$threshold) {    //Insert
        $req = $MDB->prepare('INSERT INTO thresholds(`deviceID`,`type`,`valueWarning`,`valueCritical`) VALUES(:deviceId,:type,:warnVal,:critVal)');
        $req->bindValue(':deviceId', $devId);
        $req->bindValue(':type', $type);
        $req->bindValue(':warnVal', $warningValue);
        $req->bindValue(':critVal', $criticalValue);
        $req->execute();
    }
    else {    //Update
        $req = $MDB->prepare('UPDATE thresholds SET valueWarning=:warnVal,valueCritical=:critVal WHERE deviceID=:deviceId AND type=:type');
        $req->bindValue(':deviceId', $devId);
        $req->bindValue(':type', $type);
        $req->bindValue(':warnVal', $warningValue);
        $req->bindValue(':critVal', $criticalValue);
        $req->execute();
    }

    return true;
}

function getCurrentInput($id) {
    global $PDO;
    
    $req = $PDO->prepare('SELECT * FROM `inputs` WHERE `id` = "' . $id . '"');
    $req->execute();
    
    return $req->fetch();
}
