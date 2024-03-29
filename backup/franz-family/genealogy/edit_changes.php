<?php
/**
 * Interface to review/accept/reject changes made by editing online.
 *
 * phpGedView: Genealogy Viewer
 * Copyright (C) 2002 to 2003  John Finlay and Others
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package PhpGedView
 * @subpackage Edit
 * @version $Id: edit_changes.php 2698 2008-03-10 16:40:32Z fisharebest $
 */

require "config.php";
require "includes/functions_edit.php";
require "includes/functions_import.php";
require $INDEX_DIRECTORY."pgv_changes.php";

if (!PGV_USER_CAN_ACCEPT) {
	header("Location: login.php?url=edit_changes.php");
	exit;
}

if (isset($_REQUEST['action'])) $action = $_REQUEST['action'];
if (isset($_REQUEST['cid'])) $cid = $_REQUEST['cid'];
if (isset($_REQUEST['index'])) $index = $_REQUEST['index'];
if (isset($_REQUEST['ged'])) $ged = $_REQUEST['ged'];
if (empty($action)) $action="";

print_simple_header($pgv_lang["review_changes"]);
?>
<script language="JavaScript" type="text/javascript">
<!--
	function show_gedcom_record(xref) {
		var recwin = window.open("gedrecord.php?fromfile=1&pid="+xref, "_blank", "top=50,left=50,width=600,height=400,scrollbars=1,scrollable=1,resizable=1");
	}
	function showchanges() {
		window.location = '<?php print $SCRIPT_NAME; ?>';
	}

	function show_diff(diffurl) {
		window.opener.location = diffurl;
		return false;
	}
//-->
</script>
<?php
print "<div class=\"center\">\n";
print "<span class=\"subheaders\">";
print $pgv_lang["review_changes"];
print "</span><br /><br />\n";

if (!isset($cid)) $cid = "";

if ($action=="undo" && isset($pgv_changes[$cid])) {
	if (undo_change($cid, $index)) {
		print "<br /><br /><b>";
		print $pgv_lang["undo_successful"];
		print "</b><br /><br />";
	}
}
if ($action=="undoall") {
	//-- alert that we only want to save the file and changes once
	$manual_save = true;
	$temp_changes = $pgv_changes;
	foreach($temp_changes as $cid=>$changes) {
		$change = $changes[0];
		if ($change["gedcom"]==$ged) undo_change($cid, 0);
	}
	write_changes();
	$manual_save = false;
	print "<br /><br /><b>";
	print $pgv_lang["undo_successful"];
	print "</b><br /><br />";
}

if ($action=="accept" && isset($pgv_changes[$cid])) {
	if (accept_changes($cid)) {
		print "<br /><br /><b>";
		print $pgv_lang["accept_successful"];
		print "</b><br /><br />";
	}
}
if ($action=="acceptall") {
	$temp_changes = $pgv_changes;
	//-- only save the file and changes once
	$manual_save = true;
	foreach($temp_changes as $cid=>$changes) {
		for($i=0; $i<count($changes); $i++) {
			$change = $changes[$i];
			if ($change["gedcom"]==$ged) accept_changes($cid);
		}
	}
	if ($SYNC_GEDCOM_FILE) write_file();
	write_changes();
	$manual_save = false;
	print "<br /><br /><b>";
	print $pgv_lang["accept_successful"];
	print "</b><br /><br />";
}

if (count($pgv_changes)==0) {
	print "<br /><br /><b>";
	print $pgv_lang["no_changes"];
	print "</b>";
}
else {
	$output = "<br /><table class=\"list_table\">";
	$output .= "<tr><td class=\"list_value $TEXT_DIRECTION\">";
	$changedgedcoms = array();
	foreach ($pgv_changes as $cid=>$changes) {
		foreach ($changes as $i=>$change) {
			if ($i==0) {
				$changedgedcoms[$change["gedcom"]] = true;
				$GEDCOM=$change["gedcom"];
				$gedrec=find_gedcom_record($change['gid']);
				if (empty($gedrec))
					$gedrec=$change['undo'];
				if (preg_match("/0\s*@\w+@\s*(\w+)/", $gedrec, $match))
					$type=$match[1];
				else
					$type="INDI";
				switch ($type) {
				case 'INDI':
					$names = get_indi_names($gedrec);
					$output .= "<b>".PrintReady(check_NN($names[0][0]))."</b> " . getLRM() . "(".$change["gid"].")" . getLRM()  . "<br />";
					$output .= "<a href=\"javascript:;\" onclick=\"return show_diff('individual.php?pid=".$change["gid"]."&amp;ged=".$change["gedcom"]."&amp;show_changes=yes');\">".$pgv_lang["view_change_diff"]."</a> | ";
					break;
				case 'FAM':
					$output.= "<b>".PrintReady(get_family_descriptor($change["gid"]))."</b> " . getLRM() . "(" . $change["gid"]. ")" . getLRM() . "<br />";
					$output.= "<a href=\"javascript:;\" onclick=\"return show_diff('family.php?famid=".$change["gid"]."&amp;ged=".$change["gedcom"]."&amp;show_changes=yes');\">".$pgv_lang["view_change_diff"]."</a> | ";
					break;
				case 'SOUR':
					$name=get_gedcom_value("ABBR", 1, $gedrec);
					if (empty($name))
						$name=get_gedcom_value("TITL", 1, $gedrec);
					$output.="<b>".PrintReady($name)."</b> " . getLRM() . "(".$change["gid"].")" . getLRM()  . "<br />";
					$output.="<a href=\"javascript:;\" onclick=\"return show_diff('source.php?sid=".$change["gid"]."&amp;ged=".$change["gedcom"]."&amp;show_changes=yes');\">".$pgv_lang["view_change_diff"]."</a> | ";
					break;
				case 'OBJE':
					$name = get_gedcom_value("TITL", 1, $gedrec);
					if (empty($name)) $name = get_gedcom_value("TITL", 2, $gedrec);
					$output.="<b>".PrintReady($name)."</b> " . getLRM() . "(".$change["gid"].")" . getLRM()  . "<br />";
					$output.="<a href=\"javascript:;\" onclick=\"return show_diff('mediaviewer.php?mid=".$change["gid"]."&amp;ged=".$change["gedcom"]."&amp;show_changes=yes');\">".$pgv_lang["view_change_diff"]."</a> | ";
					break;
				default:
					$output.="<b>".$factarray[$type]."</b> " . getLRM() . "(".$change["gid"].")" . getLRM() ."<br />";
					break;
				}
				$output.="<a href=\"javascript:show_gedcom_record('".$change["gid"]."');\">".$pgv_lang["view_gedcom"]."</a> | ";
				$output.="<a href=\"javascript:;\" onclick=\"return edit_raw('".$change["gid"]."');\">".$pgv_lang["edit_raw"]."</a><br />";
				$output.="<div class=\"indent\">\n";
				$output.=$pgv_lang["changes_occurred"]."<br />\n";
				$output.="<table class=\"list_table\">\n<tr>";
				$output.="<td class=\"list_label\">".$pgv_lang["accept"]."</td>";
				$output.="<td class=\"list_label\">".$pgv_lang["type"]."</td>";
				$output.="<td class=\"list_label\">".$pgv_lang["username"]."</td>";
				$output.="<td class=\"list_label\">".$pgv_lang["date"]."</td>";
				$output.="<td class=\"list_label\">GEDCOM</td>";
				$output.="<td class=\"list_label\">".$pgv_lang["undo"]."</td>";
				$output.="</tr>\n";
			}
			if ($i==count($changes)-1) {
				$output .= "<td class=\"list_value $TEXT_DIRECTION\"><a href=\"edit_changes.php?action=accept&amp;cid=$cid\">".$pgv_lang["accept"]."</a></td>\n";
			} else {
				$output .= "<td class=\"list_value $TEXT_DIRECTION\">&nbsp;</td>";
			}
			$output .= "<td class=\"list_value $TEXT_DIRECTION\"><b>".$pgv_lang[$change["type"]]."</b></td>\n";
			$output .= "<td class=\"list_value $TEXT_DIRECTION\"><a href=\"javascript:;\" onclick=\"return reply('".$change["user"]."','".$pgv_lang["review_changes"]."')\" alt=\"".$pgv_lang["message"]."\">";
			if ($user_id=get_user_id($change["user"])) {
				$output.=PrintReady(getUserFullName($user_id));
			}
 			$output .= PrintReady("&nbsp;(".$change["user"].")")."</a></td>\n";
 			$output .= "<td class=\"list_value $TEXT_DIRECTION\">".format_timestamp($change["time"])."</td>\n";
			$output .= "<td class=\"list_value $TEXT_DIRECTION\">".$change["gedcom"]."</td>\n";
			if ($i==count($changes)-1) {
				$output .= "<td class=\"list_value $TEXT_DIRECTION\"><a href=\"edit_changes.php?action=undo&amp;cid=$cid&amp;index=$i\">".$pgv_lang["undo"]."</a></td>";
			} else {
				$output .= "<td class=\"list_value $TEXT_DIRECTION\">&nbsp;</td>";
			}
			$output .= "</tr>\n";
			if ($i==count($changes)-1) {
				$output .= "</table>\n";
				$output .= "</div><br />";
			}
		}
	}
	$output .= "</td></tr></table>";

	//-- Now for the global Action bar:
	$output2 = "<br /><table class=\"list_table\">";
	// Row 1 column 1: title "Accept all"
	$output2 .= "<tr><td class=\"list_label\">".$pgv_lang["accept_all"]."</td>";
	// Row 1 column 2: separator
	$output2 .= "<td class=\"list_label width25\">&nbsp;</td>";
	// Row 1 column 3: title "Undo all"
	$output2 .= "<td class=\"list_label\">".$pgv_lang["undo_all"]."</td></tr>";

	// Row 2 column 1: action "Accept all"
	$output2 .= "<tr><td class=\"list_value\">";
	$count = 0;
	foreach($changedgedcoms as $ged=>$value) {
		if ($count!=0) $output2.="<br /><br />";
		$output2 .= "<a href=\"edit_changes.php?action=acceptall&amp;ged=$ged\">$ged - ".$pgv_lang["accept_all"]."</a>\n";
		$count ++;
	}
	$output2 .= "</td>";
	// Row 2 column 2: separator
	$output2 .= "<td class=\"list_value width25\">&nbsp;</td>";
	// Row 2 column 3: action "Undo all"
	$output2 .= "<td class=\"list_value\">";
	$count = 0;
	foreach($changedgedcoms as $ged=>$value) {
		if ($count!=0) $output2.="<br /><br />";
		$output2 .= "<a href=\"edit_changes.php?action=undoall&amp;ged=$ged\" onclick=\"return confirm('".$pgv_lang["undo_all_confirm"]."');\">$ged - ".$pgv_lang["undo_all"]."</a>\n";
		$count ++;
	}
	$output2 .= "</td></tr>";
	$output2 .= "</table>";

	print "<center>";
	print $pgv_lang["accept_gedcom"]."<br />";
	print $output2;
	print $output;
	print $output2;
	print "</center>";
}


print "<br /><br />\n</div>\n";
print "<center><a href=\"javascript:;\" onclick=\"if (window.opener.showchanges) window.opener.showchanges(); window.close();\">".$pgv_lang["close_window"]."</a><br /></center>\n";
print_simple_footer();
?>
