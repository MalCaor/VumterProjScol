<?php

require_once 'db.inc.php';

define('INSTALLER_DEFAULT_PWD', '97384261b8bbf966df16e5ad509922db');
define('EXPERT_DEFAULT_PWD', 'b9b83bad6bd2b4f7c40109304cf580e1');

try {
    $PDO = new PDO('mysql:host=' . MYSQL_HOST . ';dbname=' . MYSQL_DB, MYSQL_USER, MYSQL_PASSWD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    $PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log('Unable to connect to DB:' . $e->getMessage());
}

$upmixMode = array(    
    0 => 'Native',
    1 => 'Stereo Downmix',
    2 => 'Dolby Surround',
    3 => 'DTS Neural:X',
    4 => 'Auro-Matic'
);
