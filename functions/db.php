<?php 
//Create and Open a new connection to the MySQL server. Note you can create a separate file and use  <?php require_once just as you did in nav bar section in index.php
$servername = "localhost";
$username   = "root";
$password   = "Your password here";
$dbname     = "Bakery";

$con = new mysqli($servername, $username, $password, $dbname);  //NOTE: The MySQLi functions allows you to access MySQL database servers.MySQLi means MySQL improved.PHP 5 and later can work with a MySQL database using:
//MySQLi extension (the "i" stands for improved)
//PDO (PHP Data Objects)
//Create and Output any connection error
function confirm($result) {
	global $con;
	if ($con->connect_error) {
    die('Error : (' . $con->connect_errno . ') ' . $con->connect_error);
}
}

function escape($string){
	global $con;
	return mysqli_real_escape_string($con,$string); //used when insert into database to make data safe
}

function query($query){  //please be careful in edition this file
	global $con;  //global because the connection is outside the function
	$result = mysqli_query($con,$query);
	confirm($result);
	return $result;
}

function row_count($result){

	return mysqli_num_rows($result);
	
}

function fetch_array($result){
	global $con;
	return mysqli_fetch_array($result);
}

?>