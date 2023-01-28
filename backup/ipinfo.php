<?php
include('Mail.php');
include('Mail/mime.php');
include('../phpfncs.php');

$request = $_SERVER['REQUEST_URI'];
$referer = $_SERVER['HTTP_REFERER'];
$ip = getip();
$hostName = gethostbyaddr($ip);

echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
echo "<html><head><title>Kale Franz</title><meta http-equiv=\"Content-Type\" content=\"text/HTML; charset=UTF-8\">";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"global.css\"></head>";

echo "<body><div class=\"pagedef\"><div class=\"header\"></div>";
echo "<div class=\"menu\"><div class=\"nav\"><a href=\"index.html\">Home</a><a href=\"publications.html\">Publications</a>
      <a href=\"physics.html\">Physics</a><a href=\"links.html\">Links</a><a href=\"CV.html\">CV</a>
      <a href=\"photos.html\">Photos</a><a href=\"contact.html\">Contact</a></div></div>";

/* Beginning of main body */
echo "<div id=\"mainbody\">";

echo "<h2 style=\"text-align:center;\">$ip</h2>";
echo "<h2 style=\"text-align:center;\">$hostName</h2>";

echo "</div>";
/*   End of main body  */

echo "<div class=\"footer\"><p style=\"padding-top:10px\">&#169; 2008 Kale John Franz</p></div>";
echo "</div>";
echo "</body></html>";



?>
