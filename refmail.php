<?php
include('Mail.php');
include('Mail/mime.php');
include('../phpfncs.php');

$crlf = "\n";

$fileName = $_REQUEST['fileName'];
$authorNames = $_REQUEST['authorNames'];
$journalName = $_REQUEST['journalName'];
$paperTitle = $_REQUEST['paperTitle'];
$sendto = $_REQUEST['sendto'];
$preprint = $_REQUEST['preprint'];

$pattern1 = '/[^ a-zA-Z0-9\.]/';
$pattern2 = '/[^ a-zA-Z0-9_\-]/';

$fileName = preg_replace($pattern2,'',$fileName);
$authorNames = preg_replace($pattern1,'',$authorNames);
$journalName = preg_replace($pattern1,'',$journalName);

$from = "Kale Franz <kfranz@princeton.edu>";
$to = $sendto;
$subject = "Your requested " . $journalName . " article";

if ($preprint == "yes")
$text = "Attached is the preprint you requested by $authorNames -- $paperTitle -- to be published in $journalName. \nRegards,\nKale";
else
$text = "Attached is the publication you requested by $authorNames -- $paperTitle -- published in $journalName. \nRegards,\nKale";

$filePath = "/home/kfranz/public_html/publications/papers/" . $fileName . ".pdf";

$emailPattern = '/^[^@\s]+@([-a-z0-9]+\.)+[a-z]{2,}$/i';

if (!preg_match($emailPattern, $sendto)){
    echo "<br>You entered: <b>$sendto</b>";
    echo "<br>Please review the email address. There seems to be a problem.";
}
elseif (strlen($fileName) > 60 || strlen($authorNames) > 220 || strlen($journalName) > 60 || strlen($paperTitle) > 220) {
    echo "<br>There seems to be a problem.  Please contact Kale with your request.";
}
elseif (preg_match("/nature.com/",$sendto) ||
	    preg_match("/natureny.com/",$sendto) ||
		preg_match("/natureasia.com/",$sendto) ||
		preg_match("/naturedc.com/",$sendto) ||
		preg_match("/elsevier.com/",$sendto) ||
		preg_match("/sciencedirect.com/",$sendto) ||
		preg_match("/techset.co.uk/",$sendto)
	) {
  echo "<br><p>PHP error.  Please try again later.</p>";
}
else
{
$headers = array(
   'From' => $from,
   'Subject' => $subject,
   );

$mime = new Mail_mime($crlf);

$mime->setTXTBody($text);
$mime->addAttachment($filePath, 'application/pdf');
$body = $mime->get();
$headers = $mime->headers($headers);

$mail =& Mail::factory('mail');
$mail->send($to, $headers, $body);


if (PEAR::isError($mail)) {
  echo("<p>" . $mail->getMessage() . "</p>");
 } else {
  echo("<br><p>Your file has been sent to <b>$sendto</b>, and it should be in your email soon.</p>");
 }

}

echo "<br><br><center><a href=\"javascript:self.close()\">close window</a>";



//get ip   // http://roshanbh.com.np/2007/12/getting-real-ip-address-in-php.html
  if (!empty($_SERVER['HTTP_CLIENT_IP']))
  //check ip from share internet
  {
    $ip=$_SERVER['HTTP_CLIENT_IP'];
  }
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
  //to check ip is pass from proxy
  {
    $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  else
  {
    $ip=$_SERVER['REMOTE_ADDR'];
  }

$hostName = gethostbyaddr($ip);

// $sendto $fileName $ip $hostname

$link = dbconn();
mysql_query("INSERT INTO pubs (sendto, fileName, ip, hostname) VALUES ('$sendto','$fileName','$ip','$hostName')") or die(mysql_error());
mysql_close($link);


// http://www.php.net/reserved.variables
// http://www.drquincy.com/resources/tutorials/webserverside/sendemailattachmentphp/
// http://php.about.com/od/phpapplications/ss/form_mail_3.htm
// http://www.nyphp.org/phundamentals/email_header_injection.php
// http://www.openhosting.co.uk/articles/webdev/5918/
// http://www.bluehostforum.com/showthread.php?p=61136
// http://roshanbh.com.np/2007/12/getting-real-ip-address-in-php.html

// http://php.about.com/od/finishedphp1/qt/file_ext_PHP.htm

?>