<?php

require_once '../includes/functions.inc.php';

if ($_POST['submit'] == 'importLicense') {
    if (!empty($_FILES['file']['name'])) {
        $file = $_FILES['file']['tmp_name'];
        $name = basename($_FILES['file']['name']);
        $cmd = 'sudo /root/ISP_CU/bin/ISP_CU_Install_license.sh '.$file;
        $cmd = $cmd.' '.$name;
        if(!execCmd($cmd)) {
            echo 'error';
            exit;
        }
        else {
            echo 'success';
        }

    } else {
        echo 'error';
    }
} elseif ($_POST ['submit'] == 'getLicenses') {
    $data = array (
        'SphereAudio'    => isLicenseSphereAudio(),
        'Monitoring'     => isLicenseMonitoring(),
        'UHQ'            => isLicenseUHQ(),
    );
    echo json_encode ( array (
        'result' => $data
    ) );
} elseif ($_POST ['submit'] == 'checkLicense') {
    if(isLicenseSphereAudio() == false) { //If there is no SphereAudio license
        $currPreset = getPreset(getState('Preset'));
        $currZone = getZone($currPreset['speaker']);
        
        if(intval($currZone->layout) == 3000) { //If the current preset is a SphereAudio preset
            $configs = getConfigsAPI();
            foreach($configs as $config) {  //Run throught active presets
                $zone = getZone($config->speaker);
                
                if (intval($zone->layout) < 2000) { //If it's a valid preset
                    setState('Preset', $config->id);
                    exit;
                }
            }
            // There is no valid preset
            setState('Preset', 0);
            exit;
        }
    }
}
