<?php
if (!isset($_POST['submit'])) {
    header('location:index.php');
    exit;
}
require_once '../includes/functions.inc.php';
$data = array();


if ($_POST['submit'] == 'remote') {
    echo setState($_POST['key'], $_POST['value']);
    if($_POST['key'] == 'ActiveSpeaker') {
        checkZoneProfile($_POST['value']);
    }
} elseif ($_POST['submit'] == 'setup') {
    echo setConfig($_POST['key'], $_POST['value']);
} elseif ($_POST['submit'] == 'getSetup') {
    echo json_encode(getAllConfig());
}elseif($_POST["submit"] == "zonesRemote"){

    if(!isset($_POST["key"]) || !isset($_POST["value"]) || !isset($_POST["zone"])){
        return false;
    }

    setZoneP($_POST["key"], $_POST["value"], $_POST["zone"]);
} elseif ($_POST['submit'] == 'getStates') {

    $data  = getAllState();
    $data["Thermal"] = getConfig('Thermal');
    $data["MsgStatusTXT"] = getStateTmp('MsgStatusTXT');
    $data["Power"] = getConfig("Power");


    echo json_encode($data);
} elseif ($_POST['submit'] == 'settings') {
    if (setParameters($_POST['setup']) 
    && setTriggers($_POST['trigger']) 
    && setAVZones($_POST['zone']) 
    && setDownmixAVStatus($_POST['downmix'])
    && setSetup('ODualTheaterRca',$_POST['ODualTheaterRca'])
    && setSetup('AesThroughApmEnable',$_POST['AesThroughApmEnable'])
    && setSetup('VolumePolicy',$_POST['volPolicy'])
    && setSetup('VolumeDefault',$_POST['volDefault'])) {
        echo true;
    } else {
        echo false;
    }
} elseif ($_POST['submit'] == 'equalizer') {
    if ($_POST['temp'] == 1)
        echo setEQtemp($_POST['canal'], $_POST['zone'], $_POST['data']);
    else{
        echo setEQ($_POST['canal'], $_POST['config'], $_POST['zone'], $_POST['data']);
    }

}elseif ($_POST['submit'] == 'copyequalizer') {
    echo setCopyEQ($_POST['data'], $_POST['copy']);
}elseif ($_POST['submit'] == 'globalSaveEqualizer') {
    echo copyEQTempToReal($_POST["config"]);
}elseif ($_POST['submit'] == 'equalizer_polarity') {
    echo setEQPolarity($_POST['data']);
} elseif ($_POST['submit'] == 'inputs') {
    $isTmp = false;    
    echo setInputs($_POST['data'],$isTmp);
} elseif ($_POST['submit'] == 'inputsTmp') {
    $isTmp = true;
    echo setInputs($_POST['data'],$isTmp);
} elseif ($_POST['submit'] == 'configs') {
    echo setConfigs($_POST['data']);
} elseif ($_POST['submit'] == 'setTestPattern') {
    echo setTestPattern($_POST['data'], $_POST['circulartiming']);
} elseif ($_POST['submit'] == 'setTestDiracPattern') {
    echo setTestDiracPattern($_POST['data']);
} elseif ($_POST['submit'] == 'setPinkNoise') {
    echo setPinkNoiseState($_POST['key'], $_POST['value']);
} elseif ($_POST['submit'] == 'getInput') {
    echo json_encode(getInput($_POST['input']));
} elseif ($_POST['submit'] == 'setState') {
    $return = setState($_POST['key'], $_POST['value']);
    echo $return;
} elseif ($_POST['submit'] == 'getState') {
    $return = getState($_POST['key']);
    echo $return;
} elseif ($_POST['submit'] == 'setStateTmp') {
    $return = setStateTmp($_POST['key'], $_POST['value']);
    echo $return;
} elseif ($_POST['submit'] == 'getStateTmp') {
    $return = json_encode(getStateTmp($_POST['key']));
    echo $return;
} elseif ($_POST['submit'] == 'setExpertPassword') {
    //encryption
    $psw = md5($_POST['value']);

    echo setConfig('ExpertPassword', $psw);
} elseif ($_POST['submit'] == 'setInstallerPassword') {
    echo setInstallerPsw($_POST);
}elseif($_POST['submit'] == "setNetwork"){

    foreach($_POST["data"] as $k => $v){
        setConfig($k,$v);
    }


} elseif ($_POST['submit'] == 'setManagement') {
    echo setConfig('AMInputs', $_POST['value']['inputs']) && setConfig('AMSpeakers', $_POST['value']['speakers']) && setConfig('AMEq', $_POST['value']['eq']) && setConfig('AMPresets', $_POST['value']['presets']) && setConfig('AMFactoryReset', $_POST['value']['reset']) && setConfig('AMRemoteMonitoring', $_POST['value']['monitoring']) && setConfig('AMRemoteUpgrade', $_POST['value']['upgrade']) && setConfig('AMSettings', $_POST['value']['settings']);
} elseif ($_POST['submit'] == 'refreshStatusbar') {
    echo json_encode(refreshStatusbar());
} elseif ($_POST['submit'] == 'refreshRemote') {
    echo json_encode(refreshRemote($_POST['keys']));
} elseif ($_POST['submit'] == 'refreshZones') {
    echo json_encode(getZones());
} elseif ($_POST['submit'] == 'refreshRemotePower') {
    echo refreshRemotePower();
} elseif ($_POST['submit'] == 'setEqName') {
    echo setName('equalizersave', $_POST['id'], $_POST['value']);
} elseif ($_POST['submit'] == 'setSpeakerName') {
    echo setName('speakersave', $_POST['id'], $_POST['value']);
} elseif ($_POST['submit'] == 'resetRemote') {
    $return = 0;
    $preset = getPreset($_POST['id']);
    echo (int)$return;
}elseif ($_POST["submit"] == "remoteUpgrade"){
    exec("sudo systemctl start ISP_CU_Remote_upgrade.service 2>&1", $output, $status);
    if($status !==0){
        error_log($output[0], 0);
    }
}elseif ($_POST["submit"] == "diginmap")
{
    echo setDiginMap($_POST['map'], $_POST['sync']);
}elseif ($_POST["submit"] == "getDiginMap"){

    echo json_encode(createDiginPopUp());

}elseif($_POST["submit"] == "cloneZoneProfile") {
    $newPreset=cloneZoneProfile($_POST['eqId']);
    echo $newPreset;
}elseif($_POST["submit"] == "getZoneProfile") {
    $return=getZoneProfile($_POST['diracId']);
    echo json_encode($return);
}elseif($_POST["submit"] == "saveZoneProfile") {
    $preset = json_decode($_POST['preset']);
    echo saveZoneProfile($preset);
}elseif($_POST["submit"] == "setZoneProfileUnit") {
    echo setZoneProfileUnit($_POST['id'], $_POST['unitValue']);
}elseif($_POST["submit"] == "dismiss") {
    setState('MsgStatus', 0);
    echo "Ok";
}elseif ($_POST['submit'] == 'getVersions') {
    $data  = getVersions();
    echo json_encode($data);
}elseif ($_POST['submit'] == 'setProfileName') {
    saveProfileName($_POST['profileId'], $_POST['profileName']);
}elseif ($_POST['submit'] == 'setSpeakerZoneName') {
    setZoneP('name', $_POST['zoneName'], $_POST['zoneID']);
    if(($_POST['profileId'] != -1) && ($_POST['profileName'] != "")) {
        saveProfileName($_POST['profileId'], $_POST['profileName']);
    }
}elseif ($_POST['submit'] == 'startDirac') {
    $zoneId = $_POST['zoneId'];
    $profileId = $_POST['profileId'];
    setState('ActiveProfile', $profileId);
    resetAllToneControl($zoneId);
    setStateTmp('DiracState','1');
}elseif (($_POST['submit'] == 'getSpeakers') && (isset($_POST["isAlternate"]))){
    echo json_encode(getSpeakers($_POST["isAlternate"]));
}elseif (($_POST['submit'] == 'getNames') && (isset($_POST["description"]))){
    echo json_encode(getNames($_POST["description"]));
}elseif (($_POST['submit'] == 'getMapping') && (isset($_POST["isalt"]))){
    echo json_encode(getMapping($_POST["isalt"]));
}elseif (($_POST['submit'] == 'getNbChannels')){
    echo json_encode(getNbChannels());
}elseif (($_POST['submit'] == 'setSpeakersChannels') && (isset($_POST["channels"]))){
    echo json_encode(setSpeakersChannels($_POST["channels"]));
}elseif ($_POST['submit'] == 'deletePreset' && isset($_POST["preset"])) {
    echo deletePreset($_POST["preset"]);
}elseif ($_POST['submit'] == 'getSpeakersLimiters') {
    echo json_encode(getSpeakersLimiters());
}elseif ($_POST['submit'] == 'setRefVolume') { // Speakers Generator Reference Level       
    // records current volume in the zone table  
    $valRefVol = intval($_POST['value']);
    $idTheater = intval($_POST['id']);    
    setRefVolume($valRefVol,$idTheater);
}elseif ($_POST['submit'] == 'setMasterVolAtRefVolume') { // Speakers Generator Reference Level       
    // set master volume to the saved volume       
    $idTheater = intval($_POST['id']);        
    setMasterVolAtRefVolume($idTheater);
}elseif ($_POST['submit'] == 'getRefVolume'){ // Speakers Generator Reference Level
    $idTheater = intval($_POST['id']);
    echo json_encode(getRefVolume($idTheater));
}elseif($_POST['submit'] == 'setBypass'){ // bypass equalizer
    $id = intval($_POST['id']);
    $bypass = intval($_POST['bypass']);
    setBypass($id,$bypass);
}elseif ($_POST['submit'] == 'getAllEQsTmpPerChannel') {
    $canalEQ = intval($_POST['canalEQ']);   
    echo json_encode(getAllEQTempPerChannel($canalEQ));

}elseif($_POST['submit'] == 'saveEqsTmp'){  
    
    if ($_POST['temp'] == 1){
        echo saveEQ_tmp($_POST['canal'], $_POST['zone'], $_POST['data']);
    }else{
        //echo setEQ($_POST['canal'], $_POST['config'], $_POST['zone'], $_POST['data']);
        echo saveEQ($_POST['canal'], $_POST['zone'], $_POST['data']);
        
    }
}elseif($_POST['submit'] == 'loadEQTemp'){    
    $canalEQ = intval($_POST['canalEQ']);
    $zoneID = intval($_POST['zone']);
    $zone_profiles_id = intval($_POST['zone_profil_id']);    
    loadEQTemp($canalEQ, $zone_profiles_id, $zoneID);
}elseif($_POST['submit']=='setNetworkStby'){
    $stby = $_POST['value'];
    $key = "networkStandby";
    setConfig($key,$stby);
}elseif ($_POST["submit"]=="checkDiracBMProfil") {
    $currentProfileId = $_POST["currentProfileId"];
    $datas = json_encode(getDiracLiveBassManagement($currentProfileId));     
    echo $datas;
}elseif($_POST["submit"]=="DbUpdate") {
    if ($_POST['table'] == 'setup') {
        if(setSetup($_POST['key'], $_POST['value'])) {
            echo true;
        }
    }
    //TODO elseif for others tables
    echo false;
}