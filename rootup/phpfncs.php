<?php

function dbconn() {
if(!include_once('sfiles/lists.php')) {
die('Error include file...');
}

if (!$link = mysql_connect($db1_hostname,$db1_username,$db1_password)) {
die('Error connecting...');
}
if (!mysql_select_db($db1_tab1)) {
die('Error selecting...');
}

return $link;
} 



?>