<?php
/*
Author: itsmeJAY
Year: 2019
Version tested: 1.8.20
Contact and support exclusively in the MyBB.de Forum (German Community - https://www.mybb.de/). 
*/


if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

// Hooks and Functions
$plugins->add_hook("postbit", "bday");


function bday_info()
{
global $db, $lang;

// Sprachdatei laden
$lang->load("bday");

	return array(
		"name"			=> "$lang->bday_title",
		"description"	=> "$lang->bday_desc",
		"website"		=> "https://www.mybb.de/forum/user-10220.html",
		"author"		=> "itsmeJAY",
		"authorsite"	=> "https://www.mybb.de/forum/user-10220.html",
		"version"		=> "2.0",
		"guid" 			=> "",
		"codename"		=> "bday",
		"compatibility" => "18*"
	);
}

function bday_activate()
{
  global $db, $mybb, $lang;
  
    // Sprachdatei laden
    $lang->load("bday");

  $setting_group = array(
      'name' => 'bday',
      'title' => "$lang->bday_set_name",
      'description' => "$lang->bday_set_desc",
      'disporder' => 5,
      'isdefault' => 0
  );
  
  $gid = $db->insert_query("settinggroups", $setting_group);
  
  // Einstellungen

  $setting_array = array(
    // hurl_plugin_enable oder nicht?
    'bday_active' => array(
        'title' => "$lang->bday_enable_title",
        'description' => "$lang->bday_enable_desc",
        'optionscode' => 'yesno',
        'value' => 1, // Default
        'disporder' => 1
    ),
	    'bday_imgpfath' => array(
        'title' => "$lang->bday_img",
        'description' => "$lang->bday_img_desc",
        'optionscode' => 'text',
        'value' => "images/bdaycake.png", // Default
        'disporder' => 2
    ),
		'bday_text' => array(
        'title' => "$lang->bday_text",
        'description' => "$lang->bday_text_desc",
        'optionscode' => 'text',
        'value' => "<strong>Happy Birthday {username}!</strong>", // Default
        'disporder' => 3
    ),
		'bday_pic_height' => array(
        'title' => "$lang->bday_pic_height",
        'description' => "$lang->bday_pic_height_desc",
        'optionscode' => 'numeric',
        'value' => "16", // Default
        'disporder' => 4
    ),
		'bday_pic_width' => array(
        'title' => "$lang->bday_pic_width",
        'description' => "$lang->bday_pic_width_desc",
        'optionscode' => 'numeric',
        'value' => "16", // Default
        'disporder' => 5
    ),
		'bday_cong_pm' => array(
        'title' => "$lang->bday_cong_active",
        'description' => "$lang->bdayc_cong_desc",
        'optionscode' => 'yesno',
        'value' => 1, // Default
        'disporder' => 6
    ),
		'bday_pm_imgpfath' => array(
        'title' => "$lang->bday_pm_imgpfath",
        'description' => "$lang->bday_pm_img_desc",
        'optionscode' => 'text',
        'value' => "images/congnow.png", // Default
        'disporder' => 7
    ),
		'bday_cong_subject' => array(
        'title' => "$lang->bday_pm_subject",
        'description' => "$lang->bday_pm_subject_desc",
        'optionscode' => 'text',
        'value' => "Happy Birthday to you", // Default
        'disporder' => 8
    ),
		'bday_cong_message' => array(
        'title' => "$lang->bday_cong_message",
        'description' => "$lang->bday_cong_message_desc",
        'optionscode' => 'textarea',
        'value' => "Hello {username} \n\nHappy birthday! I hope all your birthday wishes and dreams come true. :heart: \n\nBest Wishes, \n{sender}", // Default
        'disporder' => 9
    ),
);

    // Einstellungen in Datenbank speichern
    foreach($setting_array as $name => $setting)
    {
        $setting['name'] = $name;
        $setting['gid'] = $gid;
    
        $db->insert_query('settings', $setting);
    }

    // Rebuild Settings! :-)
    rebuild_settings();

}

function bday_deactivate()
{
  global $db;

  $db->delete_query('settings', "name IN ('bday_active','bday_imgpfath','bday_text','bday_pic_height','bday_pic_width', 'bday_cong_pm', 'bday_cong_subject', 'bday_cong_message', 'bday_pm_imgpfath')");
  $db->delete_query('settinggroups', "name = 'bday'");
  
  // Rebuild Settings! :-)
  rebuild_settings();

}

function bday(&$post) {
	global $db, $mybb;
	
	// Datenbank-Query
	$query = $db->simple_select("users", "birthday, birthdayprivacy, receivepms", "uid='".$post['uid']."'", array(
    "limit" => 1
    ));
        
    $results = $db->fetch_array($query);

	// Benutzer Geburtstag
	$userbday = explode("-", $results['birthday']);
	$userbday = $userbday[0]."-".$userbday[1];
	
	// Datum heute
	$zeit = date("j-n", time());
	
	// Username und Message aus Settings umwandeln
	$shortcode = "{username}";
	$mybb->settings['bday_text'] = str_replace($shortcode, ucfirst($post['username']), $mybb->settings['bday_text']);
	$mybb->settings['bday_cong_message'] = str_replace($shortcode, ucfirst($post['username']), $mybb->settings['bday_cong_message']);
	$mybb->settings['bday_cong_message'] = str_replace("{sender}", ucfirst($mybb->user['username']), $mybb->settings['bday_cong_message']);
	$mybb->settings['bday_cong_message'] = str_replace("\n", "%0D%0A", $mybb->settings['bday_cong_message']);
	
	// VARs
	$bdaycake = "<img src=\"".$mybb->settings['bday_imgpfath']."\" width=\"".$mybb->settings['bday_pic_width']."\" height=\"".$mybb->settings['bday_pic_height']."\">";
	$pmicon = "<a href=\"private.php?action=send&uid=".$post['uid']."&subject=".$mybb->settings['bday_cong_subject']."&message=".$mybb->settings['bday_cong_message']."\"><img title=\"Send congratulations to ".$post['username']."\" src=\"".$mybb->settings['bday_pm_imgpfath']."\" width=\"".$mybb->settings['bday_pic_width']."\" height=\"".$mybb->settings['bday_pic_height']."\"></a>";
	
  if ($userbday === $zeit && $results['birthdayprivacy'] === "all" && $mybb->settings['bday_active'] === "1" && $mybb->settings['bday_cong_pm'] === "0") {
  $post['userstars'] .= "<br/>" . $bdaycake;
  $post['userstars'] .= " " . $mybb->settings['bday_text'];
  }
  
  if ($userbday === $zeit && $results['birthdayprivacy'] === "all" && $mybb->settings['bday_cong_pm'] === "1" && $results['receivepms'] === "1" && $mybb->settings['bday_active'] === "1") {
  $post['userstars'] .= "<br/>" . $pmicon . " " . $bdaycake . " " . $mybb->settings['bday_text'];
  }
}

?>
