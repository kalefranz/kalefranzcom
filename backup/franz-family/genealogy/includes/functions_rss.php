<?php
/**
 * Various functions used to generate the PhpGedView RSS feed.
 *
 * phpGedView: Genealogy Viewer
 * Copyright (C) 2002 to 2008 John Finlay and Others.  All rights reserved.
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
 * @version $Id: functions_rss.php 2884 2008-04-23 13:52:16Z fisharebest $
 * @package PhpGedView
 * @subpackage RSS
 */

if (stristr($_SERVER["SCRIPT_NAME"], basename(__FILE__))!==false) {
	print "You cannot access an include file directly.";
	exit;
}

require("config.php");
require("includes/functions_print_lists.php");

$time = client_time();
$day = date("j", $time);
$month = date("M", $time);
$year = date("Y", $time);

/**
 * Returns an ISO8601 formatted date used for the RSS feed
 *
 * @param $time the time in the UNIX time format (milliseconds since Jan 1, 1970)
 * @return SO8601 formatted date in the format of 2005-07-06T20:52:16+00:00
 */
function iso8601_date($time) {
	$tzd = date('O',$time);
	$tzd = $tzd[0] . str_pad((int) ($tzd / 100), 2, "0", STR_PAD_LEFT) .
				   ':' . str_pad((int) ($tzd % 100), 2, "0", STR_PAD_LEFT);
	$date = date('Y-m-d\TH:i:s', $time) . $tzd;
	return $date;
}

/**
 * Returns the upcoming events array used for the RSS feed.
 * Uses configuration set for the blocks. If not configured, it will default to events in the
 * next 30 days, all events for living & and not living people
 *
 * @return the array with upcoming events data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data
 */
function getUpcomingEvents() {
	global $pgv_lang, $month, $year, $day, $HIDE_LIVE_PEOPLE, $SHOW_ID_NUMBERS, $ctype, $TEXT_DIRECTION;
	global $PGV_IMAGE_DIR, $PGV_IMAGES, $GEDCOM, $PGV_BLOCKS;
	global $DAYS_TO_SHOW_LIMIT, $SERVER_URL;

	$dataArray[0] = $pgv_lang["upcoming_events"];
	$dataArray[1] = time();

	if (empty($config)) $config = $PGV_BLOCKS["print_upcoming_events"]["config"];
	if (!isset($DAYS_TO_SHOW_LIMIT)) $DAYS_TO_SHOW_LIMIT = 30;
	if (isset($config["days"])) $daysprint = $config["days"];
	else $daysprint = 30;
	if (isset($config["filter"])) $filter = $config["filter"];  // "living" or "all"
	else $filter = "all";
	if (isset($config["onlyBDM"])) $onlyBDM = $config["onlyBDM"];  // "yes" or "no"
	else $onlyBDM = "no";

	if ($daysprint < 1) $daysprint = 1;
	if ($daysprint > $DAYS_TO_SHOW_LIMIT) $daysprint = $DAYS_TO_SHOW_LIMIT;  // valid: 1 to limit

	$startjd=client_jd()+1;
	$endjd=client_jd()+$daysprint;

	$daytext=print_events_list($startjd, $endjd, $onlyBDM=='yes'?'BIRT MARR DEAT':'', $filter=='living', true);
	$daytext = str_replace(array("<br />", "<ul></ul>", " </a>"), array(" ", "", "</a>"), $daytext);
	$daytext = strip_tags($daytext, '<a><ul><li><b><span>');
	$dataArray[2]  = $daytext;
	return $dataArray;
}


/**
 * Returns the today's events array used for the RSS feed
 *
 * @return the array with todays events data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data
 */
function getTodaysEvents() {
	global $pgv_lang, $month, $year, $day, $HIDE_LIVE_PEOPLE, $SHOW_ID_NUMBERS, $ctype, $TEXT_DIRECTION;
	global $PGV_IMAGE_DIR, $PGV_IMAGES, $GEDCOM, $PGV_BLOCKS;
	global $SERVER_URL;
	global $DAYS_TO_SHOW_LIMIT;

	$dataArray[0] = $pgv_lang["on_this_day"];
	$dataArray[1] = time();

	if (empty($config)) $config = $PGV_BLOCKS["print_todays_events"]["config"];
	if (isset($config["filter"])) $filter = $config["filter"];  // "living" or "all"
	else $filter = "all";
	if (isset($config["onlyBDM"])) $onlyBDM = $config["onlyBDM"];  // "yes" or "no"
	else $onlyBDM = "no";

	$daytext=print_events_list(client_jd(), client_jd(), $onlyBDM=='yes'?'BIRT MARR DEAT':'', $filter=='living', true);
	$daytext = str_replace(array("<br />", "<ul></ul>", " </a>"), array(" ", "", "</a>"), $daytext);
	$daytext = strip_tags($daytext, '<a><ul><li><b><span>');
	$dataArray[2]  = $daytext;
	return $dataArray;
}

/**
 * Returns the GEDCOM stats.
 *
 * @return the array with GEDCOM stats data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data
 * @TODO does not print the family with most children due to the embedded html in that function.
 */
function getGedcomStats() {
	global $pgv_lang, $day, $month, $year, $PGV_BLOCKS, $GEDCOM, $GEDCOMS, $ALLOW_CHANGE_GEDCOM, $ctype, $COMMON_NAMES_THRESHOLD, $SERVER_URL, $RTLOrd, $TBLPREFIX;

	if (empty($config)) $config = $PGV_BLOCKS["print_gedcom_stats"]["config"];
	if (!isset($config['stat_indi'])) $config = $PGV_BLOCKS["print_gedcom_stats"]["config"];

	$data = "";
	$dataArray[0] = $pgv_lang["gedcom_stats"] . " - " . $GEDCOMS[$GEDCOM]["title"];

	$head = find_gedcom_record("HEAD");
	$ct=preg_match("/1 SOUR (.*)/", $head, $match);
	if ($ct>0) {
		$softrec = get_sub_record(1, "1 SOUR", $head);
		$tt= preg_match("/2 NAME (.*)/", $softrec, $tmatch);
		if ($tt>0) $title = trim($tmatch[1]);
		else $title = trim($match[1]);
		if (!empty($title)) {
			$text = strip_tags(str_replace(array("#SOFTWARE#", "#CREATED_SOFTWARE#"), $title, $pgv_lang["gedcom_created_using"]));
			$tt = preg_match("/2 VERS (.*)/", $softrec, $tmatch);
			if ($tt>0) $version = trim($tmatch[1]);
			else $version="";
			$text = strip_tags(str_replace(array("#VERSION#", "#CREATED_VERSION#"), $version, $text));
			$data .= $text;
		}
	}
	$ct=preg_match("/1 DATE (.+)/", $head, $match);
	if ($ct>0) {
		$date = trim($match[1]);
		$dataArray[1] = strtotime($date);

		$date=new GedcomDate($date);
		if (empty($title)){
			$data .= str_replace(array("#DATE#", "#CREATED_DATE#"), $date->Display(false), $pgv_lang["gedcom_created_on"]);
		} else {
			$data .= str_replace(array("#DATE#", "#CREATED_DATE#"), $date->Display(false), $pgv_lang["gedcom_created_on2"]);
		}
	}

	$stats=new stats($GEDCOM, $SERVER_URL);

	$data .= " <br />";
	if (!isset($config["stat_indi"]) || $config["stat_indi"]=="yes"){
		$data .= $stats->totalIndividuals()." - " .$pgv_lang["stat_individuals"]."<br />";
	}
	if (!isset($config["stat_fam"]) || $config["stat_fam"]=="yes"){
		$data .= $stats->totalFamilies()." - ".$pgv_lang["stat_families"]."<br />";
	}
	if (!isset($config["stat_sour"]) || $config["stat_sour"]=="yes"){
		$data .= $stats->totalSources()." - ".$pgv_lang["stat_sources"]."<br /> ";
	}
	if (!isset($config["stat_other"]) || $config["stat_other"]=="yes"){
		$data .= $stats->totalOtherRecords()." - ".$pgv_lang["stat_other"]."<br />";
	}
	if (!isset($config["stat_first_birth"]) || $config["stat_first_birth"]=="yes") {
		$data .= $pgv_lang["stat_earliest_birth"]." - ".$stats->firstBirthYear()."<br />";
	}
	if (!isset($config["stat_last_birth"]) || $config["stat_last_birth"]=="yes") {
		$data .= $pgv_lang["stat_latest_birth"]." - ".$stats->lastBirthYear()."<br />";
	}
	if (!isset($config["stat_long_life"]) || $config["stat_long_life"]=="yes") {
		$data .= $pgv_lang["stat_longest_life"]." - ".$stats->LongestLifeAge()."<br />";
	}
	if (!isset($config["stat_avg_life"]) || $config["stat_avg_life"]=="yes") {
		$data .= $pgv_lang["stat_avg_age_at_death"]." - ".$stats->averageLifespan()."<br />";
	}
	if (!isset($config["stat_most_chil"]) || $config["stat_most_chil"]=="yes") {
		$data .= $pgv_lang["stat_most_children"] . $stats->largestFamilySize().' - '.$stats->largestFamily()."<br />";
	}
	if (!isset($config["stat_avg_chil"]) || $config["stat_avg_chil"]=="yes") {
		$data .= $pgv_lang["stat_average_children"]." - ".$stats->averageChildren()."<br />";
	}
	if (!isset($config["show_common_surnames"]) || $config["show_common_surnames"]=="yes") {
		$data .="<b>".$pgv_lang["common_surnames"]."</b><br />".$stats->commonSurnames();
	}

	$data = strip_tags($data, '<a><br><b>');
	$dataArray[2] = $data;
	return $dataArray;
}

/**
 * Returns the gedcom news for the RSS feed
 *
 * @return array of GEDCOM news arrays. Each GEDCOM news array contains $itemArray[0] = title, $itemArray[1] = date,
 * 				$itemArray[2] = data, $itemArray[3] = anchor (so that the link will load the proper part of the PGV page)
 * @TODO prepend relative URL's in news items with $SERVER_URL
 */
function getGedcomNews() {
	global $pgv_lang, $PGV_IMAGE_DIR, $PGV_IMAGES, $TEXT_DIRECTION, $GEDCOM, $ctype, $VERSION, $SERVER_URL;

	$usernews = getUserNews($GEDCOM);

	$dataArray = array();
	foreach($usernews as $key=>$news) {

		$day = date("j", $news["date"]);
		$mon = date("M", $news["date"]);
		$year = date("Y", $news["date"]);
		$data = "";

		// Look for $pgv_lang, $factarray, and $GLOBALS substitutions in the News title
		$newsTitle = print_text($news["title"], 0, 2);
		$ct = preg_match("/#(.+)#/", $newsTitle, $match);
		if ($ct>0) {
			if (isset($pgv_lang[$match[1]])) $newsTitle = preg_replace("/$match[0]/", $pgv_lang[$match[1]], $newsTitle);
		}
		$itemArray[0] = $newsTitle;

		$itemArray[1] = iso8601_date($news["date"]);

		// Look for $pgv_lang, $factarray, and $GLOBALS substitutions in the News text
		$newsText = print_text($news["text"], 0, 2);
		$ct = preg_match("/#(.+)#/", $newsText, $match);
		if ($ct>0) {
			if (isset($pgv_lang[$match[1]])) $newsText = preg_replace("/$match[0]/", $pgv_lang[$match[1]], $newsText);
		}
		$ct = preg_match("/#(.+)#/", $newsText, $match);
		if ($ct>0) {
			if (isset($pgv_lang[$match[1]])) $newsText = preg_replace("/$match[0]/", $pgv_lang[$match[1]], $newsText);
			$varname = $match[1];
			if (isset($$varname)) $newsText = preg_replace("/$match[0]/", $$varname, $newsText);
		}
		$trans = get_html_translation_table(HTML_SPECIALCHARS);
		$trans = array_flip($trans);
		$newsText = strtr($newsText, $trans);
		$newsText = nl2br($newsText);
		$data .= $newsText;
		$itemArray[2] = $data;
		$itemArray[3] = $news["anchor"];
		$dataArray[] = $itemArray;

	}
	return $dataArray;
}

/**
 * Returns the top 10 surnames
 *
 * @return the array with the top 10 surname data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data
 * @TODO Possibly turn list into a <ul> list
 */
function getTop10Surnames() {
	global $pgv_lang, $GEDCOM,$SERVER_URL, $TEXT_DIRECTION;
	global $COMMON_NAMES_ADD, $COMMON_NAMES_REMOVE, $COMMON_NAMES_THRESHOLD, $PGV_BLOCKS, $ctype, $PGV_IMAGES, $PGV_IMAGE_DIR;

	$data = "";
	$dataArray = array();


	function top_surname_sort($a, $b) {
		return $b["match"] - $a["match"];
	}

	if (empty($config)) $config = $PGV_BLOCKS["print_block_name_top10"]["config"];

	if (isset($config["num"])) $numName = $config["num"];
	else $numName = 10;

	$dataArray[0] = str_replace("10", $numName, $pgv_lang["block_top10_title"]);
	$dataArray[1] = time();

	//-- cache the result in the session so that subsequent calls do not have to
	//-- perform the calculation all over again.
	if (isset($_SESSION["top10"][$GEDCOM])) {
		$surnames = $_SESSION["top10"][$GEDCOM];
	}
	else {
		$surnames = get_top_surnames($numName);

		// Insert from the "Add Names" list if not already in there
		if ($COMMON_NAMES_ADD != "") {
			$addnames = preg_split("/[,;] /", $COMMON_NAMES_ADD);
			if (count($addnames)==0) $addnames[] = $COMMON_NAMES_ADD;
			foreach($addnames as $indexval => $name) {
				//$surname = str2upper($name);
				$surname = $name;
				if (!isset($surnames[$surname])) {
					$surnames[$surname]["name"] = $name;
					$surnames[$surname]["match"] = $COMMON_NAMES_THRESHOLD;
				}
			}
		}

		// Remove names found in the "Remove Names" list
		if ($COMMON_NAMES_REMOVE != "") {
			$delnames = preg_split("/[,;] /", $COMMON_NAMES_REMOVE);
			if (count($delnames)==0) $delnames[] = $COMMON_NAMES_REMOVE;
			foreach($delnames as $indexval => $name) {
				//$surname = str2upper($name);
				$surname = $name;
				unset($surnames[$surname]);
			}
		}

		// Sort the list and save for future reference
		uasort($surnames, "top_surname_sort");
		$_SESSION["top10"][$GEDCOM] = $surnames;
	}
	if (count($surnames)>0) {
		$i=0;
		foreach($surnames as $indexval => $surname) {
			if (stristr($surname["name"], "@N.N")===false) {
				$data .= "<a href=\"" . $SERVER_URL ."indilist.php?surname=".rawurlencode($surname["name"])."\">".PrintReady($surname["name"])."</a> ";
				if ($TEXT_DIRECTION=="rtl") $data .= getRLM() . "[" . getRLM() .$surname["match"].getRLM() . "]" . getRLM() . "<br />";
				else $data .= "[".$surname["match"]."]<br />";
				$i++;
				if ($i>=$numName) break;
			}
		}
	}
	$dataArray[2] = $data;
	return $dataArray;
}

/**
 * Returns the recent changes list for the RSS feed
 *
 * @return the array with recent changes data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data
 * @TODO merge many changes from recent changes block
 * @TODO use date of most recent change instead of curent time
 */
function getRecentChanges() {
	global $pgv_lang, $factarray, $month, $year, $day, $HIDE_LIVE_PEOPLE, $SHOW_ID_NUMBERS, $ctype, $TEXT_DIRECTION;
	global $PGV_IMAGE_DIR, $PGV_IMAGES, $GEDCOM, $REGEXP_DB, $DEBUG, $ASC, $IGNORE_FACTS, $IGNORE_YEAR, $TOTAL_QUERIES, $LAST_QUERY, $PGV_BLOCKS, $SHOW_SOURCES;
	global $objectlist, $SERVER_URL;

	if ($ctype=="user") $filter = "living";
	else $filter = "all";

	if (empty($config)) $config = $PGV_BLOCKS["print_recent_changes"]["config"];
	$configDays = 30;
	if(isset($config["days"]) && $config["days"] > 0) $configDays = $config["days"];
	if (isset($config["hide_empty"])) $HideEmpty = $config["hide_empty"];
	else $HideEmpty = "no";

	$dataArray[0] = $pgv_lang["recent_changes"];
	$dataArray[1] = time();//FIXME - get most recent change time

	$recentText = "<ul>";

	$action = "today";
	$found_facts = array();
	$changes=get_recent_changes(client_jd()-$configDays);

	if (count($changes)>0) {
		$found_facts = array();
		$last_total = $TOTAL_QUERIES;
		foreach($changes as $id=>$change) {
			$gid = $change['d_gid'];
			$gedrec = find_gedcom_record($change['d_gid']);
			if (empty($gedrec)) $gedrec = find_updated_record($change['d_gid']);

			if (!empty($gedrec)) {
				$type = "INDI";
				$match = array();
				$ct = preg_match("/0 @.*@ (\w*)/", $gedrec, $match);
				if ($ct>0) $type = trim($match[1]);
				$disp = true;
				switch($type) {
					case 'INDI':
						if (($filter=="living")&&(is_dead_id($gid)==1)) $disp = false;
						else if ($HIDE_LIVE_PEOPLE) $disp = displayDetailsByID($gid);
						break;
					case 'FAM':
						if ($filter=="living") {
							$parents = find_parents_in_record($gedrec);
							if (is_dead_id($parents["HUSB"])==1) $disp = false;
							else if ($HIDE_LIVE_PEOPLE) $disp = displayDetailsByID($parents["HUSB"]);
							if ($disp) {
								if (is_dead_id($parents["WIFE"])==1) $disp = false;
								else if ($HIDE_LIVE_PEOPLE) $disp = displayDetailsByID($parents["WIFE"]);
							}
						}
						else if ($HIDE_LIVE_PEOPLE) $disp = displayDetailsByID($gid, "FAM");
						break;
					default:
						$disp = displayDetailsByID($gid, $type);
						break;
				}
				if ($disp) {
					$factrec = get_sub_record(1, "1 CHAN", $gedrec);
					$found_facts[] = array($gid, $factrec, $type);
				}
			}
		}
	}

// Start output
	if (count($found_facts)==0 and $HideEmpty=="yes") return false;
//		Print block content
	$pgv_lang["global_num1"] = $configDays;		// Make this visible
	if (count($found_facts)==0) {
		print_text("recent_changes_none", 0, 1);
	} else {
		print_text("recent_changes_some", 0, 1);
		$lastgid="";
		foreach($found_facts as $index=>$factarr) {
			if ($factarr[2]=="INDI") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid)) {
					$indirec = find_person_record($gid);
					if ($indirec) {
						if ($lastgid!=$gid) {
							$name = check_NN(get_sortable_name($gid));
							$recentText .= "<li><a href=\"" . $SERVER_URL . "individual.php?pid=$gid&amp;ged=".$GEDCOM."\"><b>".PrintReady($name)."</b>";
							if ($SHOW_ID_NUMBERS) {
								$recentText .= "&nbsp;&nbsp;";
								if ($TEXT_DIRECTION=="rtl") $recentText .= getRLM();
								$recentText .= "(".$gid.")";
								if ($TEXT_DIRECTION=="rtl") $recentText .= getRLM();
							}
							$recentText .= "</a> ";
							$lastgid=$gid;
						}
						$recentText .= $factarray["CHAN"];
						// TODO there is function in class gedcomrecord that does this.
						$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
						if ($ct>0) {
								$date=new GedcomDate($match[1]);
								$recentText .= " - " .$date->Display(false);
								$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
								if ($tt>0) {
									$recentText .= " - ".$match[1];
								}

						}
						$recentText .=  "</li>\n";
					}
				}
			}

			if ($factarr[2]=="FAM") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "FAM")) {
					$famrec = find_family_record($gid);
					if ($famrec) {
						$name = get_family_descriptor($gid);
						if ($lastgid!=$gid) {
							$recentText .= "<li><a href=\"" .$SERVER_URL . "family.php?famid=$gid&amp;ged=".$GEDCOM."\"><b>".PrintReady($name)."</b>";
							if ($SHOW_ID_NUMBERS) {
								$recentText .= "&nbsp;&nbsp;";
								if ($TEXT_DIRECTION=="rtl") $recentText .= getRLM();
								$recentText .= "(".$gid.")";
								if ($TEXT_DIRECTION=="rtl") $recentText .= getRLM();
							}
							$recentText .= "</a> ";
							$lastgid=$gid;
						}
						$recentText .= $factarray["CHAN"];
						$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
						if ($ct>0) {
							$date=new GedcomDate($match[1]);
							$recentText .= " - " .$date->Display(false);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
								$recentText .= " - ".$match[1];
							}

						}
						$recentText .=  "</li>\n";
					}
				}
			}

			if ($factarr[2]=="SOUR") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "SOUR")) {
					$sourcerec = find_source_record($gid);
					if ($sourcerec) {
						$name = get_source_descriptor($gid);
						if ($lastgid!=$gid) {
							$recentText .= "<li><a href=\"" . $SERVER_URL . "source.php?sid=$gid&amp;ged=".$GEDCOM."\"><b>".PrintReady($name)."</b>";
							if ($SHOW_ID_NUMBERS) {
								$recentText .= "&nbsp;&nbsp;";
								if ($TEXT_DIRECTION=="rtl") $recentText .= getRLM();
								$recentText .= "(".$gid.")";
								if ($TEXT_DIRECTION=="rtl") $recentText .= getRLM();
							}
							$recentText .= "</a> ";
							$lastgid=$gid;
						}
						$recentText .= $factarray["CHAN"];
						$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
						if ($ct>0) {
							$date=new GedcomDate($match[1]);
							$recentText .= " - ".$date->Display(false);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
								$recentText .= " - ".$match[1];
							}

						}
						$recentText .=  "</li>\n";
					}
				}
			}

			if ($factarr[2]=="REPO") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "REPO")) {
					$reporec = find_repo_record($gid);
					if ($reporec) {
						$name = get_repo_descriptor($gid);
						if ($lastgid!=$gid) {
							$recentText .= "<li><a href=\"" . $SERVER_URL . "repo.php?rid=$gid&amp;ged=".$GEDCOM."\"><b>".PrintReady($name)."</b>";
							if ($SHOW_ID_NUMBERS) {
								$recentText .= "&nbsp;&nbsp;";
								if ($TEXT_DIRECTION=="rtl") $recentText .= getRLM();
								$recentText .= "(".$gid.")";
								if ($TEXT_DIRECTION=="rtl") $recentText .= getRLM();
							}
							$recentText .= "</a> ";
							$lastgid=$gid;
						}
						$recentText .= $factarray["CHAN"];
						$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
						if ($ct>0) {
							$date=new GedcomDate($match[1]);
							$recentText .= " - ".$date->Display(false);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
								$recentText .=  " - ".$match[1];
							}

						}
						$recentText .=  "</li>\n";
					}
				}
			}
			if ($factarr[2]=="OBJE") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "OBJE")) {
					$mediarec = find_media_record($gid);
					if ($mediarec) {
						if (isset($objectlist[$gid]["title"]) && $objectlist[$gid]["title"] != "") $title=$objectlist[$gid]["title"];
						else $title = $objectlist[$gid]["file"];
						$SearchTitle = preg_replace("/ /","+",$title);
						if ($lastgid!=$gid) {
 							$recentText .= "<li><a href=\"" . $SERVER_URL . "medialist.php?action=filter&amp;search=yes&amp;filter=$SearchTitle&amp;ged=".$GEDCOM."\"><b>".PrintReady($title)."</b>";
							if ($SHOW_ID_NUMBERS) {
								$recentText .= "&nbsp;&nbsp;";
								if ($TEXT_DIRECTION=="rtl") $recentText .= getRLM();
								$recentText .= "(".$gid.")";
								if ($TEXT_DIRECTION=="rtl") $recentText .= getRLM();
							}
							$recentText .= "</a> ";
							$lastgid=$gid;
						}
						$recentText .= $factarray["CHAN"];
						$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
						if ($ct>0) {
							$date=new GedcomDate($match[1]);
							$recentText .= " - ".$date->Display(false);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
								$recentText .= " - ".$match[1];
							}

						}
						$recentText .= "</li>\n";
					}
				}
			}
		}

	}
	$recentText .= "</ul>";

	$recentText = str_replace(array("<br />", "<ul></ul>", " </a>"), array(" ", "", "</a>"), $recentText);
	$recentText = strip_tags($recentText, '<a><ul><li><b><span>');

	$dataArray[2] = $recentText;
	return $dataArray;
}

/**
 * Returns a random media for the RSS feed
 *
 * @return the array with random media data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data, $dataArray[3] = file path, $dataArray[4] = mime type,
 *				$dataArray[5] = file size, $dataArray[5] = media title
 */
function getRandomMedia() {
	global $pgv_lang, $GEDCOM, $foundlist, $MULTI_MEDIA, $TEXT_DIRECTION, $PGV_IMAGE_DIR, $PGV_IMAGES;
	global $MEDIA_EXTERNAL, $MEDIA_DIRECTORY, $SHOW_SOURCES;
	global $MEDIATYPE, $THUMBNAIL_WIDTH, $USE_MEDIA_VIEWER, $DEBUG;
	global $PGV_BLOCKS, $ctype, $action;
	global $PGV_IMAGE_DIR, $PGV_IMAGES;
	if (empty($config)) $config = $PGV_BLOCKS["print_random_media"]["config"];
	if (isset($config["filter"])) $filter = $config["filter"];  // indi, event, or all
	else $filter = "all";

	$dataArray[0] = $pgv_lang["random_picture"];
	$dataArray[1] = time();//FIXME - get most recent change time

	$randomMedia = "";


	if (!$MULTI_MEDIA) return;
	$medialist = array();
	$foundlist = array();

	$medialist = get_medialist(false, '', true, true);
	$ct = count($medialist);
	if ($ct>0) {
		$i=0;
		$disp = false;
		//-- try up to 40 times to get a media to display
		while($i<40) {
			$error = false;
			$value = array_rand($medialist);
			//if (isset($DEBUG)&&($DEBUG==true)) {
			//	print "<br />";print_r($medialist[$value]);print "<br />";
			//	print "Trying ".$medialist[$value]["XREF"]."<br />\n";
			//}
			$links = $medialist[$value]["LINKS"];
			$disp = ($medialist[$value]["EXISTS"]>0) && $medialist[$value]["LINKED"] && $medialist[$value]["CHANGE"]!="delete" ;
			//if (isset($DEBUG)&&($DEBUG==true) && !$disp && !$error) {$error = true; print "<span class=\"error\">".$medialist[$value]["XREF"]." File does not exist, or is not linked to anyone, or is marked for deletion.</span><br />\n";}

			$disp &= displayDetailsByID($value["XREF"], "OBJE");
			$disp &= !FactViewRestricted($value["XREF"], $value["GEDCOM"]);

			//if (isset($DEBUG)&&($DEBUG==true) && !$disp && !$error) {$error = true; print "<span class=\"error\">".$medialist[$value]["XREF"]." Failed to pass privacy</span><br />\n";}

			$isExternal = isFileExternal($medialist[$value]["FILE"]);

			if (!$isExternal) $disp &= ($medialist[$value]["THUMBEXISTS"]>0);
			//if (isset($DEBUG)&&($DEBUG==true) && !$disp && !$error) {$error = true; print "<span class=\"error\">".$medialist[$value]["XREF"]." thumbnail file could not be found</span><br />\n";}

			// Filter according to format and type  (Default: unless configured otherwise, don't filter)
			if (!empty($medialist[$value]["FORM"]) && isset($config["filter_".$medialist[$value]["FORM"]]) && $config["filter_".$medialist[$value]["FORM"]]!="yes") $disp = false;
			if (!empty($medialist[$value]["TYPE"]) && isset($config["filter_".$medialist[$value]["TYPE"]]) && $config["filter_".$medialist[$value]["TYPE"]]!="yes") $disp = false;
			//if (isset($DEBUG)&&($DEBUG==true) && !$disp && !$error) {$error = true; print "<span class=\"error\">".$medialist[$value]["XREF"]." failed Format or Type filters</span><br />\n";}

			if ($disp && count($links) != 0){
				foreach($links as $key=>$type) {
					$gedrec = find_gedcom_record($key);
					$disp &= !empty($gedrec);
					//-- source privacy is now available through the display details by id method
					// $disp &= $type!="SOUR";
					$disp &= displayDetailsById($key, $type);
				}
				//if (isset($DEBUG)&&($DEBUG==true)&&!$disp && !$error) {$error = true; print "<span class=\"error\">".$medialist[$value]["XREF"]." failed link privacy</span><br />\n";}
				if ($disp && $filter!="all") {
					// Apply filter criteria
					$ct = preg_match("/0\s(@.*@)\sOBJE/", $medialist[$value]["GEDCOM"], $match);
					$objectID = $match[1];
					$ct2 = preg_match("/(\d)\sOBJE\s{$objectID}/", $gedrec, $match2);
					if ($ct2>0) {
						$objectRefLevel = $match2[1];
						if ($filter=="indi" && $objectRefLevel!="1") $disp = false;
						if ($filter=="event" && $objectRefLevel=="1") $disp = false;
						//if (isset($DEBUG)&&($DEBUG==true)&&!$disp && !$error) {$error = true; print "<span class=\"error\">".$medialist[$value]["XREF"]." failed to pass config filter</span><br />\n";}
					}
					else $disp = false;
				}
			}
			//-- leave the loop if we find an image that works
			if ($disp) {
				break;
			}
			//-- otherwise remove the private media item from the list
			else {
				//if (isset($DEBUG)&&($DEBUG==true)) print "<span class=\"error\">".$medialist[$value]["XREF"]." Will not be shown</span><br />\n";
				unset($medialist[$value]);
			}
			//-- if there are no more media items, then try to get some more
			if (count($medialist)==0) $medialist = get_medialist(false, '', true, true);
			$i++;
		}
		if (!$disp) return false;

		$imgsize = findImageSize($medialist[$value]["FILE"]);
		$imgwidth = $imgsize[0]+40;
		$imgheight = $imgsize[1]+150;

		$mediaid = $medialist[$value]["XREF"];
		$randomMedia .= "<a href=\"mediaviewer.php?mid=".$mediaid."\">";
		$mediaTitle = "";
		if (!empty($medialist[$value]["TITL"])) {
			$mediaTitle = PrintReady($medialist[$value]["TITL"]);
		} else {
			$mediaTitle = basename($medialist[$value]["FILE"]);
		}
		//if ($block) {
			$randomMedia .= "<img src=\"".$medialist[$value]["THUMB"]."\" border=\"0\" class=\"thumbnail\"";
			if ($isExternal) print " width=\"".$THUMBNAIL_WIDTH."\"";
			$randomMedia .= " alt=\"" . $mediaTitle . "\" title=\"" . $mediaTitle . "\" />";
		/*} else {
			print "<img src=\"".$medialist[$value]["FILE"]."\" border=\"0\" class=\"thumbnail\" ";
			$imgsize = findImageSize($medialist[$value]["FILE"]);
			if ($imgsize[0] > 175) print "width=\"175\" ";
			print " alt=\"" . $mediaTitle . "\" title=\"" . $mediaTitle . "\" />";
		}*/
		$randomMedia .= "</a>\n";
		$randomMedia .= "<br />";
		$randomMedia .= "<a href=\"mediaviewer.php?mid=".$mediaid."\">";
		$randomMedia .= "<b>". $mediaTitle ."</b>";
		$randomMedia .= "</a>";

		$dataArray[2] = $randomMedia;
		$dataArray[3] = $medialist[$value]["FILE"];
		$dataArray[4] = image_type_to_mime_type($imgsize[2]);
		if ($dataArray[4] == false){
			$dataArray[4] ="";
			$parts = pathinfo($filename);
			if (isset ($parts["extension"])) {
				$ext = strtolower($parts["extension"]);
			} else {
				$ext="";
			}
			if($ext == "pdf"){
				$dataArray[4] = "application/pdf";
			}
		}
		$dataArray[5] = @filesize($medialist[$value]["FILE"]);
		$dataArray[6] = $mediaTitle;
		//$dataArray[7] = $medialist[$value]["XREF"];
	}
	return $dataArray;
}


?>
