<?php
require_once '../includes/monitoring.inc.php';
require_once '../includes/functions.inc.php';

if ($_POST ['submit'] == 'getDevicesInfoFromRoom') {
    if (isset ( $_POST ["RoomId"] )) {
        echo json_encode ( array (
                'roomDevices' => getDevicesFromRoom ( $_POST ['RoomId'] ),
                'result' => getDevices ()
        ) );
    }
} elseif ($_POST ['submit'] == 'getCurrentValue') {
    echo json_encode ( array (
            'id' => $_POST ['id'],
            'dataName' => $_POST ['dataName'],
            'result' => getCurrentValue ( $_POST ['id'], $_POST ['dataName'] )
    ) );
} elseif ($_POST ['submit'] == 'getCurrentAvailableValues') {
    if (isset ( $_POST ["id"] )) {
        $values = array();
        foreach (getAvailableDatas ( $_POST ['id'] ) as $name) {
            if ($name !== null) {
                $values[$name] = getCurrentValue ( $_POST ['id'], $name );
            }
        }
        echo json_encode ( array (
                'id' => $_POST ['id'],
                'result' => $values
        ), JSON_FORCE_OBJECT );
    }
} elseif ($_POST ['submit'] == 'sendCmdChannelMute') {
    if (isset ( $_POST ["ipAddr"] ) && isset ( $_POST ["channel"] ) && isset ( $_POST ["value"] ) && isset ( $_POST ["id"] )) {
        echo json_encode ( array (
                'ipAddr' => $_POST ['ipAddr'],
                'id' => $_POST ['id'],
                'channel' => $_POST ['channel'],
                'value' => $_POST ['value'],
                'result' => sendCmdChannelMute ( $_POST ["ipAddr"], $_POST ["channel"], $_POST ["value"] )
        ) );
    }
} elseif ($_POST ['submit'] == 'getDeviceState') {
    if (isset ( $_POST ["id"] )) {
        echo json_encode ( array (
                'id' => $_POST ['id'],
                'result' => getDeviceState ( $_POST ["id"] )
        ) );
    }
} elseif ($_POST ['submit'] == 'setDevicesFromRoom') {
    if ((isset ( $_POST ["RoomId"] )) && (isset ( $_POST ["Value"] ))) {
        echo setDevicesFromRoom ( $_POST ['RoomId'], $_POST ["Value"] );
    }
} elseif ($_POST ['submit'] == 'setDeviceName') {
    if ((isset ( $_POST ["id"] )) && (isset ( $_POST ["name"] ))) {
        setDeviceName ( $_POST ['id'], $_POST ["name"] );
    }
} elseif ($_POST ['submit'] == 'sendCmdSetUnitSTB') {
    if (isset ( $_POST ["ipAddr"] ) && isset ( $_POST ["value"] )) {
        echo json_encode ( array (
                'ipAddr' => $_POST ['ipAddr'],
                'value' => $_POST ['value'],
                'result' => sendCmdSetUnitSTB ( $_POST ["ipAddr"], $_POST ["value"] )
        ) );
    }
} elseif ($_POST ['submit'] == 'getMaxEventId') {
    if (isset ( $_POST ["id"] )) {
        echo json_encode ( array (
                'id' => $_POST ['id'],
                'result' => getMaxEventId ( $_POST ["id"] )
        ) );
    }
} elseif ($_POST ['submit'] == 'getEvents') {
    if (isset ( $_POST ["id"] ) && isset ( $_POST ["eventId"] )) {
        echo json_encode ( array (
                'id' => $_POST ['id'],
                'eventId' => $_POST ['eventId'],
                'result' => getEvents ( $_POST ["id"], $_POST ["eventId"] )
        ) );
    }
} elseif ($_POST ['submit'] == 'getLastEvents') {
    if (isset ( $_POST ["id"] ) && isset ( $_POST ["eventId"] )) {
        echo json_encode ( array (
                'id' => $_POST ['id'],
                'eventId' => $_POST ['eventId'],
                'result' => getLastEvents ( $_POST ["id"], $_POST ["eventId"] )
        ) );
    }
} elseif ($_POST ['submit'] == 'getThresholds') {
    if (isset ( $_POST ["id"] )) {
        echo json_encode ( array (
                'id' => $_POST ['id'],
                'result' => getThresholds ( $_POST ["id"] )
        ) );
    }
} elseif ($_POST ['submit'] == 'setThreshold') {
    if (isset ( $_POST ["id"] ) && isset ( $_POST ["type"] ) && isset ( $_POST ["valueWarning"] ) && isset ( $_POST ["valueCritical"] )) {
        setThreshold ( $_POST ["id"], $_POST ["type"], $_POST ["valueWarning"], $_POST ["valueCritical"]);
    }
} elseif ($_POST ['submit'] == 'getPaDevices') {
    echo json_encode ( array (
            'result' => getPaDevices ()
    ) );
} elseif ($_POST ['submit'] == 'getUnitInfos') {
    $data = array (
        'mute'                  => getState ( 'Mute' ),
        'source'                => getCurrentInput(getState('Input')),
        'upmix'                 => getActiveUpmix (),
        'streamType'            => getStateTmp ( 'InputStream_LONG' ),
        'volume'                => getState ( 'MasterVolume' ),
        'video' => array(
            'input'            => getStateTmp ( 'VideoInput' ),
            'sync'             => getStateTmp ( 'VideoSync' ),
            'timing'           => getStateTmp ( 'VideoTiming' ),
            'dynamicRange'     => getStateTmp ( 'VideoDynamicRange' ),
            'copyProtection'   => getStateTmp ( 'VideoCopyProtection' ),
            'colorSpace'       => getStateTmp ( 'VideoColorSpace' ),
            'colorDepth'       => getStateTmp ( 'VideoColorDepth' ),
            'mode'             => getStateTmp ( 'VideoMode' ),
            'Output_1_SinkDetect'   => getStateTmp ( 'Output_1_SinkDetect' ),
            'Output_1_State'    => getStateTmp ( 'Output_1_State' ),
            'Output_2_SinkDetect'   => getStateTmp ( 'Output_2_SinkDetect' ),
            'Output_2_State'    => getStateTmp ( 'Output_2_State' ),
        ),
        'powerSupply' => array (
                '1V1'    => getStateTmp ( 'PowerSupply_1V1' ),
                '3V3'    => getStateTmp ( 'PowerSupply_3V3' ),
                '5V0'    => getStateTmp ( 'PowerSupply_5V0' ),
                '12V0'   => getStateTmp ( 'PowerSupply_12V0' ),
                'status' => getStateTmp ( 'PowerSupplyStatus' ),
         ),
    );
    echo json_encode ( array (
            'result' => $data
    ) );
}
