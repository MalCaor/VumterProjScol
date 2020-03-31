<?php
require_once '../includes/functions.inc.php';
require_once '../includes/monitoring.inc.php';


if($_POST['submit'] == 'generateGraphEqs'){
    $tabEqs = array();
    $tabEqs = $_POST['tabEqs'];
    $result = array();
    // draw a graph for each eq selected
    // input Params
    // /!\ Q-> 0.25 not bdd value (384) 
    // -t <type> -f <freq> -g <gain> -q <Qfactor>
    foreach ($tabEqs as $key => $value) {
        $type = $value[0];
        $frequency = $value[1];
        $gain = $value[2];
        $qFact = ($value[3] / 4);
        $jsonData = null;
       
        $cmd = sprintf("/root/ISP_CU/bin/ISP_CU_EqFreqResp  -t %d -f %d -g %f -q %f", $type, $frequency ,$gain, $qFact);
        $res = exec($cmd, $jsonData);
        
        array_push( $result, implode($jsonData));
    }

    echo json_encode($result);
}else{

    $deviceId = $_POST['deviceId'];
    $duration = $_POST['duration'];
    $result=array();
    $end = new DateTime("now",timezone_open('UTC'));
    $end = $end->getTimestamp();
    $start = $end - $duration;
    foreach(getAvailableDatas($deviceId) as $dname) {
        if(strpos($dname,'TEMP_TH')) {
            continue;
        }
        $graphData = getGraphData($deviceId, $dname, $start, $end);
        $result[$dname] = $graphData;
    }
    echo json_encode($result);
}