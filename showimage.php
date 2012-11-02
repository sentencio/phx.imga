<?php
    include_once("config/config.php");

    $mysql_user = USER;
    $password = PWD;
    $database_host = HOST;
    $database = DB;
    
    mysql_connect($database_host, $mysql_user, $password) or die ("Unable to connect to DB server. Error: ".mysql_error());
    mysql_select_db($database);
    
    
    header('Content-type: image/jpeg');
    $query = "SELECT imgblob from images where id=". intval($_GET["id"]);
    $rs = mysql_fetch_array(mysql_query($query));
    //echo mysql_error();
    echo base64_decode($rs["imgblob"]);
?>