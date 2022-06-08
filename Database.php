<?php

use JetBrains\PhpStorm\NoReturn;

class Database{

    // https://mein.ionos.de/webhosting/7402843a-7879-4a56-a0e1-494cae6e976f/databases/61fc8814-b450-4330-bad3-41c2c4ec5ecd/open
    const Servername = 'db5008318612.hosting-data.io';
    const User = 'dbu971816';
    const Passwort = 'uniTrierVR22';
    const Db = 'dbs6958995';
    private static mysqli|null $conn = Null;
    private static String $lastError = "";

    private static function createConnection(){
        if(!is_null(self::$conn))
            return;
        // Create connection
        self::$conn = new mysqli(self::Servername, self::User, self::Passwort, self::Db) or die("No Connection to Database");
        // Check connection
        if (self::$conn->connect_error) {
            exitWithTxt("Connection failed: " . self::$conn->connect_error, 401);
        }
    }

    public static function closeConnection(){
        if(!is_null(self::$conn))
            self::$conn->close();
    }

    public static function doSQL($sqlTxt) : mysqli_result|bool{
        if($sqlTxt == "" || is_null($sqlTxt))
            return false;
        if(is_null(self::$conn))
            self::createConnection();

        $res = self::$conn->query($sqlTxt);
        //self::$lastID = self::$conn->insert_id;
        if($res == false)
            self::$lastError="MySQL Anfrage fehlgeschlagen: ".$sqlTxt."<br>Error Message: ".self::$conn->error;
        return $res;
    }

    public static function getLastError() : String{
        return self::$lastError;
    }

    ////////////

    public static function db_createNewSession() : int{
        do{
            $sessionID = rand(100000,999999);
            $res = Database::doSQL("Select * from `controllers` where `SessionID` = $sessionID");
        }while($res->num_rows != 0);
        $res = Database::doSQL("INSERT INTO `controllers` (`SessionID`, `Orientation_X`, `Orientation_Y`, `Orientation_Z`) VALUES ($sessionID, 0, 0, 0);");
        if($res == false)
            return -1;
        return $sessionID;
    }

    public static function db_getControllerValues($sessionID) : String{
        $res = Database::doSQL("Select * from `controllers` WHERE`SessionID` = $sessionID");
        if($res == false || $res->num_rows == 0)
            return "Session not found";
        $session = $res->fetch_assoc();
        if(is_null($session['Device']))
            return "No device connected";
        return json_encode($session);
    }

    public static function db_setControllerDevice($sessionID) : bool{
        //$device = gethostname();
        $device = $_SERVER['REMOTE_ADDR'];
        $res = self::doSQL("Update `controllers` SET `Device` = '$device' WHERE `SessionID` = $sessionID AND `Device` IS NULL");
        if($res == false)
            return false;
        if(self::$conn->affected_rows == 0){
            self::$lastError = "No affected rows";
            return false;
        }
        return true;
    }

    public static function db_setControllerValues($sessionID, $orientation_x, $orientation_y, $orientation_z) : bool{
        $res = self::doSQL("Update `controllers` SET `Orientation_X` = '$orientation_x', `Orientation_Y` = '$orientation_y', `Orientation_Z` = '$orientation_z' 
                            WHERE `SessionID` = '$sessionID' AND `Device` IS NOT NULL");
        if($res == false)
            return false;
        if(self::$conn->affected_rows == 0){
            self::$lastError = "No affected rows";
            return false;
        }
        return true;
    }

}


