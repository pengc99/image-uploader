<?php

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

echo "<meta content=\"True\" name=\"HandheldFriendly\" />";
echo "<meta content=\"width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;\" name=\"viewport\" />";
echo "<meta name=\"viewport\" content=\"width=device-width\" />";

if( isset($_SERVER['HTTPS'] ) )
{
        $protocol = "https://";
}

else
{
        $protocol = "http://";
}

if(isset($_FILES["uploadFiles"]))
{
        $uploaded = false;
        if (count(glob($_SERVER['DOCUMENT_ROOT']."/files/*", GLOB_ONLYDIR)) == 0)
        {
                touch($_SERVER['DOCUMENT_ROOT']."/files/index.html");
                $characters = "0123456789abcdef";
                for($i = 1; $i <= strlen($characters); $i++)
                {
                        $newUUID = substr($characters, ($i-1), 1).uniqid();
                        mkdir($_SERVER['DOCUMENT_ROOT']."/files/".$newUUID);
                        touch($_SERVER['DOCUMENT_ROOT']."/files/".$newUUID."/index.html");
                }
        }
        foreach ($_FILES["uploadFiles"]["error"] as $key => $error)
        {
                $imageMime = mime_content_type($_FILES['uploadFiles']['tmp_name'][$key]);
                if (($error == UPLOAD_ERR_OK) && ($imageMime == "image/jpeg" || $imageMime == "image/gif" || $imageMime == "image/png" || $imageMime == "video/mp4"))
                {
                        $uploaded = true;
                        $uploadDir = "files/";
                        $fileTmpLoc = $_FILES["uploadFiles"]["tmp_name"][$key];
                        $fileName = $_FILES["uploadFiles"]["name"][$key];
                        $fileHash = hash_file('crc32',$fileTmpLoc);
                        switch ($imageMime)
                        {
                                case "image/jpeg":
                                        $fileExt = "jpg";
                                break;

                                case "image/png":
                                        $fileExt = "png";
                                break;

                                case "image/gif":
                                        $fileExt = "gif";
                                break;

                                case "video/mp4":
                                        $fileExt = "mp4";
                                break;
                        }

                        $fileNewName = $fileHash.".".$fileExt;
                        $globArray = glob($uploadDir.substr($fileHash, 0, 1)."*");
                        $fileNewLoc = $globArray[0]."/".$fileNewName;
                        $fileAbsLoc = $protocol.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].$fileNewLoc;
                        $fileLocArray['full'][] = $fileAbsLoc;

                        #Move the file from the tmp location to the storage location
                        move_uploaded_file($fileTmpLoc, $fileNewLoc);

                        #Only drop into the loop if upload file is an image
                        if ($imageMime == "image/jpeg" || $imageMime == "image/png")
                        {
                                #Generate small thumbnail
                                $theImage = new Imagick($fileNewLoc);
                                $theImage->stripImage();
                                $theImage->writeImage($fileNewLoc);
                                $theImage->thumbnailImage(120,0,FALSE);
                                $theImage->borderImage("black",2,2);
                                $theImage->writeImage($globArray[0]."/".$fileHash."_tn.".$fileExt);
                                $theImage->destroy();

                                #Generate medium thumbnail
                                $theImage = new Imagick($fileNewLoc);
                                $theImage->stripImage();
                                $theImage->writeImage($fileNewLoc);
                                $theImage->thumbnailImage(600,0,FALSE);
                                $theImage->borderImage("black",2,2);
                                $theImage->writeImage($globArray[0]."/".$fileHash."_md.".$fileExt);
                                $theImage->destroy();

                                #Attach thumbnail URLs to the response array
                                $fileLocArray['thumb'][] = $protocol.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].$globArray[0]."/".$fileHash."_tn.".$fileExt;
                                $fileLocArray['medium'][] = $protocol.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].$globArray[0]."/".$fileHash."_md.".$fileExt;
                        }

                        #Only drop into the loop if upload file is a GIF
                        if ($imageMime == "image/gif")
                        {
                                #First unoptimize the GIF so it can be resized
                                exec("/usr/bin/gifsicle --batch --unoptimize $fileNewLoc");

                                #Generate small thumbnail and optimize
                                exec("/usr/bin/gifsicle --optimize=03 --resize-fit-width 120 $fileNewLoc -o $globArray[0]/{$fileHash}_tn.$fileExt");

                                #Generate medium thumbnail and optimize
                                exec("/usr/bin/gifsicle --optimize=03 --resize-fit-width 600 $fileNewLoc -o $globArray[0]/{$fileHash}_md.$fileExt");

                                #Optimize the original
                                exec("/usr/bin/gifsicle --batch --optimize=03 $fileNewLoc");

                                #Attach thumbnail URLs to the response array
                                $fileLocArray['thumb'][] = $protocol.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].$globArray[0]."/".$fileHash."_tn.".$fileExt;
                                $fileLocArray['medium'][] = $protocol.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].$globArray[0]."/".$fileHash."_md.".$fileExt;
                        }

                        #Only drop into the loop if upload file is a video
                        if ($imageMime == "video/mp4")
                        {
                        }
                }
        }
        if($uploaded)
        {

                echo "<strong>Image preview:</strong><br />\n";
                echo "<blockquote>\n";
                foreach ($fileLocArray['full'] as $key => $value)
                {
                        echo "<img src = \"".$fileLocArray['medium'][$key]."\"><br />\n";
                }
                echo "</blockquote>\n";
                echo "<strong>BBCode with thumbnail:</strong><br />\n";
                echo "<blockquote>\n";
                foreach ($fileLocArray['full'] as $key => $value)
                {
                        echo "[url=".$fileLocArray['full'][$key]."][img]".$fileLocArray['thumb'][$key]."[/img][/url]<br />\n";
                }
                echo "</blockquote>\n";
                echo "<br />\n";
                echo "<strong>HTML with thumbnail:</strong><br />\n";
                echo "<blockquote>\n";
                foreach ($fileLocArray['full'] as $key => $value)
                {
                        echo "&lt;a href=\"".$fileLocArray['full'][$key]."\" target=\"_new\"&gt;&lt;img src=\"".$fileLocArray['thumb'][$key]."\"&gt;&lt;/a&gt;<br />\n";
                }
                echo "</blockquote>\n";
                echo "<br />\n";
                echo "<strong>BBCode with medium sized image:</strong><br />\n";
                echo "<blockquote>\n";
                foreach ($fileLocArray['full'] as $key => $value)
                {
                        echo "[url=".$fileLocArray['full'][$key]."][img]".$fileLocArray['medium'][$key]."[/img][/url]<br />\n";
                }
                echo "</blockquote>\n";
                echo "<br />\n";
                echo "<strong>HTML with medium sized image:</strong><br />\n";
                echo "<blockquote>\n";
                foreach ($fileLocArray['full'] as $key => $value)
                {
                        echo "&lt;a href=\"".$fileLocArray['full'][$key]."\" target=\"_new\"&gt;&lt;img src=\"".$fileLocArray['medium'][$key]."\"&gt;&lt;/a&gt;<br />\n";
                }
                echo "</blockquote>\n";
                echo "<br />\n";
                echo "<strong>BBCode:</strong><br />\n";
                echo "<blockquote>\n";
                foreach ($fileLocArray['full'] as $value)
                {
                        echo "[img]".$value."[/img]<br />\n";
                }
                echo "</blockquote>\n";
                echo "<br />\n";
                echo "<strong>HTML:</strong><br />\n";
                echo "<blockquote>\n";
                foreach ($fileLocArray['full'] as $value)
                {
                        echo "&lt;img src=".$value."&gt;<br />\n";
                }
                echo "</blockquote>\n";
                echo "<br />\n";
                echo "<strong>Direct URL:</strong><br />\n";
                echo "<blockquote>\n";
                foreach ($fileLocArray['full'] as $value)
                {
                        echo "<a href = \"$value\">$value</a><br />\n";
                }
                echo "</blockquote>\n";
                echo "<hr>\n";
        }
}
        echo "It doesn't get any easier than this! Welcome to <strong>derp-o-matic</strong> 2000!<br /> <br />\n";
        echo "<form method=\"post\" action=\"\" enctype=\"multipart/form-data\">\n";
        echo "<input name=\"uploadFiles[]\" id=\"filesToUpload\" type=\"file\" accept=\"image/*,video/*\" multiple=\"\"/>\n";
        echo "<input type=\"submit\" value=\"HerpDerp\">\n";
        echo "</form>";
?>
