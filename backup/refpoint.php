<?php
include('../phpfncs.php');

$crlf = "\n";

$type = $_GET['type'];
$fileName = $_GET['fileName'];
$fileSource = $_GET['fileSource'];

$ip = getip();
$hostName = gethostbyaddr($ip);

$sqllink = dbconn();

$link = mysql_real_escape_string($type . '/' . $fileName);
if (!empty($fileSource))
    $link = mysql_real_escape_string($fileSource . '/' . $fileName);

$result = mysql_query("SELECT * FROM linkcount WHERE link='$link'");
  if(mysql_num_rows($result) == 0) {
  	mysql_query("INSERT INTO linkcount (link) VALUES ('$link')")
          or die(mysql_error());
	mysql_query("UPDATE linkcount SET count=count+1 WHERE link='$link'")
          or die(mysql_error());
  } else {
	mysql_query("UPDATE linkcount SET count=count+1 WHERE link='$link'")
          or die(mysql_error());
  }
if (preg_match("/googlebot/",$hostName) || preg_match("/66.231.189/",$hostName))
    ;  // don't send me an email if it's just a googlebot
else
    mysql_query("INSERT INTO clicks_indv (link, ip, hostName) VALUES ('$link','$ip','$hostName')") or die(mysql_error());

mysql_close($sqllink);

/* CREATE TABLE clicks_indv (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  link varchar(200),
  ip VARCHAR(25),
  hostName varchar(200),
  time_stamp TIMESTAMP(8)
); */


$location='http://www.kalefranz.com/';

switch ($type) {
  case 'abstract':
   $location=$location . 'publications/abstracts/' . $fileName . '.pdf';
   break;
  case 'presentation':
   $location=$location . 'publications/presentations/' . $fileName . '.pdf';
   break;
  case 'paper':
   $location=$location . 'publications/papers/' . $fileName . '.pdf';
   break;
  case 'CV':
   $location=$location . $fileName . '.pdf';
   break;
  case 'article':
   $location=$location . 'writeups/articles/' . $fileName . '.pdf';
   break;
  case 'out':
   $location=$location;
   break;
}

header('Location:' . $location);

?>
