<?php  ob_start();  // ob_start() is a predifined function used for output buffering.
session_start();


include("db.php"); 
include("functions.php") ;

//This file makes the db and functions.php available to all files since they are included in init.php which is included in the header.php which is included in all files

 ?>
