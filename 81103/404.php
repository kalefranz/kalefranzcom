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
      <a href=\"music.html\">Music</a><a href=\"photos.html\">Photos</a><a href=\"contact.html\">Contact</a></div></div>";

/* Beginning of main body */
echo "<div id=\"mainbody\">";

echo "<h2 style=\"color:#ff0000;font-size:22px;font-weight:bold;\">404 Ooops!</h2>";


if (!empty($request)) {
  echo "<span style=\"font-size:19px;\">The page you have requested -- &nbsp;<b>";
  echo $request;
  echo "</b> &nbsp;-- is not on the server.</span><br>";
}

echo "<p style=\"font-size:14px;\">The page or document you are looking for may be correctly linked on 
      the <br><a href=\"http://www.kalefranz.com/publications.html\" style=\"font-size:16px;\">Publications</a> page.</p><br>";

//if (!empty($referer)) {
//  echo "You just came from <b>";
//  echo $referer;
//  echo "</b>.<br><br>";
//}

echo "The error has been logged.  Email Kale if this problem keeps you from finding something you need.<br><br>";

echo "</div>";
/*   End of main body  */

echo "<div class=\"footer\"><p style=\"padding-top:10px\">&#169; 2008 Kale John Franz</p></div>";
echo "</div>";
echo "</body></html>";


if (preg_match("/googlebot/",$hostName))
    return;  // don't send me an email if it's just a googlebot

$headers = array(
   'From' => "kalefranz.com",
   'Subject' => "Broken link on kalefranz.com",
   );
$to = "kf@kfranz.us";

$text = "requested page: " . $request . "\nreferring page: " . $referer . 
        "\nip: " . $ip . "\nhostname: " . $hostName;

$crlf = "\n";
$mime = new Mail_mime($crlf);

$mime->setTXTBody($text);
$body = $mime->get();
$headers = $mime->headers($headers);

$mail =& Mail::factory('mail');
$mail->send($to, $headers, $body);


if (PEAR::isError($mail)) {
  echo("<p>" . $mail->getMessage() . "</p>");
 }
 
?>
