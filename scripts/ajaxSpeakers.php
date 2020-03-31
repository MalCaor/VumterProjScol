<?php

require_once '../includes/functions.inc.php';
if($_POST['submit'] == 'speakers') {
    if ($_POST['temp'] == 1) {
        saveZoneProfileParams($_POST['profile'], NULL, $_POST['gain'], $_POST['manualBm']);
        echo setSpeakers($_POST['config'], $_POST['data'], $_POST['profile'], true);

    } else {
        setZoneP('name', $_POST['zoneName'], $_POST['zoneID']);
        saveZoneProfileParams($_POST['profile'], $_POST['profileName'], $_POST['gain'], $_POST['manualBm']);
        echo setSpeakers($_POST['config'], $_POST['data'], $_POST['profile'], false);
    }
}else if($_POST["submit"] == "getNbOfFreeChannels") {
    echo getNbOfFreeChannels($_POST['isAlternate']);
} else if($_POST['submit'] == 'childzone') {    
    $data = $_POST['data'];
    echo createChildZone($data['parentZone'],$data['parentProfile'],$data['channelArray'],$data['keepDiracProfile']);

}else if($_POST['submit'] == "bypassChEq"){
    $canalEQ = $_POST['canalEQ'];
    $bypass = $_POST['bypass'];
    setEqsChBypass($canalEQ,$bypass);
}

