<?php

if(session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.inc.php';
require_once 'function_ajax.inc.php';

function getAllIndexes($array, $searchObject) {
    $keys = array();
     foreach($array as $k => $v) {
         if($v == $searchObject)
             $keys[] = $k;
    }
    return $keys;
}

function checkSpeaker(){
    global $PDO;


    $activeSpeaker = getState('ActiveSpeaker');

    if(empty(getZone($activeSpeaker))){
        setState('ActiveSpeaker', 0);
        setState('ActiveProfile', 0);
    }
}

function checkZoneProfile($zoneId) {
    $activeProfile = getState('ActiveProfile');
    $profiles = getEqsPresets($zoneId);
    if(empty($profiles)) {
        //this should not happen
        error_log('No profiles for zone '.$zoneId);
        setState('ActiveSpeaker', 0);
        setState('ActiveProfile', 0);
        return;
    }
    foreach($profiles as $p) {
        if ($p->id == $activeProfile) {
            return;
        }
    }
    // current profile is not valid, set a valid one
    setState('ActiveProfile',$profiles[0]->id);
}

function checkToneControl($maxValue=300){

    global $PDO;

    if(getState('Treble') > $maxValue){
        setState('Treble', $maxValue);
    }

    if(getState('Bass') > $maxValue){
        setState('Bass', $maxValue);
    }

    if(getState('SurroundEnhance') > $maxValue){
        setState('SurroundEnhance', $maxValue);
    }

    if(getState('CenterEnhance') > $maxValue){
        setState('CenterEnhance', $maxValue);
    }

    if(getState('SubEnhance') > $maxValue){
        setState('SubEnhance', $maxValue);
    }

    //Special case for brightness as the value is between -60 and 60 (instead of [-600;600]
    if(getState('Brightness') > ($maxValue/10)){
        setState('Brightness', ($maxValue/10));
    }

    $req = $PDO->prepare('UPDATE zones SET bass=' . $maxValue . ' WHERE bass>' . $maxValue . '');
    $req->execute();

    $req = $PDO->prepare('UPDATE zones SET treble=' . $maxValue . ' WHERE treble>' . $maxValue . '');
    $req->execute();
}

function isAutorize($keyPage) {
    if (!isset($_SESSION['type'])) {
        header('location: /login.php');
        exit;
    } elseif ($_SESSION['type'] == 'expert') {
        if ($keyPage != "AMVersion" && getConfig($keyPage) == 0) {
            header('location: /index.php');
            exit;
        }
    }
}


function getNames($description, $model = "") {
    global $PDO;

    $data = array();

    if ($model == "")
        $req = $PDO->prepare('SELECT IF(numerotation IS NULL, id,numerotation) as id,name FROM `names` WHERE `description` = "' . $description . '"');
    else
        $req = $PDO->prepare('SELECT IF(numerotation IS NULL, id,numerotation) as id,name FROM `names` WHERE `description` = "' . $description . '" AND model_' . $model . '="1" ORDER BY ordre,id');
    $req->execute();

    foreach ($req->fetchAll() as $v) {
        $data[$v->id] = $v->name;
    }

    return $data;
}

function getNamesWhereNum($description,$id){
    global $PDO;
    $req = $PDO->prepare('SELECT name FROM `names` WHERE `description` = "' . $description . '" AND `numerotation` = "' . $id . '"');
    $req->execute();
    return $req->fetch();
}

function getNamesTable($table) {
    global $PDO;

    $data = array();

    if ($table != "presets")
        $req = $PDO->prepare('SELECT id,name FROM ' . $table);
    elseif (isLicenseSphereAudio() == true)
        $req = $PDO->prepare('SELECT id,name FROM ' . $table . ' WHERE active="1"');
    else {
        $str = 'SELECT id,name FROM ' . $table . ' WHERE active="1"';
        foreach (getSphereAudioZones() as $zone) {
            $str .= ' AND speaker!=' . $zone->id;
        }
        $req = $PDO->prepare($str);
    }

    $req->execute();
    foreach ($req->fetchAll() as $v) {
        $data[$v->id] = $v->name;
    }
 
    natcasesort($data);
    return $data;
}

function getConfig($key) {
    global $PDO;
    $req = $PDO->prepare('SELECT `value` FROM `setup` WHERE `key` = "' . $key . '"');
    $req->execute();
    $res = $req->fetch();
    if($res) {
        return current($res);
    }
    return 0;
}

//
/**
 * @deprecated 
 * TODO setConfig() is duplicated, replace every calls by setSetup() / use setSetup() instead
 */
function setConfig($key, $value) {
    global $PDO;

    $req = $PDO->prepare('UPDATE `setup` SET `value`=:value WHERE `key`=:key');
    $req->bindValue(':value', $value);
    $req->bindValue(':key', $key);
    return $req->execute();
}

function getState($key) {
    global $PDO;

    if($key == "ProcState"){
        $shmid = shmop_open(12325, "w", 0, 0);
        $data = (current(unpack("int", shmop_read($shmid, 8, 4))));
        shmop_close($shmid);
    }else{
        $req = $PDO->prepare('SELECT `value` FROM `states` WHERE `key` = "' . $key . '"');
        $req->execute();
        $data = $req->fetch();
        if($data != FALSE) {
            $data=current($data);
        } else {
            error_log('empty result for get state with key '.$key);
        }
    }

    return $data;
}


function getStateTmp($key){
    global $PDO;

    $req = $PDO->prepare('SELECT `value` FROM `states_tmp` WHERE `key` = "' . $key . '"');
    $req->execute();

    $data = current($req->fetch());

    if($key == "InputStream_LONG"){

        $inputFS = getStateTmp("InputsFS");
        $inputF = getStateTmp("InputChannels");

        $data.= "<br>". $inputFS . " " . $inputF;
    }

    return $data;
}

function setState($key, $value) {
    global $PDO;
    if($key == "Preset"){
        $v = getState('Preset');
        if($value == $v){
            $d = getPreset($v);
            setState("ActiveSpeaker", $d['speaker']);
            setState("ActiveProfile", $d['eq']);
        }
    }

    $req = $PDO->prepare('UPDATE `states` SET `value`=:value WHERE `key`=:key');
    $req->bindValue(':value', $value);
    $req->bindValue(':key', $key);
    if ($req->execute()) {
        if($key == "ToneControl"){
            if($value==1) {
                checkToneControl(300);
            } elseif($value==2) {
                checkToneControl(0);
            }
        }else if($key == "Input"){
            if($value == 1)
                checkLipSync();
        }
        return true;
    } else {
        return false;
    }
}

function setDiginMap($map, $sync)
{
    global $PDO;

    $decode = json_decode($map, true);

    $req = $PDO->prepare('UPDATE `diginmap` SET `inputSignal`=:value , `sync`=:sync WHERE `id`=:id');
    foreach ($decode as $k => $v) {
        $req->bindValue(':value', $v);
        if($k+1 == $sync)
        {
            $req->bindValue(':sync', 1);
        }
        else
        {
            $req->bindValue(':sync', 0);
        }
        $req->bindValue(':id', $k+1);
        $req->execute();
    }

    return true;
}

function setStateTmp($key, $value) {
    global $PDO;

    $req = $PDO->prepare('UPDATE `states_tmp` SET `value`=:value WHERE `key`=:key');
    $req->bindValue(':value', $value);
    $req->bindValue(':key', $key);
    return $req->execute();
}

function getAllConfig() {
    global $PDO;

    $data = array();

    $req = $PDO->prepare('SELECT `key`,`value` FROM `setup`');
    $req->execute();
    foreach ($req->fetchAll() as $v) {
        $data[$v->key] = $v->value;
    }
    return $data;
}

/*Return value for the key in setup table*/
function getSetupField($key){
    global $PDO;

    $req = $PDO->prepare('SELECT `value` FROM `setup` WHERE `key` = "' . $key . '"');
    $req->execute();

    $data = current($req->fetch());
    return $data;
}
function setSetup($key, $value) {
    global $PDO;

    $req = $PDO->prepare('UPDATE `setup` SET `value`=:value WHERE `key`=:key');
    $req->bindValue(':value', $value);
    $req->bindValue(':key', $key);
    return $req->execute();
}
function getVersions() {
    global $PDO;

    $data = array();

    $req = $PDO->prepare('SELECT `key`,`value` FROM `versions`');
    $req->execute();
    foreach ($req->fetchAll() as $v) {
        $data[$v->key] = $v->value;
    }
    return $data;
}

function getVersion($key){
    global $PDO;

    $req = $PDO->prepare('SELECT `value` FROM `versions` WHERE `key` = "' . $key . '"');
    $req->execute();

    $data = current($req->fetch());
    return $data;
}

function getAllState() {
    global $PDO;

    $data = array();

    $req = $PDO->prepare('SELECT `key`,`value` FROM `states`');
    $req->execute();
    foreach ($req->fetchAll() as $v) {
        $data[$v->key] = $v->value;
    }

    $req = $PDO->prepare('SELECT `key`,`value` FROM `states_tmp`');
    $req->execute();
    foreach ($req->fetchAll() as $v) {
        $data[$v->key] = $v->value;
    }

    $data["Mute"] = getState('Mute');
    $data["ProcState"] = getState('ProcState');

    return $data;
}

function getConfigs() {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `presets` ORDER BY id ASC');
    $req->execute();
    return $req->fetchAll();
}

function getConfigsAPI() {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `presets` WHERE active=1 ORDER BY id ASC');
    $req->execute();
    return $req->fetchAll();
}

function setConfigs($values) {
    global $PDO;

    $currentPreset = getState('Preset');

    $err = 0;

    foreach ($values as $id => $data) {

        $req = $PDO->prepare('UPDATE `presets` SET `name`=:name, `active`=:active, `speaker`=:speaker, `eq`=:eq, trigger1=:trigger1, trigger2=:trigger2, trigger3=:trigger3, trigger4=:trigger4, zones=:zones, preferred_surroundmode=:preferred_surroundmode WHERE `id`="' . $id . '"');
        foreach ($data as $k => $v) {
            if($k != "name" && $k != "zones")
                $req->bindValue(':' . $k, $v);
            elseif($k == "name")
                $req->bindValue(':' . $k, strip_tags($v));
            elseif($k == "zones")
                $req->bindValue(':' . $k, json_encode($v));
        }

        if (!$req->execute()) {
            $err++;
        }

        if($id == $currentPreset){
            setState('ActiveSpeaker', $data["speaker"]);
            setState('ActiveProfile', $data["eq"]);
        }
    }

    return $err == 0;
}

function setParameters($values) {

    $err = 0;
    static $states = [
        "ReferenceLevel", "DimLevel", "AVDelay", "LfeLimitThresh", "LimitEnable",
        "ChanLimitThresh", "AutomaticPreset", "AutomaticStrength", "ToneControl",
    ];

    foreach ($values as $k => $v) {

        if (in_array($k, $states)) {
            if (!setState($k, $v)) {
                $err++;
            }
        } else {
            if (!setConfig($k, $v)) {
                $err++;
            }
        }
    }

    return $err == 0;
}

function getTriggers() {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `triggers`');
    $req->execute();
    return $req->fetchAll();
}

function setTriggers($values) {
    global $PDO;

    $err = 0;

    foreach ($values as $id => $data) {
        $req = $PDO->prepare('UPDATE `triggers` SET `name`=:name, `auto`=:auto, `manual`=:manual, `polarity`=:polarity, `activeStandby`=:activeStandby, `delay`=:delay  WHERE id="' . $id . '"');
        foreach ($data as $k => $v) {
            if($k != "name")
                $req->bindValue(':' . $k, $v);
            else
                $req->bindValue(':' . $k, strip_tags($v));
        }

        if (!$req->execute()) {
            $err++;
        }
    }

    return $err == 0;
}

function getInputs($filter='all') {
    global $PDO;
    $query = 'SELECT * FROM `inputs`';

    if($filter == 'main' || $filter == 'zone') {
        $query .= " WHERE `name` <> ''";
        if($filter == 'zone') {
            // only return inputs which have a zone audio input defined and not following main
            $query .= " AND `audioIn` > 0 and `zone2AudioIn` > 0";
        }
    }
    $req = $PDO->prepare($query);
    $req->execute();
    return $req->fetchAll();
}

function setInputs($values,$isTmp) {
    global $PDO;

    $err = 0;
    $table = $isTmp?'inputs_tmp':'inputs';
    foreach ($values as $id => $data) {        
        $req = $PDO->prepare('UPDATE `'.$table.'` SET `name`=:name, `audioIn`=:audioIn, `zone2AudioIn`=:zone2AudioIn, `trim`=:trim,`delay`=:delay, `videoIn`=:videoIn, trigger1=:trigger1, trigger2=:trigger2, trigger3=:trigger3, trigger4=:trigger4, upmix=:upmix, 
        active=:active WHERE `id`="' . $id . '"');
    
        foreach ($data as $k => $v) {                        
            if($k != "name")
            $req->bindValue(':' . $k, $v);
            else
            $req->bindValue(':' . $k, strip_tags($v));
        }
        
        if (!$req->execute()) {            
            $err++;
        }
        
    }
    checkLipSync();

    return true;
}

function getSpeakers($config) {
    global $PDO;

    if($config == 0){
        $max = getNbChannels();
    }else{
        $max = 16;
    }

    $req = $PDO->prepare('SELECT * FROM `speakers` WHERE `preset` = "' . $config . '" LIMIT 0,' . $max . '');
    $req->execute();
    return $req->fetchAll();
}

function getSpeakersZone($zone, $zoneProfile=0) {
    global $PDO;

    $zone = getZone($zone);
    if(strlen($zone->channels) == 0) {
        return array();
    }
    $channels = implode(json_decode($zone->channels), ",");

    if($zone->th1 == 1)
        $order = "ASC";
    else
        $order = "DESC";

    $req = $PDO->prepare('SELECT * FROM `speakers` WHERE id IN ('. $channels.') ORDER BY id '.$order);
    $req->execute();
    $speakers = $req->fetchAll();

    /* merge data from channel profiles to form a speaker object */
    if($zoneProfile == 0) {
        return $speakers;
    }

    $chanProfile = getChannelProfiles($zoneProfile);
    /* chanprofile is sorted by id, revert it needed */
    if($zone->th1 != 1) {
        $chanProfile = array_reverse($chanProfile);
    }
    /* sanity check : same length and index */
    if(count($speakers) != count($chanProfile)) {
        error_log("chanProfile and speakers count don't match");
        error_log(print_r($chanProfile, true));
        return $speakers;
    }
    if($speakers[0]->id != $chanProfile[0]->speakers_id) {
        error_log("chanProfile and speakers id don't match");
        error_log(print_r($chanProfile, true));
        return $speakers;
    }

    /* add property of chan profiles to speakers */
    foreach ($speakers as $idx => $sp) {
        $chan = $chanProfile[$idx];
        foreach($chan as $k => $v) {
            /* remove unneeded property */
            if(($k == 'speakers_id') || ($k == 'channel')) {
                continue;
            }
            $sp->$k = $v;
        }
        /* duplicate property for sub */
        if($sp->inputSignal == 23) {
            $sp->subLPFFreq = $sp->xoLpfFreq;
            $sp->subLPFType = $sp->xoLpfType;
            $sp->subHPFFreq = $sp->xoHpfFreq;
            $sp->subHPFType = $sp->xoHpfType;
        }
    }
    return $speakers;
}

function getAllSpeakers() {
    global $PDO;

    $max = (getNumberOfChanalByModel(getConfig('ProcessorModel'))-1);

    $req = $PDO->prepare('SELECT * FROM `speakers` WHERE `preset` != 99 AND channel<='.$max);
    $req->execute();
    return $req->fetchAll();
}

function getDiginMapping() {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `diginmap`');
    $req->execute();
   
    return $req->fetchAll();    
}

function createDiginPopUp(){  
    global $PDO;
    $req = $PDO->prepare('SELECT * FROM `diginmap`');
    $req-> execute();
    
    $processorModel = getConfig('ProcessorModel');
    $signals =  getNames('speakernameinput', $processorModel);
    $speakersNames = getNames('speakername', $processorModel);
    $tbody = '';
    
    $diginMap = $req->fetchAll();
    
    foreach($diginMap as $key => $data ){
        $tbody .= "<tr>";
        $tbody .= '<td>'.$data->id.'</td>';
        $tbody .= '<td>'.$signals[ intval($data->inputSignal) ].'</td>';
        $tbody .= '<td>'.$speakersNames[ intval($data->inputSignal) ].'</td>';
        $tbody .= '<td>';
        $tbody .= '<select name="select_speaker" onchange="onChange_selectSpeakers(this);">';
        foreach( $signals as $id => $value ){
            if($id < 28){
                $tbody .= '<option value="'.$id.'" '.(($data->inputSignal == $id) ? 'selected="selected"' : null).'> '.$value.' </option>';
            }
        }    
        $tbody .= '</select>';
        $tbody .= '</td>';
        $tbody .= '<td class="selected_signal_name">'.$speakersNames[intval($data->inputSignal)].'</td>';
        $tbody .= '<td><input class="writable" type="radio" name="check" '.(($data->sync == 1) ? 'checked="checked"' : null ).' '. (($data->inputSignal == 0) ? "disabled" : null).' /></td>';
        $tbody .= "</tr>";
    }

    return $tbody;
}


function getSpeakersTmp($config) {
    global $PDO;

    if($config == 0){
        $max = getNbChannels();
    }else{
        $max = 16;
    }

    $req = $PDO->prepare('SELECT * FROM `speakers_tmp` LIMIT 0,' . $max . '');
    $req->execute();
    return $req->fetchAll();
}

function getSpeakerName($id){
    global $PDO;

    $req = $PDO->prepare('SELECT name FROM `speakers` WHERE id='.$id);
    $req->execute();
    return $req->fetch();
}

function setSpeakers($config, $values, $profile, $temp=false) {
    global $PDO;
    $sptable = $temp ? 'speakers_tmp' : 'speakers';
    $proftable = $temp ? 'channel_profiles_tmp' : 'channel_profiles';
    $err = 0;

    foreach ($values as $id => $data) {
        /* build sql request UPDATE table SET `key1`=:key1, `key2`=:key2 ...*/
        $spkeys = array('name','enable', 'limiterEnable', 'limiterValue');
        $sql = 'UPDATE `'.$sptable.'` SET';
        foreach($spkeys as $key) {
            $sql = $sql.' `'.$key.'`=:'.$key.',';
        }
        /* remove last comma */
        $sql = substr($sql, 0 , -1);
        $sql = $sql .' WHERE `preset`="' . $config . '" AND id="' . $id . '"';
        $req = $PDO->prepare($sql);
        foreach ($data as $k => $v) {
            if(in_array($k, $spkeys)) {
                if($k != "name")
                    $req->bindValue(':' . $k, $v);
                else
                    $req->bindValue(':' . $k, strip_tags($v));
            }
        }

        if (!$req->execute()) {
            $msglog = 'query '.$req->queryString.' failed with parameters ';
            $msglog = $msglog.print_r($data, true);
            error_log($msglog);
            $err++;
        }

        /* new bm */
        $newbmkeys = array('bmXoType','bmXoFreq','bmXoHpfType','bmXoHpfFreq','delay','level','size',
                           'xoBand','xoLpfFreq','xoLpfType','xoHpfFreq',
                           'xoHpfType','delaymeter', 'withLFE', 'withSUB', 'phaseInverted', 'tiltEQ',
                           'eqBypass','bmSubHpfType','bmSubHpfFreq','bmBusNumber','bmTakeDelay');

        if($data['inputSignal'] == 23) {
            $data['xoLpfType'] = $data['subLPFType'];
            $data['xoLpfFreq'] = $data['subLPFFreq'];
            $data['xoHpfType'] = $data['subHPFType'];
            $data['xoHpfFreq'] = $data['subHPFFreq'];
        }

        $sql = 'UPDATE `'.$proftable.'` SET';
        foreach($newbmkeys as $key) {
            /* only update if key is present in data */
            if(array_key_exists($key, $data)) {
                $sql = $sql.' `'.$key.'`=:'.$key.',';
            } else {
                 error_log(__FUNCTION__.': '.$key.' missing data');
            }
        }
        $sql = substr($sql, 0 , -1);
        $sql = $sql .' WHERE `zone_profiles_id`=:profile AND speakers_id=:id';
        $req = $PDO->prepare($sql);
        foreach($newbmkeys as $key) {
            /* only update if key is present in data */
            if(array_key_exists($key, $data)) {
                $req->bindValue(':' . $key, $data[$key]);
            }
        }

        $req->bindValue(':profile', $profile);
        $req->bindValue(':id', $id);
        if (!$req->execute()) {
            $msglog = 'query '.$req->queryString.' failed with parameters ';
            $msglog = $msglog.print_r($data, true);
            error_log($msglog);
            $err++;
        }
    }

    return $err == 0;
}



function loadSpeakersTemp($zoneId, $zoneProfile){
    global $PDO;
    $speakers = getSpeakersZone($zoneId);

    $req = $PDO->prepare('TRUNCATE `speakers_tmp`');
    $req->execute();


    $req = $PDO->prepare('TRUNCATE `channel_profiles_tmp`');
    $req->execute();


    $zone = getZone($zoneId);
   
    $channels = implode(json_decode($zone->channels), ",");
    $req = $PDO->prepare('INSERT INTO speakers_tmp SELECT * from speakers WHERE'.
                         ' id IN('.$channels.')');
    $req->execute();


    $req = $PDO->prepare('INSERT INTO channel_profiles_tmp SELECT * from '.
                         'channel_profiles WHERE zone_profiles_id='.
                         $zoneProfile);
    $req->execute();
}

function getEQ($canal, $config, $zone) {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `eqs` WHERE `channel` = "' . $canal . '" AND `zone_profiles_id` = "' . $config . '" AND `zone_id` = "' . $zone . '" ORDER BY filternumber ASC LIMIT 0,10');
    $req->execute();
    return $req->fetchAll();
}

function getEQZone($config, $zone) {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `eqs` WHERE `zone_profiles_id` = "' . $config . '" AND `zone_id` = "' . $zone . '" ORDER BY id ASC');
    $req->execute();
    return $req->fetchAll();
}

function getAllEQs($config) {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `eqs` WHERE `preset` = "' . $config . '"');
    $req->execute();
    return $req->fetchAll();
}

function getAllEQTemp() {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `eqs_tmp`');
    $req->execute();
    return $req->fetchAll();
}

function getAllEQTempPerChannel($channel) {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `eqs_tmp` where channel ='.$channel.' order by filternumber ASC');
    $req->execute();
    return $req->fetchAll();
}

function loadEQTemp($canal, $config, $zone){
    global $PDO;
    
    $eqs = getEQZone($config, $zone);

    $req = $PDO->prepare('TRUNCATE `eqs_tmp`');
    $req->execute();

    foreach($eqs as $eq){
        $req = $PDO->prepare('INSERT INTO `eqs_tmp` VALUES(:id, :type, :frequency, :gain, :q, :bypass,:filternumber,:channel,:preset,:zone_id,:zone_profiles_id)');
        foreach ($eq as $k => $v) {
            $req->bindValue(':' . $k, $v);
        }
        $req->execute();
    }
}

function copyEQTempToReal($config) {
    global $PDO;

    $err = 0;

    $eqs = getAllEQTemp();
    $values = array();

    foreach($eqs as $v){
        $v = (array) $v;

        $canal = $v["channel"];
        $filternumber = $v["filternumber"];

        if(!isset($values[$canal]))
            $values[$canal] = array();

        unset($v['id']);
        unset($v['preset']);
        unset($v['channel']);
        unset($v['filternumber']);
        $values[$canal][$filternumber] = $v;
    }

    foreach($values as $canal => $filternumbers){
        foreach ($filternumbers as $filternumber => $datas) {
            $req = $PDO->prepare('UPDATE `eqs` SET `type`=:type, `frequency`=:frequency, `gain`=:gain, `q`=:q, `bypass`=:bypass WHERE `filternumber`="' . $filternumber . '" AND `preset`="' . $config . '" AND channel="' . $canal . '"');
            foreach ($datas as $k => $v) {
                $req->bindValue(':' . $k, $v);
            }
            if (!$req->execute()) {
                $err++;
            }
        }
    }

    return $err == 0;
}

function setEQ($canal, $config ,$zone, $values) {
    global $PDO;

    $err = 0;

    foreach ($values as $filternumber => $data) {
        $req = $PDO->prepare('UPDATE `eqs` SET `type`=:type, `frequency`=:frequency, `gain`=:gain, `q`=:q, `bypass`=:bypass WHERE `filternumber`="' . $filternumber . '" AND `channel`="' . $canal . '" AND `zone_profiles_id`="' . $config . '" AND `zone_id`="' . $zone . '"');
        foreach ($data as $k => $v) {
            $req->bindValue(':' . $k, $v);
        }

        if (!$req->execute()) {
            $err++;
        }
    }

    return $err == 0;
}

function setCopyEQ($values,$copy){
    global $PDO;

    $err = 0;

    foreach($copy as $cp){

        $ct = explode('-', $cp);
        /* copy to eqs */
        $req = $PDO->prepare('UPDATE `eqs` SET `type`=:type, `frequency`=:frequency, `gain`=:gain, `q`=:q, `bypass`=:bypass WHERE `filternumber`=:fnum AND `channel`="' . $ct[1] . '" AND `zone_profiles_id`="' . $ct[0] . '"');

        foreach ($values as $filternumber => $data) {
            $req->bindValue(':fnum', $filternumber);
            foreach ($data as $k => $v) {
                $req->bindValue(':' . $k, $v);
            }
            if (!$req->execute()) {
                $err++;
            }
        }
        /* copy to eqs_tmp */
        $req = $PDO->prepare('UPDATE `eqs_tmp` SET `type`=:type, `frequency`=:frequency, `gain`=:gain, `q`=:q, `bypass`=:bypass WHERE `filternumber`=:fnum AND `channel`="' . $ct[1] . '" AND `zone_profiles_id`="' . $ct[0] . '"');
        foreach ($values as $filternumber => $data) {
            $req->bindValue(':fnum', $filternumber);
            foreach ($data as $k => $v) {
                $req->bindValue(':' . $k, $v);
            }
            if (!$req->execute()) {
                $err++;
            }
        }
    }

    return $err == 0;
}

function setEQtemp($canal, $zone, $values) {
    global $PDO;

    $err = 0;

    foreach ($values as $filternumber => $data) {
        $req = $PDO->prepare('UPDATE `eqs_tmp` SET `type`=:type, `frequency`=:frequency, `gain`=:gain, `q`=:q, `bypass`=:bypass WHERE `filternumber`="' . $filternumber . '" AND `channel`="' . $canal . '" AND `zone_id`="' . $zone . '"');
        foreach ($data as $k => $v) {
            $req->bindValue(':' . $k, $v);
        }     
        if (!$req->execute()) {
            $err++;
        }
    }

    return $err == 0;
}

function getSpeakerNameDefault($config) {
    global $PDO;

    $data = array();

    $req = $PDO->prepare('SELECT n.numerotation as id,n.name FROM `speakers` as s,`names` as n WHERE s.preset="' . $config . '" AND s.name = n.numerotation AND description="speakername"');
    $req->execute();
    foreach ($req->fetchAll() as $v) {
        $data[$v->id] = $v->name;
    }
    return $data;
}

function setTestPattern($values, $circulartiming) {
    global $PDO;

    $err = 0;

    $activeSpeaker = getState('ActiveSpeaker');

    foreach ($values as $id => $data) {
        $req = $PDO->prepare('UPDATE `speakers` SET `genOrder`=:order, `genEnable`=:selected WHERE `id`=' . $id);
        $req2 = $PDO->prepare('UPDATE `speakers_tmp` SET `genOrder`=:order, `genEnable`=:selected WHERE `id`=' . $id);

        foreach ($data as $k => $v) {
            $req->bindValue(':' . $k, $v);
            $req2->bindValue(':' . $k, $v);
        }

        if (!$req->execute()) {
            $err++;
        }
        
        if (!$req2->execute()) {
            $err++;
        }
    }

    return $err == 0;
}

function setTestDiracPattern($values) {
    global $PDO;

    $err = 0;

    $activeSpeaker = getState('ActiveSpeaker');

    foreach ($values as $id => $data) {
        $req = $PDO->prepare('UPDATE `speakers` SET `diracOrder`=:order WHERE `id`=' . $id);
        $req2 = $PDO->prepare('UPDATE `speakers_tmp` SET `diracOrder`=:order WHERE `id`=' . $id);


        foreach ($data as $k => $v) {
            $req->bindValue(':' . $k, $v);
            $req2->bindValue(':' . $k, $v);
        }

        if (!$req->execute()) {
            $err++;
        }

        if (!$req2->execute()) {
            $err++;
        }

    }

    return $err == 0;
}

function getNbOfFreeChannels($isAlternate = 0) {
    global $PDO;

    if($isAlternate == 0){
        $id = getNbChannels();
    }else{
        $id = 48;
    }

    $req = $PDO->prepare('SELECT COUNT(id) FROM `speakers` WHERE inputSignal=0 AND preset='.$isAlternate.' AND id<='.$id.' ORDER BY id DESC');
    $req->execute();
    $free = current($req->fetch());
    
    return $free;
}

function getSpeakersPresets() {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `speakerspresets` WHERE id < 1000 ORDER BY name ASC ');
    $req->execute();
    $datas = $req->fetchAll();

    $req = $PDO->prepare('SELECT * FROM `speakerspresets` WHERE id > 1001 AND id < 2000 ORDER BY name ASC ');
    $req->execute();
    $tbm2000 = $req->fetchAll();

    $idss = array();

    foreach ($datas as $l) {
        $idss[] = $l->id;
    }

    // add x.0.x.x preset if x.1.x.x is not in the list (not enough channels available)
    foreach ($tbm2000 as $l2) {
        $n = $l2->id - 1000;
        if($l2->id == 1002){ // preset with id 1002 is the 2.1.0.0 without sub
            // special case for 2.0.0.0 config, when only 2 channels remaining
            // avoid having 2.0.0.0 config twice, the real 2.0.0.0 theater and the 2.1.0.0 without sub
            $n = 1;
        }
        if(!in_array($n, $idss)){
            $datas[] = $l2;
        }
    }

    return $datas;
}

function getSpeakersPreset($id) {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `speakerspresets` WHERE id='. $id);
    $req->execute();
    return $req->fetch(PDO::FETCH_ASSOC);
}

function getZone($id) {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `zones` WHERE id='. $id.' ORDER BY th1 DESC, id ASC');
    $req->execute();
    return $req->fetch();
}

function getZones($type=2002) {
    global $PDO;

    if($type != 2002)
        $req = $PDO->prepare('SELECT * FROM `zones` WHERE type='. $type.' ORDER BY th1 DESC, id ASC');
    else
        $req = $PDO->prepare('SELECT * FROM `zones` ORDER BY th1 DESC, id ASC');
    $req->execute();
    return $req->fetchAll();
}

function setZoneP($key,$value,$id) {
    global $PDO;

    $err = 0;

    if($key == "" || $id == "" || $value == ""){
        return false;
    }

    $req = $PDO->prepare('UPDATE `zones` SET `'.$key.'`=:value  WHERE id="' . $id . '"');
    $req->bindValue(':value', $value);

    if (!$req->execute()) {
        $err++;
    }

    return $err == 0;
}

function setAVZones($values) {
    global $PDO;

    $err = 0;

    foreach ($values as $id => $value) {
        $req = $PDO->prepare('UPDATE `zones` SET `delay`=:delay  WHERE id="' . $id . '"');
        $req->bindValue(':delay', $value);

        if (!$req->execute()) {
            $err++;
        }
    }

    checkLipSync();

    return $err == 0;
}

function getCopyEQ(){

    $copy = array();

    $eqPresets = getAllEqsPresets();

    foreach($eqPresets as $eqpreset){
        $sps = array();
        $speakers = getSpeakersZone($eqpreset->zone_id);

        foreach($speakers as $speaker){
            $sps[$speaker->id] = $speaker->name;
        }

        $copy[] = array(
            "zoneId" => $eqpreset->zone_id,
            "eqPid" => $eqpreset->id,
            "eqPname" => $eqpreset->name,
            "channels" => $sps,
            "goldenProfile" => $eqpreset->goldenProfile
        );
    }

    return $copy;
}

function getTheaters($IsSphereAudioReturned=false){
    global $PDO;

    if($IsSphereAudioReturned == false)
        $req = $PDO->prepare('SELECT * FROM `zones` WHERE layout<2000 AND layout>0 ORDER BY th1 DESC, id ASC');
    else
        $req = $PDO->prepare('SELECT * FROM `zones` WHERE (layout<2000 AND layout>0) OR (layout=3000) ORDER BY th1 DESC, id ASC');

    $req->execute();
    return $req->fetchAll();
}

function getAudioZones($type=2002){
    global $PDO;

    if($type != 2002)
        $req = $PDO->prepare('SELECT * FROM `zones` WHERE layout>=2000 AND type='. $type);
    else
        $req = $PDO->prepare('SELECT * FROM `zones` WHERE layout>=2000 AND layout<3000');
    $req->execute();
    return $req->fetchAll();
}

function getSphereAudioZones(){
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `zones` WHERE layout=3000');
    $req->execute();

    return $req->fetchAll();
}

function isSphereAudioZones($zoneId){
    global $PDO;
    
    $req = $PDO->prepare('SELECT layout FROM `zones` WHERE `id` = "' . $zoneId. '" AND layout=3000');
    $req->execute();
    
    $data = $req->fetch();
    
    if(empty($data)) {
        return false;
    }
    else {
        return true;
    }
    
    return false;
}

function isIISP() {
    if(getVersion("h_IISP_XLR4") != "None") {
        return true;
    }
    return false;
}

function getAllEqsPresets(){
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `zone_profiles`');
    $req->execute();
    return $req->fetchAll();
}

function getEqsPresets($zone){
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `zone_profiles` WHERE zone_id='.$zone);
    $req->execute();
    return $req->fetchAll();
}

function isGoldenProfile($id){
    global $PDO;

    $req = $PDO->prepare('SELECT goldenProfile FROM `zone_profiles` WHERE `id` = "' . $id . '"');
    $req->execute();
    return (current($req->fetch()) != 0);
}

function getDownmixAVStatus(){
    global $PDO;

    $req = $PDO->prepare('SELECT avzone FROM `zones` WHERE layout=2000');
    $req->execute();
    return current($req->fetch());
}

function setDownmixAVStatus($state){
    global $PDO;

    $req = $PDO->prepare('UPDATE `zones` set avzone=:avzone WHERE layout=2000');
    $req->bindValue(':avzone', $state);
    $req->execute();
    return true;
}

function getLayoutNames(){
    global $PDO;

    $req = $PDO->prepare('SELECT id,name FROM `speakerspresets`');
    $req->execute();
    $d = $req->fetchAll();

    $data = array(
        0 => "None"
     );

    foreach($d as $v){
        $data[$v->id] = $v->name;
    }

    $data[2001] = "Mono";
    $data[2002] = "Stereo";
    $data[2003] = "Headphone";

    return $data;
}

function getInput($id) {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `inputs` WHERE `id` = "' . $id . '"');
    $req->execute();

    return $req->fetch();
}

function getCurrentMainInput() {
    return getInput(getState('Input'));
}

function getPassword($login) {
    $key = ucfirst($login . 'Password');
    return getConfig($key);
}

function getNumberOfChanalByModel($id) {
    $models = array(
        0 => 11,
        1 => 12,
        2 => 32,
        3 => 16
    );

    return $models[$id];
}

function setInstallerPsw($datas) {
    $prev = md5(trim($datas['prev']));
    $new = trim($datas['newPsw']);
    $confirm = trim($datas['confirm']);
    $error = "";
    //check previous
    if ($prev != getConfig('InstallerPassword')) {
        $error .= "<br>Bad previous password";
    } elseif ($new == '' || $confirm == '') {
        $error .= "<br>The new password must not be empty";
    } elseif ($new == $confirm) { //check new password
        if (!setConfig('InstallerPassword', md5($new))) {
            $error .= '<br>Error while saving new password';
        }
    } else {
        $error .= "<br>The password confirmation doesn't match with the new password";
    }

    echo $error;
}

function refreshStatusbar(){
    $states = getAllState();

    $preset = getPreset($states["Preset"]);

    $currentMainInput = getCurrentMainInput();


    $states["SpeakerList"] = getTheaters(true);
    $states["EqList"] = getAllEqsPresets();
    $states["PresetList"] = getNamesTable("presets");
    $states["MainInputList"] = array();
    $states["ZonesList"] = array();

    $states["v_ISPCU_GLOBAL"] = getVersion('v_ISPCU_GLOBAL');

    $states["ActiveSurroundMode"] = getStateTmp('ActiveSurroundMode');

    $states["Connected"] = 0;
    if (isset($_SESSION['type']))
        $states["Connected"] = 1;

    foreach (getInputs('main') as $input) {
        $states["MainInputList"][$input->id] = array( 'id' => $input->id ,'name' => $input->name, 'active'=> $input->active);
    }

    $zonesDS = getZones();
    $zoneNS = array();

    foreach($zonesDS as $z){
        $zoneNS[$z->id] = $z;
    }

    foreach(getConfigs() as $presetS) {
        if($presetS->zones != "0" && $presetS->zones != "") {
            $zonesS = json_decode($presetS->zones);
            if(is_array($zonesS)) {
                foreach ($zonesS as $zoneS) {
                    $states["ZonesList"][] = array(
                        'preset' => $presetS->id,
                        'zone' => $zoneS,
                        'name' => $zoneNS[$zoneS]->name
                    );
                }
            }
        }
    }

    $states["Power"] = getConfig('Power');

    $states["SurroundMode"]=$states["SurroundMode"];
    $states["InputStream_LONG"]=str_replace('<br>',' ', getStateTmp("InputStream_LONG"));

    $triggers = getTriggers();

    foreach($triggers as $trigger){
        if($trigger->manual == 0){
            $key = "Trigger" . $trigger->id;
            $states[$key] = 2;
        }
    }
    
    $states["licenseMonitoring"] = isLicenseMonitoring();
    $states["licenseSphereAudio"] = isLicenseSphereAudio();
    $states["licenseUHQ"] = isLicenseUHQ();
    
    $states['audioIn'] = $currentMainInput->audioIn;
    
    $states['AesThroughApmEnable'] = getConfig('AesThroughApmEnable');

    return $states;
}

function refreshRemote($keyList){
    $dataList = array();
    foreach ($keyList as $key) {
        if($key == "Thermal" || $key == "Power") {
            $dataList[$key] = getConfig($key);
        }
        elseif($key != "InputStream_LONG" && $key != "Thermal" && $key != "ActiveSurroundMode" && $key != "MsgStatusTXT" && $key != "DtsDialogAvailable" && $key != "ImmersiveMode") {
            $dataList[$key] = getState($key);
        }
        else
            $dataList[$key] = getStateTmp($key);
    }

    $preset = getPreset($dataList["Preset"]);

    $dataList["Mute"] = getState('Mute');

    $speakers = getSpeakers($dataList["ActiveSpeaker"]);

    $dataList["SpeakerHeight"] = 0;

    foreach($speakers as $v){
        if($v->inputSignal >= 512)
            $dataList["SpeakerHeight"] = $dataList["SpeakerHeight"]+1;
    }

    if($dataList["SpeakerHeight"] > 1) {
        $dataList["SpeakerHeight"] = 1;
    }

    $dataList["MainAudioInName"] = getNamesWhereNum('audioinputname', getCurrentMainInput()->audioIn)->name;

    return $dataList;
}

function refreshRemotePower(){
    return getConfig('Power');
}

function setName($type, $id, $value){

    global $PDO;

    $req = $PDO->prepare('UPDATE `names` SET `name`="' . strip_tags($value) . '" WHERE `numerotation`=' . $id . ' AND `description`="' . $type . '"');
    return $req->execute();
}

function getGroup($zone){

    global $PDO;

    $zone = getZone($zone);

    $channels = implode(json_decode($zone->channels), ",");

    if($zone->th1 == 1)
        $order = "ASC";
    else
        $order = "DESC";

    $req = $PDO->prepare('SELECT DISTINCT(genOrder) FROM `speakers` WHERE inputSignal != 0 AND enable=1 AND genEnable=1 AND id IN ('. $channels.') ORDER BY genOrder ASC');
    $req->execute();
    return $req->fetchAll();

}

function getGroupOffset($zone) {
    $zone = getZone($zone);
    $chan_ids = array_map('intval', json_decode($zone->channels));
    $groupMin = min($chan_ids) - 1;
    return $groupMin;
}

function getPreset($id) {
    global $PDO;

    $req = $PDO->prepare('SELECT * FROM `presets` WHERE id = '.$id);
    $req->execute();
    return $req->fetch(PDO::FETCH_ASSOC);
}

function deletePreset($id) {
    global $PDO;

    $req = $PDO->prepare('DELETE FROM `presets` WHERE id = '.$id);
    return $req->execute();
}

function createPreset($zoneId=NULL, $profileId=NULL) {
    global $PDO;

    if($zoneId == NULL) {
        $req = $PDO->prepare("INSERT INTO `presets` (name,active,zones) VALUES ('None', '1' ,'[1]')");
        $req->execute();
    } else {
        $zone = getZone($zoneId);
        $req = $PDO->prepare("INSERT INTO `presets` (name,active,speaker,eq,zones) VALUES (:name,'1',:speaker,:profile,'[\"1\"]')");
        $req->bindValue(':name',$zone->name);
        $req->bindValue(':speaker',$zoneId);
        $req->bindValue(':profile',$profileId);
        $req->execute();
    }
    return $PDO->lastInsertId();
}

function reloadSpeakers($forceRefresh=0){
    global $PDO;

    if((getState('CustomPreset') == 0) || $forceRefresh == 1){

        $currentPreset = getState('Preset');
        $preset = getPreset($currentPreset);

        setState('ActiveSpeaker',$preset["speaker"]);
        setState('ActiveProfile',$preset["eq"]);
    }

    $req = $PDO->prepare('TRUNCATE `speakers_tmp`');
    $req->execute();
    $req = $PDO->prepare('TRUNCATE `channel_profiles_tmp`');
    $req->execute();
}

function reloadEqualizers(){
    global $PDO;

    if(getState('CustomPreset') == 0){

        $currentPreset = getState('Preset');
        $preset = getPreset($currentPreset);

        setState('ActiveSpeaker',$preset["speaker"]);
        setState('ActiveProfile',$preset["eq"]);

    }

    $req = $PDO->prepare('TRUNCATE `eqs_tmp`');
    $req->execute();
}

function getEQPName($id) {
    global $PDO;

    $req = $PDO->prepare('SELECT name FROM `zone_profiles` WHERE id='.$id);
    $req->execute();
    return current($req->fetch());
}

function checkLipSync(){
    global $PDO;

    $av = getCurrentAV();
    $input = getCurrentMainInput();
    $input = $input->delay;

    $req = $PDO->prepare('SELECT * FROM `zones` WHERE avzone=1 AND layout >=2000 ORDER BY th1 DESC, id ASC');
    $req->execute();
    $zs = $req->fetchAll();

    foreach($zs as $z){
        $minlipsync = -($z->delay + $input);
        if($z->lipsync < $minlipsync)
            setZoneP("lipsync",$minlipsync,$z->id);
    }

    $minlipsync = -($av + $input);

    if(getState("LipSync") < $minlipsync)
        setState('LipSync', $minlipsync);

}

function getCurrentAV(){
    global $PDO;

    $id = getState('ActiveSpeaker');

    $req = $PDO->prepare('SELECT delay FROM zones WHERE id="' . $id . '"');
    $req->execute();
    $data = $req->fetch();

    if(empty($data))
        $data = 0;
    else
        $data = current($data);

    return $data;
}

function messageBox($msg)
{
    echo '<script language="javascript">';
    echo 'alert("' . $msg. '");';
    echo 'history.back();';
    echo '</script>';
}

function execCmd($cmd)
{
    exec($cmd, $output, $status);
    if($status !==0){
        error_log(implode('\n',$output), 0);
        return FALSE;
    }
    return TRUE;
}

/* create non conflicting name for zone */
function getFreeZoneName($BaseName, $Suffix="", $index=0)
{
    global $PDO;

    $i = $index;

    do
    {
        $i++;
        if($Suffix == "") {
            $req = $PDO->prepare('SELECT id FROM `zones` WHERE name="'. $BaseName. $i. '"' );
        }
        else {
            $req = $PDO->prepare('SELECT id FROM `zones` WHERE name="'. $BaseName. $i. $Suffix. '"' );
        }
        $req->execute();
        $res = $req->fetchAll();

    } while (count($res) != 0);


    return ($BaseName. $i. $Suffix);
}

function getFreeEQName($zoneId, $BaseName='New Profile ', $index=0)
{
    global $PDO;

    $i = $index;

    do
    {
        $i++;

        $req = $PDO->prepare('SELECT id FROM `zone_profiles` WHERE name="'. $BaseName. $i. '" AND zone_id="'. $zoneId. '"');
        $req->execute();
        $res = $req->fetchAll();

    } while (count($res) != 0);


    return ($BaseName. $i);
}

function createZoneProfile($zoneId)
{
    global $PDO;
    /* create name */
    $zone = getZone($zoneId);
    $layout = $zone->layout;
    // a single profile is available for zone. Name it after the zone name
    if($layout >= 2000) {
        $name=$zone->name;
    } else {
        $name = getFreeEQName($zoneId);
    }

    /* get channel list */
    $chlist = json_decode($zone->channels);

    /* create profile and get id */
    $req=$PDO->prepare('INSERT INTO zone_profiles(`name`,`zone_id`) VALUES(:name,:id)');
    $req->bindValue(':name', $name);
    $req->bindValue(':id', $zoneId);
    $req->execute();
    $profileId = $PDO->lastInsertId();

    /* get speakers id and channel id and loop */
    $chlist = "(".implode($chlist,",").")";
    $req = $PDO->prepare('SELECT id,channel,inputSignal,altSignal,dolbyenable FROM speakers WHERE id in '.$chlist);
    $req->execute();
    $spentrys = $req->fetchAll();
    $hasSubs = false;

    /* first pass : check sub presence */
    foreach($spentrys as $spentry) {
        if($spentry->inputSignal == 23) {
            $hasSubs = true;
        }
    }
    
    foreach($spentrys as $spentry) {
        /* create chprofile */
        $xoLpfType = 0;
        $xoLpfFreq = 0;
        $xoHpfType = 0;
        $xoHpfFreq = 0;
        if($spentry->inputSignal == 23) {
            $xoLpfType = 0;
            $xoLpfFreq = 120;
            $xoHpfType = 5;
            $xoHpfFreq = 10;
        }
        $size = 1;
        $withLFE = 201;
        if(!$hasSubs){
            $size=2;
            if((($spentry->inputSignal == 1) || ($spentry->inputSignal == 2)) &&
               ($zone->parentZone == 0)){
                $withLFE=50;
            }
        }
        if($spentry->dolbyenable > 0) {
            $size=1;
            $bmXoFreq=80;
        } else {
            $bmXoFreq=80;
        }

        $sql = 'INSERT INTO channel_profiles (channel, speakers_id, size,'
              .'zone_profiles_id,bmXoFreq,xoLpfType, xoLpfFreq, withLFE,xoHpfType,'
              .'xoHpfFreq) VALUES ('
              .' :channel,:spid,:size,:profileId,:bmxofreq,:xoLType,:xoLFreq,:withLFE'
              .',:xoHType,:xoHFreq)';
        $req = $PDO->prepare($sql);
        $req->bindValue(':channel',$spentry->channel);
        $req->bindValue(':spid',$spentry->id);
        $req->bindValue(':profileId',$profileId);
        $req->bindValue(':bmxofreq', $bmXoFreq);
        $req->bindValue(':xoLType', $xoLpfType);
        $req->bindValue(':xoLFreq', $xoLpfFreq);
        $req->bindValue(':size', $size);
        $req->bindValue(':withLFE', $withLFE);
        $req->bindValue(':xoHType', $xoHpfType);
        $req->bindValue(':xoHFreq', $xoHpfFreq);
        $req->execute();

        /* create parameq */
        $req = $PDO->prepare("INSERT INTO `eqs` (type,frequency,gain,q,bypass,filternumber,channel,preset,zone_id,zone_profiles_id) ".
                             "VALUES (0,1000,384,1,0,:filternumber,:ch,0,:zone,:profileId)");

        for($filternumber=0; $filternumber<20;$filternumber++){
            $req->bindValue(':filternumber', $filternumber);
            $req->bindValue(':ch', $spentry->id);
            $req->bindValue(':zone', $zoneId);
            $req->bindValue(':profileId', $profileId);
            $req->execute();
        }
    }
    return $profileId;
}

/* this clones the preset and the associated eq in the eq table */
function cloneZoneProfile($profileId,$keepBmFlag = false)
{
    global $PDO;
    $req = $PDO->prepare('SELECT * from zone_profiles WHERE id='.$profileId);
    $req->execute();
    $res = $req->fetch();
    $currentProfile = $res;
    $name = $res->name;
    $zoneId = $res->zone_id;
    $slot = $res->diracSlot;

    // create new Name
    $name = getFreeEQName($res->zone_id);

    //now new  eqpreset

    $req=$PDO->prepare('INSERT INTO zone_profiles(`name`,`zone_id`,'.
                       'roomSize,curveType,unit,globalGain,manualBm,oldBmFilter,bmBusCount) '.
                       'VALUES(:name,:id,:room,:curve,:unit,:gain,:bm,:oldBm,:bmBusCount)');
    $req->bindValue(':name', $name);
    $req->bindValue(':id', $zoneId);
    $req->bindValue(':room', $currentProfile->roomSize);
    $req->bindValue(':curve', $currentProfile->curveType);
    $req->bindValue(':unit', $currentProfile->unit);
    $req->bindValue(':gain', $currentProfile->globalGain);
    $req->bindValue(':bm', $currentProfile->manualBm);
    $req->bindValue(':bmBusCount', $currentProfile->bmBusCount);
    if($keepBmFlag) {
        $req->bindValue(':oldBm', $currentProfile->oldBmFilter);
    } else {
        $req->bindValue(':oldBm', 0);
    }
    $req->execute();
    $newPreset = $PDO->lastInsertId();

    //now clone eq table
    $columns = 'type, frequency, gain, q, bypass, filternumber, channel,'.
               ' preset,zone_id';
    $inserted = $columns . ',zone_profiles_id';
    $copied = $columns .' ,:newpreset';


    $sql = 'INSERT INTO eqs ('.$inserted.')  SELECT '.$copied.
           ' FROM eqs WHERE zone_profiles_id=:oldpreset';
    $req = $PDO->prepare($sql);
    $req->bindValue(':newpreset', $newPreset);
    $req->bindValue(':oldpreset', $profileId);
    if(!$req->execute())
        error_log($sql);

    /* now clone machine_profiles */
    $sql = "CREATE TEMPORARY TABLE chcopy LIKE channel_profiles";
    $PDO->exec($sql);
    $sql = 'INSERT INTO chcopy SELECT * FROM channel_profiles '.
           'WHERE zone_profiles_id=:oldpreset';
    $req =  $PDO->prepare($sql);
    $req->bindValue(':oldpreset', $profileId);
    if(!$req->execute()) {
        error_log($sql);
        return 0;
    }
    // Update profile id
    $sql = 'UPDATE chcopy SET zone_profiles_id=:newid';
    $req =  $PDO->prepare($sql);
    $req->bindValue(':newid', $newPreset);
    if(!$req->execute()) {
        error_log($sql);
        return 0;
    }

    //drop id column
    $PDO->exec('ALTER TABLE chcopy DROP id');
    $PDO->exec('INSERT INTO channel_profiles SELECT 0,chcopy.* FROM chcopy');
    $PDO->exec('DROP TABLE chcopy');
    
    /* update dirac Slot in zone_profiles */
    $req=$PDO->prepare('UPDATE zone_profiles SET diracSlot=:slot WHERE id=:newPreset');
    $req->bindValue(':newPreset', $newPreset);
    $req->bindValue(':slot', $slot);
    $req->execute();

    return $newPreset;
}

function deleteZoneProfile($zoneId, $profileId)
{
    global $PDO;

    $req = $PDO->prepare('DELETE FROM `channel_profiles` WHERE zone_profiles_id=:pId');
    $req->bindValue(':pId',$profileId);
    $req->execute();

    $req = $PDO->prepare('DELETE FROM `eqs` WHERE zone_profiles_id=:pId');
    $req->bindValue(':pId',$profileId);
    $req->execute();

    $req = $PDO->prepare('DELETE FROM `zone_profiles` WHERE id=:pId');
    $req->bindValue(':pId',$profileId);
    $req->execute();

    $req = $PDO->prepare('UPDATE `presets` set eq=0 WHERE eq=:pId');
    $req->bindValue(':pId',$profileId);
    $req->execute();

    $req = $PDO->prepare('SELECT id FROM `zone_profiles` WHERE zone_id=:zId LIMIT 1' );
    $req->bindValue(':zId',$zoneId);
    $req->execute();
    $res = $req->fetch();

    return !empty($res) ? $res->id : 0;
}


function getZoneProfile($profileId)
{
    global $PDO;
    $req = $PDO->prepare('SELECT * from zone_profiles WHERE id='.$profileId);
    $req->execute();
    $res = $req->fetch();
    return $res;
}

function getZoneProfiles() {
    return getAllEqsPresets();

}

/**
 * check if DiracLive Bass Management => manualBm = 2
 */
function getDiracLiveBassManagement($currentProfileId){

    global $PDO;
    $req = $PDO->prepare('SELECT * from `zone_profiles` where manualBm=2 and id=:zId');
    $req->bindValue(':zId',$currentProfileId);
    $req->execute();
    $res = $req->fetchAll();
    return $res;
}

function saveZoneProfile($preset)
{
    global $PDO;
    $req = $PDO->prepare('UPDATE zone_profiles SET name=:eqname,'.
                         'roomsize=:room, curvetype=:curve WHERE id=:eqid');
    $req->bindValue(':eqname',$preset->eqName);
    $req->bindValue(':eqid',$preset->eqId);
    $req->bindValue(':room', $preset->roomSize);
    $req->bindValue(':curve', $preset->curveType);
    $req->execute();
    return true;
}

function setZoneProfileUnit($pId, $pUnit)
{
    global $PDO;
    $req = $PDO->prepare('UPDATE zone_profiles SET unit=:p_unit WHERE id=:p_id');
    $req->bindValue(':p_id', $pId);
    $req->bindValue(':p_unit', $pUnit);
    $req->execute();
    return true;
}

function saveZoneProfileParams($pId, $pName, $gain, $manualBm)
{
    global $PDO;
    if($pName == NULL) {
        $req = $PDO->prepare('UPDATE zone_profiles SET globalGain=:gain, '.
                             'manualBm=:bm WHERE id=:eqid');
    } else {
        $req = $PDO->prepare('UPDATE zone_profiles SET name=:eqname,'.
                             'globalGain=:gain, manualBm=:bm WHERE id=:eqid');
        $req->bindValue(':eqname',$pName);

    }
    $req->bindValue(':gain',$gain);
    $req->bindValue(':eqid',$pId);
    $req->bindValue(':bm',$manualBm);
    $req->execute();
    return true;
}

function getChannelProfiles($profileId)
{
    global $PDO;
    $req = $PDO->prepare('SELECT channel, diracLevel,level, diracDelay,'.
                         'delay, speakers_id, bmXoType, bmXoFreq,bmXoHpfType,'.
                         'bmXoHpfFreq, size, xoBand,'.
                         'xoLpfFreq, xoLpfType, xoHpfFreq, xoHpfType,delaymeter'.
                         ',withLFE,withSUB,phaseInverted,tiltEQ,eqBypass'.
                         ' FROM channel_profiles'.
                         ' WHERE zone_profiles_id='.$profileId.' ORDER BY speakers_id');
    if(! $req->execute()) {
        error_log($req->queryString);
    }
    return $req->fetchAll();
}

function getFreeDiracSlots($zoneId)
{
    global $PDO;

    $zone = getZone($zoneId);
    $splist = implode(json_decode($zone->channels), ",");
    $freeSlots = [];
    $req = $PDO->prepare("SELECT id from channel_profiles WHERE speakers_id IN (".
                         $splist .") AND diracSlot=:slot");

    for($slot=1; $slot < 11; $slot++) {
        $realSlot = $slot;
        if($zone->type == 1) {
            $realSlot = $slot + 10;
        }
        $req->bindValue(':slot', $realSlot);
        $req->execute();
        if($req->rowCount() == 0) {
            array_push($freeSlots,$slot);
        }
    }
    return $freeSlots;
}

function hasFreeDiracSlot($zoneId)
{
    if(count(getFreeDiracSlots($zoneId)))
        return true;
    return false;
}

function mtimePath($path)
{
    $path = $path.'?'.filemtime($path);
    return $path;
}

function saveProfileName($pId, $pName)
{
    global $PDO;
    $req = $PDO->prepare('UPDATE zone_profiles SET name=:B_pName WHERE id=:B_pId');
    $req->bindValue(':B_pName',$pName);
    $req->bindValue(':B_pId',$pId);
    $req->execute();
    return true;
}

// reset tone controls, but set eq to one for zone
function resetAllToneControl($zoneId)
{
    global $PDO;
    $req = $PDO->prepare('UPDATE states SET value=0 WHERE `key` in ('.
                         '"SurroundEnhance","CenterEnhance","SubEnhance",'.
                         '"Bass","Treble","Loudness","ReEQ","Brightness")');
    $req->execute();
    $req = $PDO->prepare('UPDATE zones SET eq=1, bass=0, treble=0, balance=0 '.
                         'WHERE id='.$zoneId);
    $req->execute();
}

function getActiveUpmix()
{
    global $upmixMode;

    $ActiveSurroundMode = getStateTmp('ActiveSurroundMode');

    return $upmixMode[$ActiveSurroundMode];
}

function getNbChannels()
{
    if(getVersion("h_ISP_DAC16_2") != "None" ||
       getVersion("h_ISP_DAC16_V2_2") != "None" ||
       getVersion("h_ISP_DIGOUT") != "None" ||
       getVersion("h_ISP_AVB") != "None") {
        $res = 32;
    } else if(getVersion("h_ISP_DAC_XLR4") != "None")
        $res = 20;
    else
        $res = 16;

    return $res;
}

function getMapping($isAlt)
{
    global $PDO;
    $resArray = array();
    $channelToZoneId = array();
    $index = 0;

    foreach(getZones() as $zone) {
        if($zone->parentZone == 0) { //Ignore subTheater
            $channels = json_decode($zone->channels);
            if(is_array($channels)){
                foreach($channels as $channel){
                    $channelToZoneId[$channel] = $zone->id;
                }
            }
        }
    }

    $req = $PDO->prepare('SELECT * FROM `speakers`');
    $req->execute();
    $allSpeakers = $req->fetchAll();

    if($isAlt == 1){
        $idStart = 33;
        $idEnd = 48;
    }else{
        $idStart = 1;
        $idEnd = getNbChannels();
    }
    foreach($allSpeakers as $speaker) {
        if(($speaker->id >= $idStart) && ($speaker->id <= $idEnd)) {
            $resArray[$speaker->id]['speakerId']= $speaker->id;

            if(is_numeric($speaker->name)) {
                $resArray[$speaker->id]['speakerName']= getNamesWhereNum('speakername', $speaker->name)->name;
            }
            else {
                $resArray[$speaker->id]['speakerName']= $speaker->name;
            }

            $resArray[$speaker->id]['output'] = $speaker->channel + 1;
            if(isset($channelToZoneId[$speaker->id])) {
                $resArray[$speaker->id]['zoneId'] = getZone($channelToZoneId[$speaker->id])->id;
                $resArray[$speaker->id]['zoneName'] = getZone($channelToZoneId[$speaker->id])->name;
            } else {
                $resArray[$speaker->id]['zoneId'] = 0;
                $resArray[$speaker->id]['zoneName'] = '';
            }
        }
    }

    return $resArray;
}

function setSpeakersChannels($channels)
{
    global $PDO;

    $decode = json_decode($channels, true);

    $configs = getConfigs();

    foreach ($decode as $row) {
        $req = $PDO->prepare('UPDATE speakers SET channel=:B_Channel WHERE id=:B_Id');
        $req->bindValue(':B_Channel', $row['value']);
        $req->bindValue(':B_Id', $row['id']);
        $req->execute();

        $req = $PDO->prepare('UPDATE channel_profiles SET channel=:B_Channel WHERE speakers_id=:B_Id');
        $req->bindValue(':B_Channel',$row['value']);
        $req->bindValue(':B_Id', $row['id']);
        $req->execute();

        // Remove zones with channel<16 from alt theaters presets
        if((intval($row['zoneId']) != 0) && (intval($row['value']) < 16)) {
            foreach($configs as $config) {
                $zone = getZone($config->speaker);
                if(intval($zone->type) == 1){    //if alternate
                    $configZones = json_decode($config->zones);
                    if(is_array($configZones)){
                        $newConfigs = array();
                        $changed = False;
                        foreach($configZones as $configZone){
                            if(intval($configZone) != intval($row['zoneId'])) {
                                $newConfigs[] = $configZone;
                            }
                            else {
                                $changed = True;
                            }
                        }

                        if($changed == True) {
                            //Set Preset zones
                            $req = $PDO->prepare('UPDATE `presets` SET zones=:zones WHERE `id`="' . $config->id. '"');
                            $req->bindValue(':zones', json_encode($newConfigs));
                            $req->execute();
                        }
                    }
                }
            }
        }
    }

    return true;
}

function rebranding()
{
    switch(getConfig("Brand")) {
        case 1: return 'bryston';
        case 2: return 'focal';
        default: return null; // no rebranding
    }
}

function getBrandName()
{
    switch(getConfig("Brand")) {
        case 1: return 'Bryston';
        case 2: return 'Focal';
        default: return 'StormAudio'; // no rebranding
    }
}

function getModel()
{
    if(rebranding() == "bryston") {
        return 'SP4';
    }
    elseif(rebranding() == "focal") {
        return 'Astral 16';
    }
    elseif (isIISP() == true) {
        return 'IISP';
    }
    else {
        return 'ISP ELITE';
    }

    return 'Unknown';
}

function getFullSerialNumberInfo($mdl,$sn){
    $serialTypeFocal = false;
    $model = array(
        0 => "ISP",
        1 => '2ISP' 
    );
    
    $fullSerialNumber="";

    if($mdl === 'SP4'){
        $fullSerialNumber .= $model[0];
    }elseif($mdl === 'ISP ELITE'){
        $fullSerialNumber .= $model[0];
    }elseif($mdl === 'IISP'){
        $fullSerialNumber .= $model[1];
    }elseif($mdl === 'Astral 16') {
        $serialTypeFocal = true;
    }


    if($serialTypeFocal === false) {
        // Legacy model have an integer serial (without N), but newer model
        // already have the N.
        if($sn[0] != 'N') {
            $fullSerialNumber .= "/N".$sn;
        } else {
            $fullSerialNumber .= "/".$sn;
        }
    } else {
        // We assume the whole serial number is set in EEPROM
        // including the A4BZV prefix
        $fullSerialNumber = $sn;
    }
    return $fullSerialNumber;
}

function isLicenseSphereAudio()
{
    global $PDO;
    
    $req = $PDO->prepare('SELECT `value` FROM `states_tmp` WHERE `key` = "LicenseSphereaudio"');
    $req->execute();
    return (current($req->fetch()) != 0);
}

function isLicenseMonitoring()
{
    global $PDO;
    
    $req = $PDO->prepare('SELECT `value` FROM `states_tmp` WHERE `key` = "LicenseMonitoring"');
    $req->execute();
    return (current($req->fetch()) != 0);
}

function isLicenseUHQ()
{
    global $PDO;
    
    $req = $PDO->prepare('SELECT `value` FROM `states_tmp` WHERE `key` = "LicenseUHQ"');
    $req->execute();
    return (current($req->fetch()) != 0);
}

function createZone($name, $layout, $channelList, $avzone, $isAlt)
{
    global $PDO;
    $mode = 0;
    $th1 = 0;
    if($layout == 2003) {
        $mode = 1;
    }
    $volume = getState('MasterVolume');

    if($layout < 2000) {
        $req = $PDO->prepare('SELECT id FROM `zones` WHERE th1=1 AND type='. $isAlt);
        $req->execute();
        $dz = $req->fetch();
        if($dz == "") {
            $th1 = 1;
        }
    }

    $req = $PDO->prepare("INSERT INTO zones (name,type,th1,layout,channels,enable,volume,avzone,mode) VALUES(:name,:type,:th1,:layout,:chs,1,:volume, :avzone, :mode)");

    $req->bindValue(':name', $name);
    $req->bindValue(':type', $isAlt);
    $req->bindValue(':th1', $th1);
    $req->bindValue(':layout', $layout);
    $req->bindValue(':chs', $channelList);
    $req->bindValue(':avzone', $avzone);
    $req->bindValue(':mode', $mode);
    $req->bindValue(':volume', $volume);
    $req->execute();

    return $PDO->lastInsertId();
}

function createChildZone($parentZone, $parentProfile, $channelArray,$keepDiracProfile)
{
    global $PDO;
    $zoneHasSub = false;
    $name = getFreeZoneName("SubTheater");
    $chList = array();
    foreach($channelArray as $id => $signal) {
        $req = $PDO->prepare('UPDATE speakers SET altSignal=:altSig WHERE `id`=:id');
        $req->bindValue(':altSig', $signal['altSig']);
        $req->bindValue(':id', $id);
        $req->execute();
        $chList[] = $id;
        if($signal['inputSig'] == 23) {
            $zoneHasSub = true;
        }
    }

    /* create copy of parent zone */
    $req = $PDO->prepare('CREATE TEMPORARY TABLE zonecopy SELECT * FROM zones where id=:id');
    $req->bindValue(':id',$parentZone);
    $req->execute();

    /* update names, layout and channel list and parentZone */
    $zoneChList ='["';
    $zoneChList .= implode('","', $chList);
    $zoneChList .='"]';
    $req = $PDO->prepare('UPDATE zonecopy SET id=0, name=:name, layout=38, channels=:channels, parentZone=:parentZone');
    $req->bindValue(':parentZone',$parentZone);
    $req->bindValue(':name',$name);
    $req->bindValue(':channels',$zoneChList);
    $req->execute();

    $req = $PDO->prepare('INSERT INTO zones SELECT * FROM zonecopy');
    $req->execute();
    $childZone = $PDO->lastInsertId();
    $req = $PDO->prepare('DROP TABLE zonecopy');
    $req->execute();

    /* clone parent profile */
    $childProfile  = cloneZoneProfile($parentProfile);

    /* adjust zone in cloned zone profile */
    if($keepDiracProfile == 'true'){
        // if "keep dirac profile" ticked, dirac must be kept for child zone
        $req = $PDO->prepare('UPDATE zone_profiles SET name="New Profile 1",zone_id=:zoneId,manualBm=0,goldenProfile=0 WHERE `id`=:id');
    } else{
        // dirac slot must be reset
        $req = $PDO->prepare('UPDATE zone_profiles SET name="New Profile 1",zone_id=:zoneId,manualBm=0,goldenProfile=0, diracSlot=0 WHERE `id`=:id');
    }
    $req->bindValue(':id',$childProfile);
    $req->bindValue(':zoneId',$childZone);
    $req->execute();

    /* adjust Name
    /* adjust zone in cloned eq */
    $req = $PDO->prepare('UPDATE eqs SET zone_id=:zoneId WHERE `zone_profiles_id`=:id');
    $req->bindValue(':id',$childProfile);
    $req->bindValue(':zoneId',$childZone);
    $req->execute();

    /* keep only the subzone channel in eq */
    $chSet = '(';
    $chSet .= implode(',',$chList);
    $chSet .= ')';
    $req = $PDO->prepare('DELETE FROM eqs WHERE zone_profiles_id=:id AND channel NOT IN '.$chSet);
    $req->bindValue(':id',$childProfile);
    $req->execute();

    /* keep only the subzone channel in channel_profiles */
    $req = $PDO->prepare('DELETE FROM channel_profiles WHERE zone_profiles_id=:id AND speakers_id NOT IN '.$chSet);
    $req->bindValue(':id',$childProfile);
    $req->execute();

    /* keep speakers size, but map LARGE_* to LARGE  */
    $req = $PDO->prepare('UPDATE channel_profiles SET size=2  WHERE size IN (0,4) AND zone_profiles_id=:zid');
    $req->bindValue(':zid',$childProfile );
    $req->execute();

    if($keepDiracProfile != 'true'){
        // dirac slot must be reset
        $req = $PDO->prepare('UPDATE channel_profiles SET diracLevel=0,diracDelay=0,coefLO=0,coefHI=0,diracSlot=0  WHERE zone_profiles_id=:zid');
        $req->bindValue(':zid',$childProfile );
        $req->execute();
    }
    $presetId = createPreset($childZone, $childProfile);
    setState('Preset', $presetId);
    return $childZone;
}

function getSpeakersLimiters() {
    global $PDO;

    $req = $PDO->prepare('SELECT limiterEnable,limiterValue FROM `speakers` WHERE `preset`=0 ORDER BY channel ASC');
    $req->execute();

    return $req->fetchAll();
}


// Set Ref volume => records current volume in the zone table
function setRefVolume($value,$key){
    global $PDO;

    $req = $PDO->prepare('UPDATE zones SET genRefVolume=:value WHERE id=:key');
    $req->bindValue(':value', $value);
    $req->bindValue(':key', $key);
    return $req->execute();
}
// Recall Ref volume => set master volume to the saved volume
function setMasterVolAtRefVolume($key){
    global $PDO;
    
    $req = $PDO->prepare('UPDATE states SET value = ( SELECT genRefVolume FROM ( SELECT * FROM zones) as grv WHERE `id` =:id ) WHERE `key` =:mVol');    
    $req->bindValue(':mVol','MasterVolume');
    $req->bindValue(':id', $key); 

    return $req->execute();   
}
// Get Ref volume
function getRefVolume($key){
    global $PDO;

    $req = $PDO->prepare('SELECT genRefVolume FROM zones WHERE id=:key');    
    $req->bindValue(':key', $key);
    $req->execute();
    return $req->fetch();
}

//bypass EQ
function setBypass($id,$bypass){
    global $PDO;
    
    $req = $PDO->prepare('UPDATE eqs_tmp SET bypass =:bypass  WHERE id =:id' );    
    $req->bindValue(':id',$id);    
    $req->bindValue(':bypass',$bypass);

    return $req->execute();
}


function saveEQ($canal, $zone, $values) {
    global $PDO;
    $err = 0;

    foreach ($values as $filternumber => $data) {
        
        $req = $PDO->prepare('UPDATE `eqs` SET `type`=:type, `frequency`=:frequency, `gain`=:gain, `q`=:q, `bypass`=:bypass , `filternumber`=:filternumber , `channel`=:channel, `zone_profiles_id`=:zone_profiles_id, `preset`=:preset, `zone_id`=:zone_id WHERE `id`=:id');
        
        foreach ($data as $k => $v) {
            $req->bindValue(':' . $k, $v);            
        }

        if (!$req->execute()) {
            $err++;
        }
    }

    return $err;
}

function saveEQ_tmp($canal, $zone, $values) {
    global $PDO;
    $err = 0;

    foreach ($values as $filternumber => $data) {
        
        $req = $PDO->prepare('UPDATE `eqs_tmp` SET `type`=:type, `frequency`=:frequency, `gain`=:gain, `q`=:q, `bypass`=:bypass , `filternumber`=:filternumber , `channel`=:channel, `zone_profiles_id`=:zone_profiles_id, `preset`=:preset, `zone_id`=:zone_id WHERE `id`=:id');
        
        foreach ($data as $k => $v) {           
            $req->bindValue(':' . $k, $v);
        }

        if (!$req->execute()) {
            $err++;
        }
    }

    return $err == 0;
}


function cleanEQsTmp(){
    global $PDO;
    $req = $PDO->prepare('TRUNCATE `eqs_tmp`');
    $req->execute();
}

// MUTE ALL FILTER FOR ONE CHANNEL
function muteEQSforOneChannel($zoneId,$canal,$bypass){
    global $PDO;    
    $req = $PDO->prepare('UPDATE `eqs` SET `bypass`=:bypass WHERE `zone_id`=:zone_id and `channel`=:canal');
    $req->bindValue(':zone_id',$zoneId);
    $req->bindValue(':canal',$canal);
    $req->bindValue(':bypass',$bypass);
    return $req->execute();
}

// get for one zone if all cells are bypass
function checkIfAllCellsIsmute($zoneId){
    // return
    /*
    +---------+-----+
    | channel | byp |
    +---------+-----+
    |   1     | 20  |
    |   2     | 0   |
    
    */ 
    global $PDO;    
    $req = $PDO->prepare("SELECT `channel`, SUM(bypass=1) as allByp FROM eqs WHERE `zone_id`=:zone_id group by channel");
    $req->bindValue(':zone_id',$zoneId);
    
    $req->execute();
    return $req->fetchAll();
}

function getEqsChBypass($canal){
    global $PDO;
    $req = $PDO->prepare("SELECT `eqBypass` FROM channel_profiles WHERE `speakers_id`=:channel");
    $req->bindValue(':channel',$canal);

    $req->execute();
    return $req->fetch();
}

function setEqsChBypass($canal,$bypass){
    global $PDO;
    $req = $PDO->prepare("UPDATE channel_profiles SET `eqBypass`=:bypass WHERE `speakers_id`=:channel");
    $req->bindValue(':channel',$canal);
    $req->bindValue(':bypass' , $bypass);
    return $req->execute();
}
