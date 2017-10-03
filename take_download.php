<?php
require_once("./netease/netease.class.php");

if (!$_POST["songID"]) {
  header("HTTP/1.1 404 Not Found");
  exit;
}

$songid = $_POST["songID"];
$instance = new netease($songid);
if (!$instance->savelyric()) {
  header("HTTP/1.1 404 Not Found");
  exit;
}

$temp_lrc = $instance->temp_url();
if (!file_exists($temp_lrc)) {
  header("HTTP/1.1 404 Not Found");
  exit;
}

/* Start write file */
header("Content-Type: application/octet-stream");
Header("Accept-Ranges: bytes");
Header("Accept-Length: ".filesize($temp_lrc));
header("Content-Disposition: attachment; filename=".$songid.".lrc");

$fp = fopen($temp_lrc,"r");
$file_size = filesize($temp_lrc);
$buffsize = 1024;
$filecount = 0;

while(!feof($fp) && $file_size - $filecount > 0) {
  echo fread($fp,$buffsize);
  $filecount += $buffsize;
}

fclose($fp);
?>
