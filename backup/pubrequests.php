<?php
include('../phpfncs.php');

$sorton = $_GET['sort'];

$link = dbconn();

$data = mysql_query("SELECT * FROM pubs") or die(mysql_error()); 

echo "<html><head><title></title></head><body>\n";

Print "<table border cellpadding=3>\n";
Print "<tr><th>id</th><th>timestamp</th><th>sendto</th><th>fileName</th><th>ip</th><th>hostname</th></tr>\n";
while($info = mysql_fetch_array( $data )) 
{ 
Print "<tr>"; 
Print "<td>".$info['id'] . "</td> "; 
Print "<td>".$info['time_stamp'] . "</td> ";
Print "<th>".$info['sendto'] . "</th> ";
Print "<th>".$info['fileName'] . "</th> ";
Print "<td>".$info['ip'] . "</td> "; 
Print "<td>".$info['hostname'] . "</td></tr>\n"; 
} 
Print "</table>\n"; 

Print "<br><br>\n";

if (empty($sorton))
    $data = mysql_query("SELECT * FROM linkcount ORDER BY lastupdate DESC") or die(mysql_error()); 
else
    $data = mysql_query("SELECT * FROM linkcount ORDER BY " . $sorton . " DESC") or die(mysql_error()); 

Print "<table border cellpadding=3>";
Print "<tr><th><a href=\"pubrequests.php?sort=count\">count</a></th><th><a href=\"pubrequests.php?sort=link\">link</a></th><th><a href=\"pubrequests.php?sort=lastupdate\">lastupdate</a></th></tr>\n";
while($info = mysql_fetch_array( $data )) 
{ 
Print "<tr>"; 
Print "<td>".$info['count'] . "</td> "; 
Print "<td>".$info['link'] . "</td> ";
Print "<th>".$info['lastupdate'] . "</th></tr>\n"; 
} 
Print "</table>\n"; 

echo "</body></html>";


mysql_close($link);

?>