<?php
/*
	MyBB GoMobile - Version: 1.0 Beta 3
	Based on UA Theme. Notices below.
	
	Copyright (c) 2010, Fawkes Software
	All rights reserved.

	Redistribution and use in source and binary forms, with or without modification,
	are permitted provided that the following conditions are met:

	* Redistributions of source code must retain the above copyright notice, this
	list of conditions and the following disclaimer.
	* Redistributions in binary form must reproduce the above copyright notice,
	this list of conditions and the following disclaimer in the documentation
	and/or other materials provided with the distribution.
	* Neither the name of Fawkes Software nor the names of its contributors may be
	used to endorse or promote products derived from this software without specific
	prior written permission.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
	EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
	OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
	SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
	INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
	TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
	BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
	ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Run only if the user isn't updating or installing
// Page hook, for overriding the theme as best as we can
$plugins->add_hook("global_start", "gomobile_forcetheme");

// New Reply & New Thread hooks, for determining whether or not the post is from a mobile
$plugins->add_hook("datahandler_post_insert_post", "gomobile_posts");
$plugins->add_hook("datahandler_post_insert_thread_post", "gomobile_posts");

// Portal hooks
$plugins->add_hook("portal_start", "gomobile_portal_default");
$plugins->add_hook("pro_portal_start", "gomobile_portal_default");

// Forumdisplay hooks
$plugins->add_hook("forumdisplay_thread", "gomobile_forumdisplay");

// Showthread hooks
$plugins->add_hook("showthread_end", "gomobile_showthread");

// User CP Options
$plugins->add_hook("usercp_options_end", "gomobile_usercp_options");
$plugins->add_hook("usercp_do_options_end", "gomobile_usercp_options");

// Misc hooks
$plugins->add_hook("misc_start", "gomobile_switch_version");


if(defined("IN_ADMINCP"))
{
	require MYBB_ROOT."inc/plugins/gomobile/gm_admin.php";
}

function gomobile_forcetheme()
{
	global $db, $mybb, $plugins, $lang;
	$lang->load("gomobile");

	if($mybb->session->is_spider == false)
	{
		// Force some changes to our footer but only if we're not a bot
		$GLOBALS['gmb_orig_style'] = intval($mybb->user['style']);

		$plugins->add_hook("global_end", "gomobile_forcefooter");
	}

	// Has the user chosen to disable GoMobile completely?
	if($mybb->cookies['use_dmv'] == "2" || (isset($mybb->user['usemobileversion']) && $mybb->user['usemobileversion'] == 0 && $mybb->user['uid']))
	{
		return;
	}
	
	$use_mobile = false;
	if($mybb->cookies['use_dmv'] == "1")
	{
		$use_mobile = true;
	}
	else
	{
		// Fetch the list of User Agent strings
		foreach(explode("\n", str_replace("\r", "", $mybb->settings['gomobile_ua_list'])) as $test_regex)
		{
			$test_regex = trim($test_regex);
			// Switch to GoMobile if the UA matches our list
			if(preg_match($test_regex, $_SERVER['HTTP_USER_AGENT']) != 0)
			{
				$use_mobile = true;
			}
		}
		
		// Terminate early if not using theme
		if(!$use_mobile)
		{
			return;
		}
	}
	
	
	// Fetch the theme permissions from the database
	$tquery = $db->simple_select("themes", "allowedgroups", "tid = ".intval($mybb->settings['gomobile_theme_id']));
	$tperms = $db->fetch_field($tquery, "allowedgroups");
	if($tperms != "all") {
		if(!$tperms) {
			return;
		}
		if(strpos(','.$tperms.',', ','.$mybb->user['usergroup'].',') === false)
		{
			// current usergroup not listed - we need to check additional groups
			if(!$mybb->user['additionalgroups']) {
				return;
			}
			$canuse = array_flip(explode(",", $tperms));
			$allowed = false;
			foreach(explode(",", $mybb->user['additionalgroups']) as $gid)
			{
				if(isset($canuse[$gid]))
				{
					$allowed = true;
					break;
				}
			}
			if(!$allowed) {
				return;
			}
		}
	}
	
	$mybb->user['style'] = intval($mybb->settings['gomobile_theme_id']);
}

function gomobile_forcefooter()
{
	global $lang, $footer, $mybb, $navbits;

	// Replace the footer, but only if the visitor isn't a bot
	$footer = str_replace("<a href=\"<archive_url>\">".$lang->bottomlinks_litemode."</a>", "<a href=\"misc.php?action=switch_version&amp;my_post_key=".$mybb->post_code."\">".$lang->gomobile_mobile_version."</a>", $footer);

	if($mybb->user['style'] == $mybb->settings['gomobile_theme_id'])
	{
		// Override default breadcrumb bbname (for mobile theme only)
		$navbits = array();
		$navbits[0]['name'] = $mybb->settings['gomobile_mobile_name'];
		$navbits[0]['url'] = $mybb->settings['bburl']."/index.php";	
	}
} 

// TODO: well, this could be deleted as we can use {$thread['closed']} directly in
//       templates; however if this change be made, it would require some upgrade to
//       existing themes, so I'll refrain from doing this
function gomobile_forumdisplay()
{
	global $mybb, $thread, $tstatus;
	
	// All we're doing here is showing the thread title in a red font if it's closed
	if($thread['closed'] == 1) {
		$tstatus = "threadlist_closed";
	}
	else {
		$tstatus = "";
	}
}

function gomobile_showthread()
{
	global $mybb, $lang, $postcount, $perpage, $thread, $pagejump, $pages, $page_location;
	
	// Display the total number of pages
	if($pages > 0) {
		$page_location = " {$lang->gomobile_of} {$pages}";
	}
	
	// If there's more than one page, display links to the first & last posts
	if($postcount > $perpage){
		$pj_template = "<div class=\"float_left\" style=\"padding-top: 12px;\">
			<a href=\"".get_thread_link($thread['tid'])."\" class=\"pagination_a\">{$lang->gomobile_jump_fpost}</a>
			<a href=\"".get_thread_link($thread['tid'], 0, 'lastpost')."\" class=\"pagination_a\">{$lang->gomobile_jump_lpost}</a>
			</div>";
		$pagejump = $pj_template;
	}
}

function gomobile_portal_default()
{
	global $mybb, $lang;
	
	// Has the admin disabled viewing of the portal from GoMobile?
	if($mybb->user['style'] == $mybb->settings['gomobile_theme_id'] && $mybb->settings['gomobile_redirect_enabled'] == 1)
	{
		redirect($mybb->settings['gomobile_redirect_location'], $lang->gomobile_redirect_portal);
	}
}

function gomobile_posts($p)
{
	global $mybb;

	// TODO: we shouldn't trust the input here so much
	//       perhaps just see if the theme being used is GoMobile
	$is_mobile = intval($mybb->input['mobile']);

	// Was the post sent from GoMobile?
	if($is_mobile != 1)
	{
		$is_mobile = 0;
	}

	// If so, we're going to store it for future use
	$p->post_insert_data['mobile'] = $is_mobile;
} 

function gomobile_usercp_options()
{
	global $db, $mybb, $templates, $user;

	if(isset($GLOBALS['gmb_orig_style']))
	{
		// Because we override this above, reset it to the original
		$mybb->user['style'] = $GLOBALS['gmb_orig_style'];
	}

	if($mybb->request_method == "post")
	{
		// We're saving our options here
		$update_array = array(
			"usemobileversion" => intval($mybb->input['usemobileversion'])
		);

		$db->update_query("users", $update_array, "uid = '".$user['uid']."'");
	}

	$usercp_option = '</tr><tr>
<td valign="top" width="1"><input type="checkbox" class="checkbox" name="usemobileversion" id="usemobileversion" value="1" {$GLOBALS[\'$usemobileversioncheck\']} /></td>
<td><span class="smalltext"><label for="usemobileversion">{$lang->gomobile_use_mobile_version}</label></span></td>';

	$find = '{$lang->show_codebuttons}</label></span></td>';
	$templates->cache['usercp_options'] = str_replace($find, $find.$usercp_option, $templates->cache['usercp_options']);

	// We're just viewing the page
	$GLOBALS['$usemobileversioncheck'] = '';
	if($user['usemobileversion'])
	{
		$GLOBALS['$usemobileversioncheck'] = "checked=\"checked\"";
	}
}

function gomobile_switch_version()
{
	global $db, $lang, $mybb;

	if($mybb->input['action'] != "switch_version")
	{
		return;
	}

	$url = "index.php";
	if(isset($_SERVER['HTTP_REFERER']))
	{
		$url = htmlentities($_SERVER['HTTP_REFERER']);
	}

	// no verify_post_check($mybb->input['my_post_key']); ?
	if($mybb->post_code != $mybb->input['my_post_key'])
	{
		redirect($url, $lang->invalid_post_code);
	}

	// state of use_dmv cookie: 1=force on, 2=force off, else=default
	// Assume we're wanting to switch to the mobile version if 'full' isn't set
	my_setcookie("use_dmv", ($mybb->input['do'] == "full" ? "2" : "1"), -1);

	$lang->load("gomobile");
	redirect($url, $lang->gomobile_switched_version);
}
?>