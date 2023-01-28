<?php
include('../phpfncs.php');

$link = dbconn();

$data = mysql_query("SELECT * FROM pubs") or die(mysql_error()); 
Print "<table border cellpadding=3>";
Print "<tr><th>id</th><th>timestamp</th><th>sendto</th><th>fileName</th><th>ip</th><th>hostname</th></tr><br>";
while($info = mysql_fetch_array( $data )) 
{ 
Print "<tr>"; 
Print "<td>".$info['id'] . "</td> "; 
Print "<td>".$info['time_stamp'] . "</td> ";
Print "<th>".$info['sendto'] . "</th> ";
Print "<th>".$info['fileName'] . "</th> ";
Print "<td>".$info['ip'] . "</td> "; 
Print "<td>".$info['hostname'] . "</td></tr><br>"; 
} 
Print "</table>"; 

Print "<br><br>";

$data = mysql_query("SELECT * FROM linkcount ORDER BY lastupdate DESC") or die(mysql_error()); 
Print "<table border cellpadding=3>";
Print "<tr><th>count</th><th>link</th><th>lastupdate</th></tr><br>";
while($info = mysql_fetch_array( $data )) 
{ 
Print "<tr>"; 
Print "<td>".$info['count'] . "</td> "; 
Print "<td>".$info['link'] . "</td> ";
Print "<th>".$info['lastupdate'] . "</th></tr><br>"; 
} 
Print "</table>"; 




mysql_close($link);

?>