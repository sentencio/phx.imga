<?php
    include "imga.class.php";
    $ig = new IMGA();
    $ig->downloadImage($_GET["id"]);
?>