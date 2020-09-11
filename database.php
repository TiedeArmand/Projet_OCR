<?php 
require_once __DIR__.'/config.php';
	class Database
	{
	    public static function connect(){
	        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		    mysqli_set_charset($conn, "utf8");
	        // $conn->set_charset('utf8');
		    if (mysqli_connect_errno()) {
		    	echo 'Connect error:'.mysqli_connect_errno();
		    }
		    return $conn;
	   }
	   public static function Closeconnect($conn){
	   	mysqli_close($conn);
       }
    }
?>