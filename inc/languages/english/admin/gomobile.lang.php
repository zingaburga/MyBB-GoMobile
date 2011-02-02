<?php
/*
 MyBB GoMobile Language Strings - Version: 1.0 Beta 3
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
 
 inc/languages/xxx/admin/gomobile.lang.php: The various strings shown to the user
*/

$l['gomobile'] = "MyBB GoMobile";
$l['gomobile_desc'] = "MyBB GoMobile's accompanying plugin, forces user agents matching a regex to use a certain theme.";

// Setting Strings
$l['setting_group_gomobile'] = "GoMobile Settings";
$l['setting_group_gomobile_desc'] = "Configures options for MyBB GoMobile.";

$l['setting_gomobile_mobile_name'] = "Mobile Board Name";
$l['setting_gomobile_mobile_name_desc'] = "Use this setting to shorten both your header and breadcrumb (navigation) text. You may use full HTML formatting.";
$l['setting_gomobile_redirect_enabled'] = "Enable Portal Redirect?";
$l['setting_gomobile_redirect_enabled_desc'] = "Enable or disable the GoMobile portal redirect. Enabling this will automatically redirect users who visit to portal on their mobile, to a page of your choice instead.";
$l['setting_gomobile_redirect_location'] = "Redirect Location";
$l['setting_gomobile_redirect_location_desc'] = "Enter the location (relative) that you would like to redirect users to. Ignore this if the redirect option above is not enabled.";
$l['setting_gomobile_theme_id'] = "Theme ID";
$l['setting_gomobile_theme_id_desc'] = "Enter the tid (Theme ID) of GoMobile below. This is used to switch the user to the mobile version.";
$l['setting_gomobile_homename'] = "Home Name";
$l['setting_gomobile_homename_desc'] = "This is the text that will appear in the footer of GoMobile in place of the home link. It is recommended that you keep this to as few charaters as possible.";
$l['setting_gomobile_homelink'] = "Home Link";
$l['setting_gomobile_homelink_desc'] = "Below is the link that appears in the footer of GoMobile.";
$l['setting_gomobile_ua_list'] = "User-Agent List";
$l['setting_gomobile_ua_list_desc'] = "Specify a list of regular expressions (matched by <a href=\"http://php.net/manual/en/function.preg-match.php\">preg_match</a>) which will be matched against the User-Agent sent by the browser to determine if the user is using a mobile browser.";
