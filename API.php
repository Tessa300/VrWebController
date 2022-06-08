<?php

use JetBrains\PhpStorm\NoReturn;

require_once("./Database.php");

if($_GET['key'] != "VR_uniTrier"){
    exitWithTxt("Wrong key", 401);
}

if($_GET['get'] == "NewSession") {
    $sessionID = Database::db_createNewSession();
    if($sessionID == -1)
        exitWithTxt(Database::getLastError(), 400);
    exitWithTxt($sessionID);
}elseif($_GET['get'] == "ControllerData"){
    if(!isset($_GET['SessionID']))
        exitWithTxt("No sessionID given", 400);
    exitWithTxt(Database::db_getControllerValues($_GET['SessionID']));
}elseif($_GET['set'] == "ControllerData"){
    if(!isset($_GET['SessionID']))
        exitWithTxt("No sessionID given", 400);
    if(!isset($_POST['Orientation_x']) || !isset($_POST['Orientation_y']) || !isset($_POST['Orientation_z']) || $_POST['Orientation_x'] == "")
        exitWithTxt("No given controller data", 400);
    $res = Database::db_setControllerValues($_GET['SessionID'], $_POST['Orientation_x'], $_POST['Orientation_y'], $_POST['Orientation_z']);
    if($res == false)
        exitWithTxt(Database::getLastError(), 400);
    exitWithTxt("Success");
}else{
    exitWithTxt("No request");
}

#[NoReturn] function exitWithTxt(String $txt, int $error = -1){
    Database::closeConnection();
    if($error != -1)
        http_response_code($error);
    exit($txt);
}



