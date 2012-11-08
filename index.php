<?php 
    error_reporting(E_PARSE);    
    include "imga.class.php";
    $ig = new IMGA();
?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta charset="utf-8"/>
    <meta name="description" content="Image Gallery">
    <meta name="author" content="Steffen Volkhardt">
    
    <link rel="stylesheet" href="css/style.css" type="text/css" />
    <link rel="stylesheet" href="css/jquery.fancybox-1.3.4.css" type="text/css" />

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script src="js/jquery.easing-1.3.pack.js"></script>
    <script src="js/jquery.fancybox-1.3.4.pack.js"></script>
    
    <title>PHX.IMGA - Phyxius Image Gallery</title>

    <script type="text/javascript"> 
        $(document).ready(function() {            
            $("#uploadform").hide();
            $("#catsform").hide();
            $("div#messages").hide();
            
            $("#upload").live("click", function() {
                if($("#uploadform").is(":visible")) {
                    $("#uploadform").hide();
                } else {
                    $("#uploadform").show("slow");   
                }
            });
            
             $("#kategorien").live("click", function() {
                if($("#catsform").is(":visible")) {
                    $("#catsform").hide();
                } else {
                    $("#catsform").show("slow");   
                }
            });
            
            // click engine for tags
            $("a.tag").live("click", function() {
                
                if($(this).hasClass("active")) 
                {
                    $(this).removeClass("active");                    
                    var str = $("input#name").val();
                    var tmp = str.replace(" "+$(this).text(), "");
                    $("input#name").val(tmp);
                } 
                else 
                {                    
                    $(this).addClass("active");                    
                    var list = $("input#name").val();
                    list += " "+$(this).text();                    
                    $("input#name").val(list);  
                }
            });
            
            $("div#all").hide();
            
            $("div#tagmenu a").live("click", function() {
                if(!($(this).hasClass("on"))) {
                    $("div#tagmenu a").removeClass("on");
                    $(this).addClass("on");
                    
                    if($(this).text() == "Gesamt") {
                        $("div#most").hide();
                        $("div#all").show("slow");
                    }
                    
                    if($(this).text() == "Top 10") {
                        $("div#all").hide();
                        $("div#most").show("slow");
                    }
                } 
            });
            
            // message handling & form validation
            
            $("input#sub").live("click", function() {
                $("div#messages").removeClass("warn error correct");
                
                if($("input#tags").val() == "") {                    
                    $("div#messages").text("Bitte mindestens ein Tag angeben.").addClass("warn").show();  
                    
                } else if ($("input#file1").val() == "") {
                    $("div#messages").text("Bitte wählen Sie eine Bilddatei aus.").addClass("warn").show();      
                } else {
                    $("form#uploadform").submit();   
                }
            });
            
            <?php
                if($_POST["act"] == "upload") { 
                    $res = $ig->uploadImage($_FILES, $_POST["tags"]);
            
                    if($res == "") { 
                        ?>
                            $("div#messages").removeClass("warn error correct");
                            $("div#messages").text("Image saved to database.").addClass("correct").show();
                        <?php
                    } else {
                        ?>
                            $("div#messages").removeClass("warn error correct");
                            $("div#messages").text("<?php echo $res; ?>").addClass("error").show();   
                        <?php
                    }
                }
            
                if($_POST["act"] == "setcat") { 
                    $res = $ig->setCategory($_POST["tagname"]); 
                    
                    if($res == "") {
                        ?>
                            $("div#messages").removeClass("warn error correct");
                            $("div#messages").text("Tag is now marked as category.").addClass("correct").show();
                        <?php
                    } else {
                        ?>
                            $("div#messages").removeClass("warn error correct");
                            $("div#messages").text("<?php echo $res; ?>").addClass("error").show();   
                        <?php
                    }
                }  
            
                 if($_POST["act"] == "catdel") {
                    $ig->unsetCategory($_POST["catnr"]);   
                }
            ?>
         });
        
        function initFileUploads() { $("#faki").val($("#file1").val()); }
    </script>    
</head>
<body>
    <div id="header">
        <a href="index.php">PHX.IMGA<br /><span>Phyxius Image Gallery</span></a>
    </div>
    <div id="cats">
        <h2>Categories</h2>
        <?php $ig->getAllCategories(); ?>
        <br /><br />
        <p class="leg" style="background-color: #FFE875;">&nbsp;<p> Category<div style="clear: both;"></div>
        <p class="leg" style="background-color: #10324D;">&nbsp;<p> Image details<div style="clear: both;"></div>
        <p class="leg" style="background-color: #E9E9E9;">&nbsp;<p> Tag<div style="clear: both;"></div>
    </div>
    <div id="wrap">
        <div id="messages"></div>
        
        <form method="post" action="index.php" id="searchform">      
            <h2>Search</h2>
            <input name="name" type="text" id="name" value="" />&nbsp;
            <input type="submit" value="Search" id="search" />&nbsp;
            <input type="button" value="Upload" id="upload" />&nbsp;
            <input type="button" value="Categories" id="kategorien" /><br />
            <p>*Please insert tags separated by blank to serach images</p><br />
            <input type="checkbox" name="searchtyp" id="searchtyp" value="AND">Only search images that match that match certain criteria <br />
            <input type="hidden" name="act" value="search" />
        </form> 
        
        <form enctype="multipart/form-data" method="post" action="index.php" id="uploadform" style="margin-top: 40px;">
            <h2>Upload</h2>
            <input name="tags" type="text" id="tags" value="" />&nbsp;
            <input type="button" id="sub" value="Ok" style="vertical-align: top;" /><br />
            <p>*Please insert tags separated by blank. It helps to find images later</p>
            <div id="hiddiv" class="fileinputs">
                <input type="file" class="file hidden" id="file1" name="file1[]" onchange="javascript: initFileUploads();" accept="image/*" />
                <div class="fakefile">
                    <input id="faki" />
                    <img src="images/search.png" height="35px" style="vertical-align: middle;"  />
                </div>
            </div>
            <input type="hidden" name="act" value="upload" />
        </form>
        
        <form method="post" action="index.php" id="catsform" style="margin-top: 40px;">  
            <h2>Category</h2>
            <input name="tagname" type="text" id="tagname" value="" />&nbsp; 
            <input type="submit" value="Ok" id="setcat" />
            <p>*Please insert an existing tag name to mark it as category</p>
            <input type="hidden" name="act" value="setcat" />
        </form>
        
        <hr />
        <div id="tagmenu">
            <a class="on" href="javascript: void(0);">Top 10</a><a href="javascript: void(0);">All</a>
        </div>
        <div id="all"><?php $ig->getTaglist(); ?></div>
        <div id="most"><?php $ig->getMostWantedTaglist(); ?></div>
        <div style="clear: both;"></div>
        
        <div id="results">
            <?php
                if($_POST["act"] == "search") 
                { 
                    echo '<hr />';
                    echo '<br /><br /><br /><br />';
                    echo '<h2>Images</h2>';
                    
                    if (isset($_POST['searchtyp'])) { $typ = "AND"; } else { $typ = "OR"; }
                    $ig->searchImage($_POST["name"], $typ); 
                }
            ?>
        </div>
        <div style="clear: both;"></div>
        <div id="footer"><p>Version 1.0 | <a href="http://www.phyxius.de">www.phyxius.de</a> | Copyright © 2012 Steffen Volkhardt</p></div>
    </div>
    <script type="text/javascript"> 
        // dynamic width/height
        $('a.lightbox').each(function() {  
            
            var x = $(this).children("input.x"); 
			var y = $(this).children("input.y"); 
            
            $(this).fancybox({
                'transitionIn'	:	'elastic',
                'transitionOut'	:	'elastic',
                'speedIn'		:	600, 
                'speedOut'		:	200, 
                'overlayShow'	:	false,
                'type'          :   'iframe',
                'width'         :   parseInt(x.val()) + 20,
                'height'        :   parseInt(y.val()) + 20
            });
            
        });
    </script>
</body>
</html>




















