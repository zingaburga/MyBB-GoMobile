<?php

global $mybb;
define("GOMOBILE_ACP_CONFIG_URL", "index.php?module=config" . ($mybb->version_code >= 1500 ? "-" : "/"));

$plugins->add_hook("admin_config_settings_begin", "gomobile_admin_settings");

// we'll stick the template modification we do in a define for convenience purposes...
define('GOMOBILE_TPL_MOD', '<img src="{$mybb->settings[\'bburl\']}/images/mobile/posted_{$post[\'mobile\']}.png" alt="" width="{$post[\'mobile\']}8" height="{$post[\'mobile\']}8" title="Posted from GoMobile (when icon is displayed)" style="vertical-align: middle;" /> ');

function gomobile_info()
{
	global $lang;
	$lang->load('gomobile');

	// Plugin information
	return array(
		"name"			=> $lang->gomobile,
		"description"	=> $lang->gomobile_desc,
		"website"		=> "http://www.mybbgm.com",
		"author"		=> "MyBB GoMobile",
		"authorsite"	=> "http://www.mybbgm.com",
		"version"		=> "1.0 Beta 3",
		"compatibility" => "14*, 16*"
	);
}

function gomobile_install()
{
	global $db, $mybb, $lang;
	$lang->load('gomobile');

	// Add a column to the posts & threads tables for tracking mobile posts
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."posts ADD mobile {$tinyint}(1) NOT NULL default '0'");
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."threads ADD mobile {$tinyint}(1) NOT NULL default '0'");

	// And another to the users table for options
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."users ADD usemobileversion {$tinyint}(1) NOT NULL default '1'");

	// First, check that our theme doesn't already exist
	$query = $db->simple_select("themes", "tid", "LOWER(name) LIKE 'gomobile 1.0%'");
	if($db->num_rows($query))
	{
		// We already have the GoMobile theme installed
		$theme = $db->fetch_field($query, "tid");
	}
	else
	{
		// Import the theme for our users
		$theme = MYBB_ROOT."inc/plugins/gomobile/gomobile_theme.xml";
		if(!file_exists($theme))
		{
			flash_message("Upload the GoMobile Theme to the plugin directory (./inc/plugins/) before continuing.", "error");
			admin_redirect(GOMOBILE_ACP_CONFIG_URL."plugins");
		}

		$contents = @file_get_contents($theme);
		if($contents)
		{
			$options = array(
				'no_stylesheets' => 0,
				'no_templates' => 0,
				'version_compat' => 1,
				'parent' => 1,
				'force_name_check' => true,
			);

			require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
			$theme = import_theme_xml($contents, $options);
		}
	}

	// Get our settings ready
	$setting_group = array
	(
		"name" => "gomobile",
		"title" => $db->escape_string($lang->setting_group_gomobile),
		"description" => $db->escape_string($lang->setting_group_gomobile_desc),
		"disporder" => "1",
		"isdefault" => "0",
	);
	$gid = $db->insert_query("settinggroups", $setting_group);

	$disporder = 0;
	foreach(array(
		"mobile_name"       => array("text", $mybb->settings['bbname']),
		"redirect_enabled"  => array("yesno", 0),
		"redirect_location" => array("text", "index.php"),
		"theme_id"          => array("text", $theme),
		"homename"          => array("text", $mybb->settings['homename']),
		"homelink"          => array("text", $mybb->settings['homeurl']),
		"ua_list"           => array("textarea",
"/ip[ho].+?mobile.+?safari/i
/mobile/i
/Android/i
/Opera Mini/i
/BlackBerry/i
/IEMobile/i
/Windows Phone/i
/HTC/i
/Nokia/i
/Netfront/i
/SmartPhone/i
/Symbian/i
/SonyEricsson/i
/AvantGo/i
/DoCoMo/i
/Pre\//i
/UP\.Browser/i")
	) as $name => $opts) {
		$lang_title = "setting_gomobile_{$name}";
		$lang_desc = "setting_gomobile_{$name}_desc";
		$db->insert_query("settings", array(
			"name"        => "gomobile_$name",
			"title"       => $db->escape_string($lang->$lang_title),
			"description" => $db->escape_string($lang->$lang_desc),
			"optionscode" => $opts[0],
			"value"       => $db->escape_string($opts[1]),
			"disporder"   => ++$disporder,
			"gid"         => $gid,
		));
	}

	rebuild_settings();
}

function gomobile_is_installed()
{
	global $db;
	return $db->table_exists("gomobile");
}

function gomobile_activate()
{
	// Edit existing templates (shows when posts are from GoMobile)
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("postbit_posturl", '#\\<span.*?\\{\\$lang-\\>postbit_post\\}#', str_replace('$', '\\$', GOMOBILE_TPL_MOD).' $0');
}
function gomobile_deactivate()
{
	// Can the template edits we made earlier
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("postbit_posturl", '#'.preg_quote(GOMOBILE_TPL_MOD).'#', '', 0);
}

function gomobile_uninstall()
{
	global $db;

	// Clean up the users, posts & threads tables
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."posts DROP COLUMN mobile");
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."threads DROP COLUMN mobile");
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."users DROP COLUMN usemobileversion");

	// Lastly, remove the settings for GoMobile
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='gomobile'");
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN (
		'gomobile_header_text',
		'gomobile_redirect_enabled',
		'gomobile_redirect_location',
		'gomobile_theme_id',
		'gomobile_homename',
		'gomobile_homelink',
		'gomobile_ua_list'
	)");
	rebuild_settings();
	
	// do we remove the theme too?
}

function gomobile_admin_settings()
{
	global $lang;
	// this allows dynamic translations
	$lang->load("gomobile");
}
