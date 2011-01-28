<?php


// Admin hooks, for adding our control panel page
$plugins->add_hook('admin_config_action_handler','gomobile_adminAction');
$plugins->add_hook('admin_config_menu','gomobile_adminLink');
$plugins->add_hook('admin_load','gomobile_admin');


global $mybb;
define("GOMOBILE_ACP_CONFIG_URL", "index.php?module=config" . ($mybb->version_code >= 1500 ? "-" : "/"));


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

	$tinyint = 'smallint';
	// Install the right database table for our database type
	switch($mybb->config['database']['type'])
	{
		case "pgsql":
			$db->write_query("CREATE TABLE ".TABLE_PREFIX."gomobile (
				gmtid serial,
				regex varchar(120) NOT NULL default '',
				PRIMARY KEY (gmtid)
			);");
			break;
		case "sqlite":
		case "sqlite2":
		case "sqlite3":
			$db->write_query("CREATE TABLE ".TABLE_PREFIX."gomobile (
				gmtid INTEGER PRIMARY KEY,
				regex varchar(120) NOT NULL default '')
			);");
			break;
		default:
			$db->write_query("CREATE TABLE ".TABLE_PREFIX."gomobile (
				gmtid int(10) unsigned NOT NULL auto_increment,
				regex varchar(120) NOT NULL default '',
				PRIMARY KEY(gmtid)
			) TYPE=MyISAM;");
			$tinyint = 'tinyint';
	}

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

	// Get a list of default regexes ready for insertion
	// You can also add more from your ACP
	$data_array = array(
		"/ip[ho](.+?)mobile(.+?)safari/i",
		"/mobile/i",
		"/Android(.+?)/i",
		"/Opera Mini(.+?)/i",
		"/BlackBerry(.+?)/i",
		"/IEMobile(.+?)/i",
		"/Windows Phone(.+?)/i",
		"/HTC(.+?)/i",
		"/Nokia(.+?)/i",
		"/Netfront(.+?)/i",
		"/SmartPhone(.+?)/i",
		"/Symbian(.+?)/i",
		"/SonyEricsson(.+?)/i",
		"/AvantGo(.+?)/i",
		"/DoCoMo(.+?)/i",
		"/Pre\/(.+?)/i",
		"/UP.Browser(.+?)/i"
	);

	// Insert the data listed above
	foreach($data_array as $data)
	{
		$gomobile = array(
			"regex" => $db->escape_string($data)
		);

		$db->insert_query("gomobile", $gomobile);
	}

	// Edit existing templates (shows when posts are from GoMobile)
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";

	find_replace_templatesets("postbit_posturl", '#\\<span.*?\\{\\$lang-\\>postbit_post\\}#', str_replace('$', '\\$', GOMOBILE_TPL_MOD).' $0');

	// Get our settings ready
	$setting_group = array
	(
		"name" => "gomobile",
		"title" => "GoMobile Settings",
		"description" => "Configures options for MyBB GoMobile.",
		"disporder" => "1",
		"isdefault" => "0",
	);

	$gid = $db->insert_query("settinggroups", $setting_group);

	$settings = array(
		"gomobile_mobile_name" => array(
			"title"			=> "Mobile Board Name",
			"description"	=> $lang->gomobile_settings_mobile_name,
			"optionscode"	=> "text",
			"value"			=> $mybb->settings['bbname'],
			"disporder"		=> "1"
		),
		"gomobile_redirect_enabled" => array(
			"title"			=> "Enable Redirect?",
			"description"	=> $lang->gomobile_settings_redirect_enabled,
			"optionscode"	=> "yesno",
			"value"			=> "0",
			"disporder"		=> "2",
		),
		"gomobile_redirect_location" => array(
			"title"			=> "Redirect where?",
			"description"	=> $lang->gomobile_settings_redirect_location,
			"optionscode"	=> "text",
			"value"			=> "index.php",
			"disporder"		=> "3"
		),
		"gomobile_theme_id" => array(
			"title"			=> "Theme ID",
			"description"	=> $lang->gomobile_settings_theme_id,
			"optionscode"	=> "text",
			"value"			=> $theme,
			"disporder"		=> "4"
		),
		"gomobile_homename" => array(
			"title"			=> "Home Name",
			"description"	=> $lang->gomobile_settings_homename,
			"optionscode"	=> "text",
			"value"			=> $mybb->settings['homename'],
			"disporder"		=> "5"
		),
		"gomobile_homelink" => array(
			"title"			=> "Home Link",
			"description"	=> $lang->gomobile_settings_homelink,
			"optionscode"	=> "text",
			"value"			=> $mybb->settings['homeurl'],
			"disporder"		=> "6"
		)
	);

	// Insert the settings listed above
	foreach($settings as $name => $setting)
	{
		$setting['gid'] = $gid;
		$setting['name'] = $name;

		$db->insert_query("settings", $setting);
	}

	rebuild_settings();
}

function gomobile_is_installed()
{
	global $db;

	if($db->table_exists("gomobile"))
	{
		// The gomobile database table exists, so it must be installed.
		return true;
	}
}

function gomobile_uninstall()
{
	global $db;

	// Drop the GoMobile table
	$db->drop_table("gomobile");

	// Clean up the users, posts & threads tables
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."posts DROP COLUMN mobile");
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."threads DROP COLUMN mobile");
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."users DROP COLUMN usemobileversion");

	// Can the template edits we made earlier
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";

	find_replace_templatesets("postbit_posturl", '#'.preg_quote(GOMOBILE_TPL_MOD).'#', '', 0);

	// Lastly, remove the settings for GoMobile
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='gomobile'");
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_header_text'");
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_redirect_enabled'");
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_redirect_location'");
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_theme_id'");
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_homename'");
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_homelink'");
}

function gomobile_adminAction(&$action)
{
	// I'm honestly not sure what this is for...
	$action['gomobile'] = array('active' => 'gomobile');
}

function gomobile_adminLink(&$sub)
{
	global $lang;
	$lang->load('gomobile');

	end($sub);

	$key = key($sub) + 10;

	$sub[$key] = array(
		'id' => 'gomobile',
		'title' => $lang->gomobile_sidemenu,
		'link' => GOMOBILE_ACP_CONFIG_URL.'gomobile'
	);
}

function gomobile_admin()
{
	global $mybb, $page, $db, $lang;
	
	if($page->active_action != 'gomobile')
	{
		return false;
	}

	$lang->load('gomobile');
	$page->add_breadcrumb_item($lang->gomobile, GOMOBILE_ACP_CONFIG_URL.'gomobile');

	if($mybb->input['action'] == 'edit')
	{
		// Adding or creating a regex...
		if(!isset($mybb->input['gmtid']) || intval($mybb->input['gmtid']) == 0)
		{
			flash_message($lang->gomobile_noexist, 'error');
			admin_redirect(GOMOBILE_ACP_CONFIG_URL.'gomobile');
		}
		else
		{
			$gmtid = intval($mybb->input['gmtid']);
		}

		if($mybb->input['save'])
		{
			// User wants to save. Grab the values for later
			$gomobile['regex'] = $mybb->input['regex'];

			// Did they forget to fill in the regex?
			if($gomobile['regex'] == '')
			{
				$error = $lang->gomobile_noregex;
			}
			else
			{
				// No? Let's save it then
				$gomobile['regex'] = $db->escape_string($gomobile['regex']);

				// Did they create a new one?
				if($gmtid == -1)
				{
					// Yes, so we need to add a new database row
					$db->insert_query("gomobile", $gomobile);
				}
				else
				{
					// No, so we just update the existing one.
					// To do: check to make sure the gmtid exists
					$db->update_query("gomobile", $gomobile, "gmtid='{$gmtid}'");
				}

				flash_message($lang->gomobile_saved, 'success');
				admin_redirect(GOMOBILE_ACP_CONFIG_URL.'gomobile');
			}
		}
		else if($mybb->input['delete'])
		{
			// Delete the regex and return to the main menu
			$db->delete_query("gomobile", "gmtid='{$gmtid}'");

			admin_redirect(GOMOBILE_ACP_CONFIG_URL.'gomobile');
		}

		// If there was a problem saving earlier,
		// we've already got this stuff, and the
		// user just needs to fix it
		if(!isset($gomobile))
		{
			// If it doesn't exist yet, let's fill it out
			if($gmtid != -1)
			{
				// The user is editing an existing regex, so load it
				$query = $db->simple_select("gomobile", "regex", "gmtid='{$gmtid}'");
				$gomobile = $db->fetch_array($query);
			}
			else
			{
				// The user is creating a new one, so fill it with some defaults
				$gomobile['regex'] = "";
			}
		}

		// If at this point $gomobile == null,
		// we tried to load a non-existant regex.
		if($gomobile != null)
		{
			// At this point, though, it does exist so
			// do the edity thingy
			$page->add_breadcrumb_item($lang->gomobile_edit);
			$page->output_header($lang->gomobile);

			// Display any errors set earlier
			if(isset($error))
			{
				$page->output_inline_error($error);
			}

			// Create edit box
			$form = new Form(GOMOBILE_ACP_CONFIG_URL.'gomobile&amp;action=edit&amp;gmtid=' . $gmtid, 'post');
			$form_container = new FormContainer($lang->gomobile_edit);

			// Long and ugly.
			// basically ends up as title, description, form thing(name, value, extras)
			$form_container->output_row($lang->gomobile_regex, $lang->gomobile_regex_desc, $form->generate_text_box('regex', htmlspecialchars($gomobile['regex']), array('id' => 'regex')));

			// Done with the box!
			$form_container->end();

			// Buttons! Buttons everywhere!
			$buttons[] = $form->generate_submit_button($lang->gomobile_save, array('name' => 'save', 'id' => 'save'));

			// If the user is creating a new one, there's no sense in
			// showing the delete button.
			if($gmtid != -1)
			{
				$buttons[] = $form->generate_submit_button($lang->gomobile_delete, array('name' => 'delete', 'id' => 'delete'));
			}

			// Show the button(s)
			$form->output_submit_wrapper($buttons);

			// And we're done!
			$form->end();
			$page->output_footer();
		}
		else
		{
			// This happens if the user tried to edit a non-existant regex
			flash_message($lang->gomobile_noexist, 'error');
			admin_redirect(GOMOBILE_ACP_CONFIG_URL.'gomobile');
		}
	}
	else
	{
		// This is the main menu
		$page->output_header($lang->gomobile);

		// Make a box for the menu
		$table = new Table;
		$table->construct_header($lang->gomobile_regex);
		$table->construct_header($lang->controls, array("class" => "align_center", "width" => 155));

		// list existing regexes
		$query = $db->simple_select("gomobile", "gmtid, regex");
		while($list = $db->fetch_array($query))
		{
			// show the regex
			$list['regex'] = htmlspecialchars($list['regex']);
			$table->construct_cell("<strong>{$list['regex']}</strong>");

			// Show the edit and delete menu
			$popup = new PopupMenu("gomobile_{$list['gmtid']}", $lang->options);
			$popup->add_item($lang->gomobile_edit, GOMOBILE_ACP_CONFIG_URL."gomobile&amp;action=edit&amp;gmtid={$list['gmtid']}");
			$popup->add_item($lang->gomobile_delete, GOMOBILE_ACP_CONFIG_URL."gomobile&amp;action=edit&amp;delete=true&amp;gmtid={$list['gmtid']}");
			$table->construct_cell($popup->fetch(), array("class" => "align_center", "width" => 155));

			// Done!
			$table->construct_row();
		}

		// list 'add new regex' link
		$table->construct_cell("<strong><a href=\"".GOMOBILE_ACP_CONFIG_URL."gomobile&amp;action=edit&amp;gmtid=-1\">{$lang->gomobile_addnew}</a></strong>");
		$table->construct_cell('');
		$table->construct_row();

		// Done!
		$table->output($lang->gomobile);
		$page->output_footer();
	}
}
