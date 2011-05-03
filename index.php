<?
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
		if (($error == UPLOAD_ERR_OK) && ($_FILES['uploadFiles']['type'][$key] == "image/jpeg" || $_FILES['uploadFiles']['type'][$key] == "image/gif" || $_FILES['uploadFiles']['type'][$key] == "image/png"))
		{
			$uploaded = true;
			$uploadDir = "files/";
			$fileType = $_FILES['uploadFiles']['type'][$key];
			$fileTmpLoc = $_FILES["uploadFiles"]["tmp_name"][$key];
			$fileName = $_FILES["uploadFiles"]["name"][$key];
			$fileHash = hash_file(crc32,$fileTmpLoc);
			switch ($fileType)
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
			}
			$fileNewName = $fileHash.".".$fileExt;
			$globArray = glob($uploadDir.substr($fileHash, 0, 1)."*");
			$fileNewLoc = $globArray[0]."/".$fileNewName;
			$fileAbsLoc = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].$fileNewLoc;
			$fileLocArray['full'][] = $fileAbsLoc;
			move_uploaded_file($fileTmpLoc, $fileNewLoc);
			$theImage = new Imagick($fileNewLoc);
			$theImage->stripImage();
			$theImage->writeImage($fileNewLoc);
			$theImage->thumbnailImage(120,0);
			$theImage->borderImage("black",2,2);
			$theImage->writeImage($globArray[0]."/".$fileHash."_tn.".$fileExt);
			$fileLocArray['thumb'][] = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].$globArray[0]."/".$fileHash."_tn.".$fileExt;
		}
	}
	if($uploaded)
	{
                echo "<strong>BBCode with thumbnail:</strong><br />\n";
                echo "<blockquote>\n";
                foreach ($fileLocArray['full'] as $key => $value)
                {
                        echo "[url=\"".$fileLocArray['full'][$key]."\"][img]".$fileLocArray['thumb'][$key]."[/img][/url]<br />\n";
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
                        echo $value."<br />\n";
                }
                echo "</blockquote>\n";
	        echo "<hr>\n";
	}
}
	echo "It doesn't get any easier than this! Welcome to <strong>derp-o-matic</strong> 2000!<br /> <br />\n";
	echo "<form method=\"post\" action=\"\" enctype=\"multipart/form-data\">\n";
	echo "<input name=\"uploadFiles[]\" id=\"filesToUpload\" type=\"file\" multiple=\"\"/>\n";
	echo "<input type=\"submit\" value=\"HerpDerp\">\n";
	echo "</form>";
?>
