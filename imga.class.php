<?php
    include_once("config/config.php");

    class IMGA
    {
        private $db = DB;
        private $user = USER;
        private $pwd = PWD;
        private $host = HOST;
        
        private $link;
        
        private function __dbconnect()
        {
            $this->link = mysql_connect($this->host, $this->user, $this->pwd) or die ("Unable to connect to DB server. Error: ".mysql_error());
            mysql_select_db($this->db); 
        }
        
        
        private function __dbdisconnect()
        {
            mysql_close($this->link);  
        }
        
        
        private function __getTagsFromImage($iid, $iscat=0)
        {
            $arr = array();
            $binds = mysql_query("SELECT tid FROM bindings WHERE iid=".$iid.";");
            
            while($r = mysql_fetch_assoc($binds))
            {
                $tnameres = mysql_query("SELECT * FROM tags WHERE id=".$r["tid"].";");
                $tname = mysql_fetch_assoc($tnameres);
                
                if($iscat == 1) { 
                    $myarr = array($tname["name"], $tname["iscat"]);
                    array_push($arr, $myarr);
                }
                else { array_push($arr, $tname["name"]); }
            }
            
            return $arr;
        }
        
        private function __getNameFromImage($iid)
        {
            $res = mysql_query("SELECT name FROM images WHERE id=".$iid.";");  
            $r = mysql_fetch_assoc($res);
            return $r["name"];
        }
        
        private function __getEndingFromImage($iid)
        {
            $res = mysql_query("SELECT ending FROM images WHERE id=".$iid.";");  
            $r = mysql_fetch_assoc($res);
            return $r["ending"];
        }
        
        public function uploadImage($files, $tags)
        {
            $err = "";
            
            foreach ($files["file1"]["error"] as $key => $error) 
            {
                // If upload is successful
                if($error == UPLOAD_ERR_OK)
                {
                    $this->__dbconnect();
                    
                    // tagid - imageid
                    $tid = 0; $iid = 0;
                    
                    $spl = explode('.', $files["file1"]["name"][0]);
                    $image = chunk_split(base64_encode(file_get_contents($files["file1"]['tmp_name'][$key])));
                    $query = "INSERT INTO images(name, ending, imgblob) VALUES ('$spl[0]', '$spl[1]','$image');";
                    mysql_query($query);
                    $iid = mysql_insert_id();
                    $err .= mysql_error();
                
                    // tag handling
                    $taglist = explode(" ", $tags);
                    
                    foreach($taglist as $tag) {
                        if($tag != "")
                        {
                            $res = mysql_query("SELECT * FROM tags WHERE name='$tag';");
                            if(mysql_num_rows($res) == 0) {
                                mysql_query("INSERT INTO tags (name) VALUES('$tag');");
                                $tid = mysql_insert_id();
                                $err .= mysql_error();
                            }
                            else { 
                                $row = mysql_fetch_assoc($res);
                                $tid = $row["id"];
                            }
                            
                            // bindings
                            if($tid > 0 && $iid > 0) {
                                mysql_query("INSERT INTO bindings(tid, iid) VALUES($tid, $iid);");
                                $err .= mysql_error();
                            }
                        }
                    }
                    
                    $this->__dbdisconnect();
                }
                else { $err .= $error; }
                
                return $err;
            }
        }
        
        public function searchImage($tags, $typ)
        {
            $err = "";
            $this->__dbconnect();
            
            $taglist = explode(" ", $tags);
            
            // remove empty fields
            foreach ($taglist as $key => $link)
            {
                if ($taglist[$key] == '')
                {
                    unset($taglist[$key]);
                }
            }

            if(count($taglist) > 0) {
                $q = "SELECT * FROM tags ";
                $i = 0;
                foreach($taglist as $tag) {
                    if($i == 0) { $q .= "WHERE name LIKE '$tag'"; }  
                    else { $q .= " OR name LIKE '$tag'"; }                    
                    $i++;
                }
                $q .= ";";
                
                $res = mysql_query($q); 
                $err .= mysql_error();
                
                $nr = mysql_num_rows($res);
                
                if($nr > 0)
                {
                    $t = 0;
                    
                    if($typ == "OR") { $iq = 'SELECT DISTINCT iid FROM bindings '; } 
                    else { $iq = 'SELECT iid FROM bindings '; }
                    
                    while($r = mysql_fetch_assoc($res)) { 
                        if($t == 0) { $iq .= 'WHERE tid='.$r["id"]; }
                        else { $iq .= ' OR tid='.$r["id"];  }
                        
                        $t++;
                    }
                
                    
                    if($typ == "OR") { $iq .= ';'; } 
                    else { if($nr > 1) { $iq .= ' GROUP BY iid HAVING COUNT(*) > 1;'; } else { $iq .= ' GROUP BY iid;'; } }

                    
                    $imgs = mysql_query($iq);                    
                    
                    while($im = mysql_fetch_assoc($imgs))
                    {     
						$query = "SELECT imgblob from images where id=".$im["iid"];
					    $rs = mysql_fetch_array(mysql_query($query));					
						$gd = imagecreatefromstring(base64_decode($rs["imgblob"]));
						$width = imagesx($gd);
						$height = imagesy($gd);
					
					
                        echo '<div class="item">';
                        echo '<a class="lightbox" href="showimage.php?id='.$im["iid"].'" title="" >';
						echo '<input type="hidden" class="x" name="x" value="'.$width.'" />';
						echo '<input type="hidden" class="y" name="y" value="'.$height.'" />';
						echo '<img src="showimage.php?id='.$im["iid"].'" /></a>';
                        echo '<p class="info">';
                        echo '<a class="ending" href="javascript:void(0);">'.$this->__getNameFromImage($im["iid"]).'</a>';
                        echo '<a class="ending" href="javascript:void(0);">'.$this->__getEndingFromImage($im["iid"]).'</a>';
                        
                        $tags = $this->__getTagsFromImage($im["iid"], 1);
                        foreach($tags as $tag) {
                            if($tag[1] == 1) {
                                echo '<a class="cat" href="javascript:void(0);">'.$tag[0].'</a>'; 
                            } else { 
                                echo '<a href="javascript:void(0);">'.$tag[0].'</a>';   
                            }
                        }        
                        
                        echo '<a class="down" href="downimage.php?id='.$im["iid"].'">Download</a>';
                        echo '</p>';
                        echo '</div>';
                    }
                } else { echo "Keine Bilder gefunden..."; }
            } else { echo "Keine Bilder gefunden..."; }
            $this->__dbdisconnect();
            return $err;
        }
        
        public function getTaglist()
        {
            $this->__dbconnect();
            $taglist = mysql_query("SELECT * FROM tags ORDER BY name ASC;");
            while($row = mysql_fetch_assoc($taglist)) { 
                if($row["iscat"] == 1) { echo '<a class="tag cat" href="javascript: void(0);">'.$row["name"].'</a>'; }
                else { echo '<a class="tag" href="javascript: void(0);">'.$row["name"].'</a>'; }
            }
            $this->__dbdisconnect();
        }
        
        public function getMostWantedTaglist()
        {
            $this->__dbconnect();            
            $taglist = mysql_query("SELECT tid, COUNT( tid ) AS numbers FROM bindings GROUP BY tid ORDER BY numbers DESC LIMIT 0 , 10;");            
            while($row = mysql_fetch_assoc($taglist)) { 
                
                $tag = mysql_query("SELECT * FROM tags WHERE id=".$row['tid']."");
                $t = mysql_fetch_assoc($tag);
                if($t["iscat"] == 1) { echo '<a class="tag cat" href="javascript: void(0);">'.$t["name"].'</a>'; }
                else { echo '<a class="tag" href="javascript: void(0);">'.$t["name"].'</a>'; }
            }            
            $this->__dbdisconnect();    
        }
     
        
        public function downloadImage($iid)
        {
            $this->__dbconnect();      
            $imgres = mysql_query("SELECT * FROM images WHERE id=".$iid.";");
            $img = mysql_fetch_assoc($imgres);
            
            
            $token = md5(uniqid(1));
            $token .= ".".$img["ending"];


            $file = fopen( $token, "w" );
            fwrite( $file, base64_decode($img["imgblob"]));
            fclose( $file );
            $this->__dbdisconnect();  
            
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false);
            
            header('Content-type: application/force-download');
            header('Content-Disposition: attachment; filename="'.$token.'"');            
            header("Content-Transfer-Encoding: binary");
            
            ob_clean();
            flush();
            readfile($token);
            
            unlink($token);
        }
        
        public function setCategory($tag)
        {
            $err = "";
            
            $this->__dbconnect();            
            $res = mysql_query("SELECT * FROM tags WHERE name = '$tag'");
            
            if(mysql_num_rows($res) > 0)
            {
                $r = mysql_fetch_assoc($res);
                
                mysql_query("UPDATE tags SET iscat=1 WHERE id=".$r['id'].";");
                $err .= mysql_error();
            }
            else { $err .= "Tag wnot found..."; }
            
            $this->__dbdisconnect();
            return $err;
        }
        
        public function getAllCategories()
        {
            $this->__dbconnect();            
            $res = mysql_query("SELECT * FROM tags WHERE iscat = 1;");
            echo mysql_error();
            echo '<ul id="mencat">';
            
            while($r = mysql_fetch_assoc($res))
            {
                echo '<li>';
                echo '<a href="javascript: void();">'.$r["name"].'</a>';
                echo '<form method="post" action="index.php" id="catdelform">';
                echo '<input type="submit" value="x" />';
                echo '<input type="hidden" name="act" value="catdel" />';
                echo '<input type="hidden" name="catnr" value="'.$r["id"].'" />';
                echo '</form>';
                echo '<div></div></li>';
            }
            
            echo '</ul>';
            $this->__dbdisconnect();
        }
        
        public function unsetCategory($tid)
        {
            $this->__dbconnect();  
            mysql_query("UPDATE tags SET iscat=0 WHERE id=".$tid.";"); 
            echo mysql_error();
            $this->__dbdisconnect();
        }
    }

?>


    
    
















