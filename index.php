<?
	/*
	Yep. Hosts images. One of the more well-coded and graceful scripts
	I've written. Takes advantage of the multiple-upload HTML5 element.
	Each uploaded image is hashed to it's CRC32 hash, and then stored
	in one of 16 directories created via UUID's. The UUID is generated
	and then a prefix is applied for each element of the hexadecimal
	elements (regex [0-9a-f]). This way, we get a more or less uniform
	distribution of files across 16 directories to reduce the stress
	on the filesystem if you had them all stuffed in one directory.
	Should be good for at least several hundred thousand files per
	instance of the script. The script also tries to be as dynamic and
	self-healing as possible. If it detects missing data directories
	it simply recreates it. If you upload a file that already exists
	it will overwrite the current file. Thumbnails are generated at
	upload using the PHP ImageMagick PECL library. HTML, phpBB, and
	direct URL codes are given when images are uploaded.
	
	Created and maintained by Andrew Peng:
	http://andrewpeng.net/computing/php-scripting/image-uploader-script
	
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	*/

	//checks to see if the form is submitted to itself
	if(isset($_FILES["uploadFiles"]))
	{
		//checks to see if the data directory exists
		$uploaded = false;
		if (count(glob($_SERVER['DOCUMENT_ROOT']."/files/*", GLOB_ONLYDIR)) == 0)
		{
			//creates the data directory structure
		        touch($_SERVER['DOCUMENT_ROOT']."/files/index.html");
		        $characters = "0123456789abcdef";
		        for($i = 1; $i <= strlen($characters); $i++)
		        {
		                $newUUID = substr($characters, ($i-1), 1).uniqid();
		                mkdir($_SERVER['DOCUMENT_ROOT']."/files/".$newUUID);
		                touch($_SERVER['DOCUMENT_ROOT']."/files/".$newUUID."/index.html");
		        }
		}

		//loop for each file to be uploaded 
		foreach ($_FILES["uploadFiles"]["error"] as $key => $error) 
		{
			//only begin processing if upload completes without error
			if (($error == UPLOAD_ERR_OK) && ($_FILES['uploadFiles']['type'][$key] == "image/jpeg" || $_FILES['uploadFiles']['type'][$key] == "image/gif" || $_FILES['uploadFiles']['type'][$key] == "image/png"))
			{
				//set variables and stuff
				$uploaded = true;
				$uploadDir = "files/";
				$fileType = $_FILES['uploadFiles']['type'][$key];
				$fileTmpLoc = $_FILES["uploadFiles"]["tmp_name"][$key];
				$fileName = $_FILES["uploadFiles"]["name"][$key];
				//generate the crc32 checksum of the file
				$fileHash = hash_file(crc32,$fileTmpLoc);
				//determine filetyes to prepare for renaming and tokenizing of the filenames
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
				//rename the file to it's crc32 checksum and set the extension
				$fileNewName = $fileHash.".".$fileExt;
				$globArray = glob($uploadDir.substr($fileHash, 0, 1)."*");
				$fileNewLoc = $globArray[0]."/".$fileNewName;
				$fileAbsLoc = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].$fileNewLoc;
				//location of the full size image
				$fileLocArray['full'][] = $fileAbsLoc;
				//move file to it's final location
				move_uploaded_file($fileTmpLoc, $fileNewLoc);
				//create imagemagick object for image resizing to make the thumbnail
				$theImage = new Imagick($fileNewLoc);
				//this removes EXIF data for privacy concerns
				$theImage->stripImage();
				//write the stripped fullsize image
				$theImage->writeImage($fileNewLoc);
				//generate a thumbnail 120px side
				$theImage->thumbnailImage(120,0);
				//add a black border to the thumbnail image
				$theImage->borderImage("black",2,2);
				//write the thumbnail and affix a _th to the file name before the extension
				$theImage->writeImage($globArray[0]."/".$fileHash."_tn.".$fileExt);
				//set location of the thumbnail
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
