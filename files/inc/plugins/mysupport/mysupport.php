<?php
/**
 * MySupport 1.8.0

 * Copyright 2010 Matthew Rogowski
 * https://matt.rogow.ski/

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
**/

if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

function mysupport_do_info()
{
	global $lang, $mysupport;
	$mysupport->lang_load();

	return array(
		'name' => 'MySupport',
		'description' => $lang->mysupport_desc,
		'website' => 'http://mattrogowski.co.uk/mybb/plugins/plugin/mysupport',
		'author' => 'MattRogowski',
		'authorsite' => 'http://mattrogowski.co.uk/mybb/',
		'version' => MYSUPPORT_VERSION,
		'versioncode' => MYSUPPORT_VERSION_CODE,
		'compatibility'	=> '18*',
		'codename'		=> 'mysupport',
		//'newpoints'		=> '2.1.1',
		//'myalerts'		=> '2.0.4',
		'pl'			=> array(
			'version'	=> 13,
			'url'		=> 'https://community.mybb.com/mods.php?action=view&pid=573'
		)
	);
}

function mysupport_do_install()
{
	global $db, $cache, $mysupport_uninstall_confirm_override, $mysupport;
	$mysupport->meets_requirements();

	$mysupport->_db_verify_tables();
	$mysupport->_db_verify_columns();
	$mysupport->_db_verify_indexes();

	// this is so we override the confirmation when trying to uninstall, so we can just run the uninstall code
	$mysupport_uninstall_confirm_override = true;

	// insert some default priorities
	$priorities = array();
	$priorities[] = array(
		"type" => "priority",
		"name" => "Low",
		"description" => "Low priority threads.",
		"extra" => "ADCBE7"
	);
	$priorities[] = array(
		"type" => "priority",
		"name" => "Normal",
		"description" => "Normal priority threads.",
		"extra" => "D6ECA6"
	);
	$priorities[] = array(
		"type" => "priority",
		"name" => "High",
		"description" => "High priority threads.",
		"extra" => "FFF6BF"
	);
	$priorities[] = array(
		"type" => "priority",
		"name" => "Urgent",
		"description" => "Urgent priority threads.",
		"extra" => "FFE4E1"
	);
	foreach($priorities as $priority)
	{
		$db->insert_query("mysupport", $priority);
	}

	mysupport_insert_task();

	// set some values for the staff groups
	$update = array(
		"canmarksolved" => 1,
		"canmarktechnical" => 1,
		"canseetechnotice" => 1,
		"canassign" => 1,
		"canbeassigned" => 1,
		"cansetpriorities" => 1,
		"canseepriorities" => 1,
		"canmanagesupportdenial" => 1
	);
	$db->update_query("usergroups", $update, "gid IN ('3','4','6')");

	change_admin_permission("config", "mysupport", 1);

	$cache->update_forums();
	$cache->update_usergroups();
	mysupport_cache();
}

function mysupport_do_is_installed()
{
	global $db, $mysupport;

	foreach($mysupport->_db_tables() as $name => $table)
	{
		$installed = $db->table_exists($name);
		break;
	}

	return $installed;
}

function mysupport_do_uninstall()
{
	global $mybb, $db, $cache, $mysupport_uninstall_confirm_override, $mysupport, $db, $PL;
	$mysupport->meets_requirements();

	// this is a check to make sure we want to uninstall
	// if 'No' was chosen on the confirmation screen, redirect back to the plugins page
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=config-plugins");
	}
	else
	{
		// there's a post request so we submitted the form and selected yes
		// or the confirmation is being overridden by the installation function; this is for when mysupport_uninstall() is called at the start of mysupport_install(), we just want to execute the uninstall code at this point
		if($mybb->request_method == "post" || $mysupport_uninstall_confirm_override === true || $mybb->input['action'] == "delete")
		{
			// Drop DB entries
			foreach($mysupport->_db_tables() as $name => $table)
			{
				$db->drop_table($name);
			}
			foreach($mysupport->_db_columns() as $table => $columns)
			{
				foreach($columns as $name => $definition)
				{
					!$db->field_exists($name, $table) or $db->drop_column($table, $name);
				}
			}

			// Delete the cache.
			$PL->cache_delete('mysupport');

			// Delete stylesheet
			$PL->stylesheet_delete('mysupport');

			// Delete settings
			$PL->settings_delete('mysupport');

			// Delete template/group
			$PL->templates_delete('mysupport');

			$cache->update_forums();
			$cache->update_usergroups();
			$cache->update_moderators();
			$db->delete_query("datacache", "title = 'mysupport'");
		}
		// need to show the confirmation
		else
		{
			global $lang, $page;

			$lang->load("config_mysupport");

			$page->output_confirm_action("index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=mysupport&my_post_key={$mybb->post_code}", $lang->mysupport_uninstall_warning);
		}
	}
}

function mysupport_do_activate()
{
	global $mysupport, $lang, $PL, $cache;
	$mysupport->lang_load();
	$mysupport->meets_requirements();

	// Here we are going to "update" anything that seems incompatible with the new version, aka delete
	if(defined('MYSUPPORT_FORCE_UPDATE'))
	{

		//mysupport_upgrade();

		//require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

		//$db->delete_query("themestylesheets", "name = 'mysupport.css'");

		//$query = $db->simple_select("themes", "tid");
		//while($tid = $db->fetch_field($query, "tid"))
		//{
		//	update_theme_stylesheet_list($tid);
		//}

		exit('Please deactivate the MYSUPPORT_FORCE_UPDATE constant and run the activation again.');
	}

	$PL->stylesheet('ougc_customrep', '.mysupport_status_technical {
	color: blue;
}

.mysupport_status_solved {
	color: green;
}

.mysupport_status_notsolved {
	color: red;
}

.mysupport_bar_technical {
	background: blue;
	height: 10px;
}

.mysupport_bar_solved {
	background: green;
	height: 10px;
}

.mysupport_bar_notsolved {
	background: red;
	height: 10px;
}

.mysupport_bestanswer_highlight {
	background: #D6ECA6 !important;
}

.mysupport_staff_highlight {
	background: #E6E8FA !important;
}

.usercp_nav_support_threads {
	background: url(images/usercp/mysupport_support.png) no-repeat left center;
}

.usercp_nav_assigned_threads {
	background: url(images/usercp/mysupport_assigned.png) no-repeat left center;
}

.modcp_nav_tech_threads {
	background: url(images/modcp/mysupport_technical.png) no-repeat left center;
}

.modcp_nav_deny_support {
	background: url(images/mysupport_no_support.gif) no-repeat left center;
}
', 'showthread.php|forumdisplay.php|usercp.php|usercp2.php|modcp.php');

	// Add our settings
	$PL->settings('mysupport', $lang->mysupport, $lang->mysupport_desc, array(
		'enabled'	=> array(
			'title'			=> $lang->setting_mysupport_enabled,
			'description'	=> $lang->setting_mysupport_enabled_desc,
			'optionscode'	=> 'onoff',
			'value'			=> 1,
		),
		'displaytype'	=> array(
			'title'			=> $lang->setting_mysupport_displaytype,
			'description'	=> $lang->setting_mysupport_displaytype_desc,
			'optionscode'	=> 'radio
image=Image
text=Text',
			'value'			=> 'image',
		),
		'displaytypeuserchange'	=> array(
			'title'			=> $lang->setting_mysupport_displaytypeuserchange,
			'description'	=> $lang->setting_mysupport_displaytypeuserchange_desc,
			'optionscode'	=> 'groupselect',
			'value'			=> -1,
		),
		'displayto'	=> array(
			'title'			=> $lang->setting_mysupport_displayto,
			'description'	=> $lang->setting_mysupport_displayto_desc,
			'optionscode'	=> 'radio
all=Everybody
canmas=Those who can mark as solved
canmasauthor=Those who can mark as solved and the author of the thread',
			'value'			=> 'all',
		),
		'enablesolvedstatus'	=> array(
			'title'			=> $lang->setting_mysupport_enablesolvedstatus,
			'description'	=> $lang->setting_mysupport_enablesolvedstatus_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'closewhensolved'	=> array(
			'title'			=> $lang->setting_mysupport_closewhensolved,
			'description'	=> $lang->setting_mysupport_closewhensolved_desc,
			'optionscode'	=> 'radio
always=Always
option=Optional
never=Never',
			'value'			=> 'never',
		),
		'moveredirect'	=> array(
			'title'			=> $lang->setting_mysupport_moveredirect,
			'description'	=> $lang->setting_mysupport_moveredirect_desc,
			'optionscode'	=> 'select
none=No redirect
1=1 Day
2=2 Days
3=3 Days
5=5 Days
10=10 days
28=28 days
forever=Forever',
			'value'			=> 0,
		),
		'bumpnotice'	=> array(
			'title'			=> $lang->setting_mysupport_bumpnotice,
			'description'	=> $lang->setting_mysupport_bumpnotice_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'enableonhold'	=> array(
			'title'			=> $lang->setting_mysupport_enableonhold,
			'description'	=> $lang->setting_mysupport_enableonhold_desc,
			'optionscode'	=> 'onoff',
			'value'			=> 1,
		),
		'enablebestanswer'	=> array(
			'title'			=> $lang->setting_mysupport_enablebestanswer,
			'description'	=> $lang->setting_mysupport_enablebestanswer_desc,
			'optionscode'	=> 'onoff',
			'value'			=> 1,
		),
		'bestanswerrep'	=> array(
			'title'			=> $lang->setting_mysupport_bestanswerrep,
			'description'	=> $lang->setting_mysupport_bestanswerrep_desc,
			'optionscode'	=> 'numeric',
			'value'			=> 1,
		),
		'enabletechnical'	=> array(
			'title'			=> $lang->setting_mysupport_enabletechnical,
			'description'	=> $lang->setting_mysupport_enabletechnical_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'hidetechnical'	=> array(
			'title'			=> $lang->setting_mysupport_hidetechnical,
			'description'	=> $lang->setting_mysupport_hidetechnical_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'technicalnotice'	=> array(
			'title'			=> $lang->setting_mysupport_technicalnotice,
			'description'	=> $lang->setting_mysupport_technicalnotice_desc,
			'optionscode'	=> 'radio
off=Nowhere (Disabled)
global=Global
specific=Specific',
			'value'			=> 'global',
		),
		'enableassign'	=> array(
			'title'			=> $lang->setting_mysupport_enableassign,
			'description'	=> $lang->setting_mysupport_enableassign_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'assignpm'	=> array(
			'title'			=> $lang->setting_mysupport_assignpm,
			'description'	=> $lang->setting_mysupport_assignpm_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'assignsubscribe'	=> array(
			'title'			=> $lang->setting_mysupport_assignsubscribe,
			'description'	=> $lang->setting_mysupport_assignsubscribe_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'enablepriorities'	=> array(
			'title'			=> $lang->setting_mysupport_enablepriorities,
			'description'	=> $lang->setting_mysupport_enablepriorities_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'enablenotsupportthread'	=> array(
			'title'			=> $lang->setting_mysupport_enablenotsupportthread,
			'description'	=> $lang->setting_mysupport_enablenotsupportthread_desc,
			'optionscode'	=> 'radio
0=Disabled
1=Enabled - By default, new threads are support threads
2=Enabled - By default, new threads are not support threads',
			'value'			=> 0,
		),
		'enablenotsupportthread'	=> array(
			'title'			=> $lang->setting_mysupport_enablenotsupportthread,
			'description'	=> $lang->setting_mysupport_enablenotsupportthread_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'enablesupportdenial'	=> array(
			'title'			=> $lang->setting_mysupport_enablesupportdenial,
			'description'	=> $lang->setting_mysupport_enablesupportdenial_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'closewhendenied'	=> array(
			'title'			=> $lang->setting_mysupport_closewhendenied,
			'description'	=> $lang->setting_mysupport_closewhendenied_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'modlog'	=> array(
			'title'			=> $lang->setting_mysupport_modlog,
			'description'	=> $lang->setting_mysupport_modlog_desc,
			'optionscode'	=> "checkbox
0={$lang->mysupport_mod_log_action_0}
1={$lang->mysupport_mod_log_action_1}
2={$lang->mysupport_mod_log_action_2}
3={$lang->mysupport_mod_log_action_3}
4={$lang->mysupport_mod_log_action_4}
5={$lang->mysupport_mod_log_action_5}
6={$lang->mysupport_mod_log_action_6}
7={$lang->mysupport_mod_log_action_7}
8={$lang->mysupport_mod_log_action_8}
9={$lang->mysupport_mod_log_action_9}
10={$lang->mysupport_mod_log_action_10}
11={$lang->mysupport_mod_log_action_11}
12={$lang->mysupport_mod_log_action_12}
13={$lang->mysupport_mod_log_action_13}",
			'value'			=> '0,1,2,3,4,5,6,7,8,9,10,11,12,13',
		),
		'highlightstaffposts'	=> array(
			'title'			=> $lang->setting_mysupport_highlightstaffposts,
			'description'	=> $lang->setting_mysupport_highlightstaffposts_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'threadlist'	=> array(
			'title'			=> $lang->setting_mysupport_threadlist,
			'description'	=> $lang->setting_mysupport_threadlist_desc,
			'optionscode'	=> 'onoff',
			'value'			=> 1,
		),
		'stats'	=> array(
			'title'			=> $lang->setting_mysupport_stats,
			'description'	=> $lang->setting_mysupport_stats_desc,
			'optionscode'	=> 'onoff',
			'value'			=> 1,
		),
		'relativetime'	=> array(
			'title'			=> $lang->setting_mysupport_relativetime,
			'description'	=> $lang->setting_mysupport_relativetime_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'taskautosolvetime'	=> array(
			'title'			=> $lang->setting_mysupport_taskautosolvetime,
			'description'	=> $lang->setting_mysupport_taskautosolvetime_desc,
			'optionscode'	=> 'select
0=Disabled
604800=1 Week
1209600=2 Weeks
1814400=3 Weeks
2419200=1 Month
4838400=2 Months
7257600=3 Months',
			'value'			=> 2419200,
		),
		'taskbackup'	=> array(
			'title'			=> $lang->setting_mysupport_taskbackup,
			'description'	=> $lang->setting_mysupport_taskbackup_desc,
			'optionscode'	=> 'select
0=Disabled
86400=Every day
259200=Every 3 days
604800=Every week',
			'value'			=> 604800,
		),
		'pointssystem'	=> array(
			'title'			=> $lang->setting_mysupport_pointssystem,
			'description'	=> $lang->setting_mysupport_pointssystem_desc,
			'optionscode'	=> 'select
myps=MyPS
newpoints=NewPoints
other=Other
none=None (Disabled)',
			'value'			=> 'none',
		),
		'pointssystemname'	=> array(
			'title'			=> $lang->setting_mysupport_pointssystemname,
			'description'	=> $lang->setting_mysupport_pointssystemname_desc,
			'optionscode'	=> 'text',
			'value'			=> '',
		),
		'pointssystemcolumn'	=> array(
			'title'			=> $lang->setting_mysupport_pointssystemcolumn,
			'description'	=> $lang->setting_mysupport_pointssystemcolumn_desc,
			'optionscode'	=> 'text',
			'value'			=> '',
		),
		'bestanswerpoints'	=> array(
			'title'			=> $lang->setting_mysupport_bestanswerpoints,
			'description'	=> $lang->setting_mysupport_bestanswerpoints_desc,
			'optionscode'	=> 'text',
			'value'			=> '',
		)
	));

	// Insert template/group
	$PL->templates('mysupport', 'MySupport', array(
		'form'	=> "<form action=\"showthread.php\" method=\"post\" style=\"display: inline;\">
	<input type=\"hidden\" name=\"tid\" value=\"{\$tid}\" />
	<input type=\"hidden\" name=\"action\" value=\"mysupport\" />
	<input type=\"hidden\" name=\"via_form\" value=\"1\" />
	<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
	{\$status_list}
	{\$assigned_list}
	{\$priorities_list}
	{\$categories_list}
	{\$on_hold}
	{\$is_support_thread}
	{\$gobutton}
</form><br />",
		'form_ajax'	=> '<div class="modal">
	<div style="overflow-y: auto; max-height: 400px;">
<form action="{$mybb->settings[\'bburl\']}/showthread.php" method="post">
	<input type="hidden" name="tid" value="{$tid}" />
	<input type="hidden" name="action" value="mysupport" />
	<input type="hidden" name="via_form" value="1" />
	<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr>
			<td class="thead" align="center">
				<strong>{$lang->mysupport_additional_options}</strong>
			</td>
		</tr>
		{$status_list}
		{$assigned_list}
		{$priorities_list}
		{$categories_list}
		{$on_hold}
		{$is_support_thread}
		<tr>
			<td class="tfoot" align="center">
				<input type="submit" class="button" value="{$lang->update}" />
			</td>
		</tr>
	</table>
</form>
	</div>
</div>',
		'tab'	=> "<div class=\"mysupport_tab {\$class}\"><a href=\"{\$url}\"{\$onclick}>{\$text}</a></div>",
		'bestanswer'	=> " <a href=\"{\$mybb->settings['bburl']}/showthread.php?action=bestanswer&amp;pid={\$post['pid']}&amp;my_post_key={\$mybb->post_code}\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/{\$bestanswer_img}.gif\" alt=\"{\$bestanswer_alt}\" title=\"{\$bestanswer_title}\" /> {\$bestanswer_desc}</a>",
		'status_image'	=> "<img src=\"{\$theme['imgdir']}/mysupport_{\$status_img}.png\" alt=\"{\$status_title}\" title=\"{\$status_title}\" /> ",
		'status_text'	=> "<span class=\"mysupport_status_{\$status_class}\" title=\"{\$status_title}\">[{\$status_text}]</span> ",
		'notice'	=> "<table border=\"0\" cellspacing=\"1\" cellpadding=\"4\" class=\"tborder\">
	<tr>
		<td class=\"trow1\" align=\"right\"><a href=\"{\$mybb->settings['bburl']}/{\$notice_url}\"><span class=\"smalltext\">{\$notice_text}</span></a></td>
	</tr>
</table><br />",
		'threadlist_thread'	=> "<tr{\$priority_class}>
	<td class=\"{\$bgcolor}\" width=\"30%\">
		<div>
			<span><a href=\"{\$thread['threadlink']}\">{\$thread['subject']}</a></span>
			<div class=\"author smalltext\">{\$thread['profilelink']}</div>
		</div>
	</td>
	<td class=\"{\$bgcolor}\" width=\"25%\">{\$thread['forumlink']} <a href=\"{\$mybb->settings['bburl']}/{\$view_all_forum_link}\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_arrow_right.gif\" alt=\"{\$view_all_forum_text}\" title=\"{\$view_all_forum_text}\" /></a></td>
	<td class=\"{\$bgcolor}\" width=\"25%\">{\$status_time}</td>
	<td class=\"{\$bgcolor}\" width=\"20%\" style=\"white-space: nowrap; text-align: right;\">
		<span class=\"lastpost smalltext\">{\$lastpostdate} {\$lastposttime}<br />
		<a href=\"{\$thread['lastpostlink']}\">{\$lang->thread_list_lastpost}</a>: {\$lastposterlink}</span>
	</td>
</tr>",
		'threadlist'	=> "<html>
<head>
<title>{\$mybb->settings['bbname']} - {\$thread_list_title}</title>
{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			{\$navigation}
			<td valign=\"top\">
				{\$stats}
				{\$threads_list}
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>",
		'threadlist_list'	=> "{\$mysupport_priority_classes}
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" width=\"100%\" colspan=\"4\"><strong>{\$thread_list_heading}</strong><div class=\"float_right\">{\$threadlist_filter_form}</div></td>
	</tr>
	<tr>
		<td class=\"tcat\" width=\"30%\"><strong>{\$lang->thread_list_threadauthor}</strong></td>
		<td class=\"tcat\" width=\"25%\"><strong>{\$lang->forum}</strong></td>
		<td class=\"tcat\" width=\"25%\"><strong>{\$status_heading}</strong></td>
		<td class=\"tcat\" width=\"20%\" ><strong>{\$lang->thread_list_lastpost}:</strong></td>
	</tr>
	{\$threads}
	{\$view_all}
</table>",
		'threadlist_footer'	=> "<tr>
	<td class=\"tfoot\" colspan=\"4\"><a href=\"{\$mybb->settings['bburl']}/{\$view_all_url}\"><strong>{\$view_all}</strong></a></td>
</tr>",
		'nav_option'	=> "<tr><td class=\"trow1 smalltext\"><a href=\"{\$mybb->settings['bburl']}/{\$nav_link}\" class=\"{\$class1} {\$class2}\">{\$nav_text}</a></td></tr>",
		'threadlist_stats'	=> "<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" width=\"100%\"><strong>{\$title_text}</strong></td>
	</tr>
	<tr>
		<td class=\"trow1\" width=\"100%\">{\$overview_text}</td>
	</tr>
	<tr>
		<td class=\"trow2\" width=\"100%\">
			<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
				<tr>
					{\$solved_row}
					{\$notsolved_row}
					{\$technical_row}
				</tr>
			</table>
		</td>
	</tr>
</table><br />",
		'jumpto_bestanswer'	=> "<a href=\"{\$mybb->settings['bburl']}/{\$jumpto_bestanswer_url}\"><img src=\"{\$theme['imgdir']}/{\$bestanswer_image}\" alt=\"{\$lang->jump_to_bestanswer}\" title=\"{\$lang->jump_to_bestanswer}\" /></a>",
		'assigned'	=> "<img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_assigned.png\" alt=\"{\$lang->assigned}\" title=\"{\$lang->assigned}\" />",
		'assigned_toyou'	=> "<a href=\"{\$mybb->settings['bburl']}/usercp.php?action=assignedthreads\" target=\"_blank\"><img src=\"{\$theme['imgdir']}/mysupport_assigned_toyou.png\" alt=\"{\$lang->assigned_toyou}\" title=\"{\$lang->assigned_toyou}\" /></a>",
		'deny_support_post'	=> "<img src=\"{\$theme['imgdir']}/mysupport_no_support.gif\" alt=\"{\$denied_text_desc}\" title=\"{\$denied_text_desc}\" /> {\$denied_text}",
		'deny_support_post_linked'	=> "<a href=\"{\$mybb->settings['bburl']}/modcp.php?action=supportdenial&amp;do=denysupport&amp;uid={\$post['uid']}&amp;tid={\$post['tid']}\" title=\"{\$denied_text_desc}\"><img src=\"{\$theme['imgdir']}/mysupport_no_support.gif\" alt=\"{\$denied_text_desc}\" title=\"{\$denied_text_desc}\" /> {\$denied_text}</a>",
		'deny_support'	=> "<html>
<head>
<title>{\$lang->support_denial}</title>
{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			{\$modcp_nav}
			<td valign=\"top\">
				{\$deny_support}
				{\$multipage}
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>",
		'deny_support_deny'	=> "<form method=\"post\" action=\"modcp.php\">
	<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
		<tr>
			<td class=\"thead\"><strong>{\$deny_support_to}</strong></td>
		</tr>
		<tr>
			<td class=\"trow1\" align=\"center\">{\$lang->deny_support_desc}</td>
		</tr>
		<tr>
			<td class=\"trow1\" align=\"center\">
				<label for=\"username\">{\$lang->username}</label> <input type=\"text\" name=\"username\" id=\"username\" value=\"{\$username}\" />
			</td>
		</tr>
		<tr>
			<td class=\"trow2\" width=\"80%\" align=\"center\">
				<input type=\"hidden\" name=\"action\" value=\"supportdenial\" />
				<input type=\"hidden\" name=\"do\" value=\"do_denysupport\" />
				<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
				<input type=\"hidden\" name=\"tid\" value=\"{\$tid}\" />
				{\$deniedreasons}
			</td>
		</tr>
		<tr>
			<td class=\"trow2\" width=\"80%\" align=\"center\">
				<input type=\"submit\" value=\"{\$lang->deny_support}\" />
			</td>
		</tr>
	</table>
</form>",
		'deny_support_list'	=> "<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"5\">
			<div class=\"float_right\"><a href=\"modcp.php?action=supportdenial&amp;do=denysupport\">{\$lang->deny_support}</a></div>
			<strong>{\$lang->users_denied_support}</strong>
		</td>
	</tr>
	<tr>
		<td class=\"tcat\" align=\"center\" width=\"20%\"><strong>{\$lang->username}</strong></td>
		<td class=\"tcat\" align=\"center\" width=\"30%\"><strong>{\$lang->support_denial_reason}</strong></td>
		<td class=\"tcat\" align=\"center\" width=\"20%\"><strong>{\$lang->support_denial_user}</strong></td>
		<td class=\"tcat\" colspan=\"2\" align=\"center\" width=\"30%\"><strong>{\$lang->controls}</strong></td>
	</tr>
	{\$denied_users}
</table>",
		'deny_support_list_user'	=> "<tr>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"20%\">{\$support_denied_user}</td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"30%\">{\$support_denial_reason}</td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"20%\">{\$support_denier_user}</td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"15%\"><a href=\"{\$mybb->settings['bburl']}/modcp.php?action=supportdenial&amp;do=denysupport&amp;uid={\$denieduser['support_denied_uid']}\">{\$lang->edit}</a></td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"15%\"><a href=\"{\$mybb->settings['bburl']}/modcp.php?action=supportdenial&amp;do=do_denysupport&amp;uid={\$denieduser['support_denied_uid']}&amp;deniedsupportreason=-1&amp;my_post_key={\$mybb->post_code}\">{\$lang->revoke}</a></td>
</tr>",
		'usercp_options'	=> "<fieldset class=\"trow2\">
	<legend><strong>{\$lang->mysupport_options}</strong></legend>
	<table cellspacing=\"0\" cellpadding=\"2\">
		<tr>
			<td valign=\"top\" width=\"1\">
				<input type=\"checkbox\" class=\"checkbox\" name=\"mysupportdisplayastext\" id=\"mysupportdisplayastext\" value=\"1\" {\$mysupportdisplayastextcheck} />
			</td>
			<td>
				<span class=\"smalltext\"><label for=\"mysupportdisplayastext\">{\$lang->mysupport_show_as_text}</label></span>
			</td>
		</tr>
	</table>
</fieldset>
<br />",
		'inline_thread_moderation'	=> "<optgroup label=\"{\$lang->mysupport}\">
	<option disabled=\"disabled\">{\$lang->markas}</option>
	{\$mysupport_solved}
	{\$mysupport_solved_and_close}
	{\$mysupport_technical}
	{\$mysupport_not_technical}
	{\$mysupport_not_solved}
	<option disabled=\"disabled\">{\$lang->hold_status}</option>
	{\$mysupport_onhold}
	{\$mysupport_offhold}
	<option disabled=\"disabled\">{\$lang->assign_to}</option>
	{\$mysupport_assign}
	<option value=\"mysupport_assign_0\">-- {\$lang->assign_to_nobody}</option>
	<option disabled=\"disabled\">{\$lang->priority}</option>
	{\$mysupport_priorities}
	<option value=\"mysupport_priority_0\">-- {\$lang->priority_none}</option>
	<option disabled=\"disabled\">{\$lang->category}</option>
	{\$mysupport_categories}
	<option value=\"mysupport_category_0\">-- {\$lang->category_none}</option>
</optgroup>",
		'member_profile'	=> "<br />
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td colspan=\"2\" class=\"thead\"><strong>{\$lang->mysupport}</strong></td>
	</tr>
	{\$bestanswers}
	{\$denied_text}
</table>",
		'form_select_option'	=> "<option value=\"{\$value}\"{\$selected}>{\$label}</option>",
		''	=> "",
	));

	$mysupport->_db_verify_tables();
	$mysupport->_db_verify_columns();
	$mysupport->_db_verify_indexes();

	mysupport_template_edits(1);

	mysupport_cache("version");
	mysupport_cache("priorities");
	mysupport_cache("deniedreasons");

	$cache->update_forums();
	$cache->update_usergroups();
	$cache->update_moderators();
}

function mysupport_do_deactivate()
{
	global $cache;

	mysupport_template_edits(0);

	mysupport_cache("version");
}

// function called upon activation to check if anything needs to be upgraded
// upgrade process is deactivate, upload new files, activate - this function checks for the old version upon re-activation and performs any necessary upgrades
// if settings/templates need to be added/edited/deleted, it'd be taken care of here
// would also deal with any database changes etc
function mysupport_upgrade()
{
	global $mybb, $db, $cache;

	$mysupport_cache = $cache->read("mysupport");
	$old_version = $mysupport_cache['version'];
	// legacy
	if(!$old_version)
	{
		$old_version = $cache->read("mysupport_version");
	}

	// only need to run through this if the version has actually changed
	if(!empty($old_version) && $old_version < MYSUPPORT_VERSION || defined('MYSUPPORT_FORCE_UPDATE'))
	{
		$deleted_settings = array();
		$deleted_templates = array();

		// go through each upgrade process; versions are only listed here if there were changes FROM that version to the next
		// it will go through the ones it needs to and make the changes it needs
		if($old_version <= 0.3)
		{
		}
		if($old_version <= 0.4)
		{
			mysupport_insert_task();
			mysupport_recount_technical_threads();
			$query = $db->simple_select("threads", "DISTINCT assign", "assign != '0'");
			while($user = $db->fetch_field($query, "assign"))
			{
				mysupport_recount_assigned_threads($user);
			}
			// there's just a 'mysupport' cache now with other things in it
			$db->delete_query("datacache", "title = 'mysupport_version'");
			// cache priorities and support denial reasons
			mysupport_cache("priorities");
			mysupport_cache("deniedreasons");
			// we need to update the setting of what to log, to include putting threads on hold, but don't change which actions may have logging disabled
			if($mybb->settings['mysupportmodlog'])
			{
				$mybb->settings['mysupportmodlog'] .= ",";
			}
			$mybb->settings['mysupportmodlog'] .= "12";
			$update = array(
				"value" => $db->escape_string($mybb->settings['mysupportmodlog'])
			);
			$db->update_query("settings", $update, "name = 'mysupportmodlog'");
			rebuild_settings();
		}

		// now we can update the cache with the new version
		mysupport_cache("version");
		// rebuild the forums and usergroups caches in case anything's changed
		$cache->update_forums();
		$cache->update_usergroups();
	}
}

function mysupport_insert_task()
{
	global $db, $lang;
	
	$lang->load("config_mysupport");

	include_once MYBB_ROOT . "inc/functions_task.php";
	$new_task = array(
		"title" => $lang->mysupport,
		"description" => $lang->mysupport_task_description,
		"file" => "mysupport",
		"minute" => 0,
		"hour" => 0,
		"day" => "*",
		"month" => "*",
		"weekday" => "*",
		"enabled" => 1,
		"logging" => 1
	);
	$new_task['nextrun'] = fetch_next_run($new_task);
	$db->insert_query("tasks", $new_task);
}

function mysupport_setting_names()
{
	$settings = mysupport_settings_info();
	$setting_names = array();

	foreach($settings as $setting)
	{
		$setting_names[] = $setting['name'];
	}

	return $setting_names;
}

function mysupport_settings_info()
{
}

/**
 * Update the display order of settings if settings
**/
function mysupport_update_setting_orders()
{
	global $db;

	$settings = mysupport_setting_names();

	$i = 1;
	foreach($settings as $setting)
	{
		$update = array(
			"disporder" => $i
		);
		$db->update_query("settings", $update, "name = '" . $db->escape_string($setting) . "'");
		$i++;
	}

	rebuild_settings();
}

/**
 * Make the template edits necessary for MySupport to work.
 *
 * @param int Activating/deactivating - 1/0
**/
function mysupport_template_edits($type)
{
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

	if($type == 1)
	{
		find_replace_templatesets("showthread", "#".preg_quote('{$multipage}')."#i", '{$multipage}{$mysupport_options}');
		find_replace_templatesets("showthread", "#".preg_quote('{$footer}')."#i", '{$mysupport_js}{$footer}');
		find_replace_templatesets("postbit", "#".preg_quote('trow1')."#i", 'trow1{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');
		find_replace_templatesets("postbit", "#".preg_quote('trow2')."#i", 'trow2{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$altbg}')."#i", '{$altbg}{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'subject_extra\']}')."#i", '{$post[\'subject_extra\']}<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>');
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'subject_extra\']}')."#i", '{$post[\'subject_extra\']}<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>');
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'icon\']}')."#i", '{$post[\'mysupport_status\']}{$post[\'icon\']}');
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'icon\']}')."#i", '{$post[\'mysupport_status\']}{$post[\'icon\']}');
		find_replace_templatesets("showthread", "#".preg_quote('{$thread[\'threadprefix\']}')."#i", '{$mysupport_status}{$thread[\'threadprefix\']}');
		find_replace_templatesets("header", "#".preg_quote('{$unreadreports}')."#i", '{$unreadreports}{$mysupport_tech_notice}{$mysupport_assign_notice}');
		find_replace_templatesets("forumdisplay", "#".preg_quote('{$header}')."#i", '{$header}{$mysupport_priority_classes}');
		find_replace_templatesets("search_results_threads ", "#".preg_quote('{$header}')."#i", '{$header}{$mysupport_priority_classes}');
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$prefix}')."#i", '{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}{$prefix}');
		find_replace_templatesets("search_results_threads_thread ", "#".preg_quote('{$prefix}')."#i", '{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}{$prefix}');
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$bgcolor}')."#i", '{$bgcolor}{$priority_class}');
		find_replace_templatesets("forumdisplay_thread_rating", "#".preg_quote('{$bgcolor}')."#i", '{$bgcolor}{$priority_class}');
		find_replace_templatesets("forumdisplay_thread_modbit", "#".preg_quote('{$bgcolor}')."#i", '{$bgcolor}{$priority_class}');
		find_replace_templatesets("search_results_threads_thread", "#".preg_quote('{$bgcolor}')."#i", '{$bgcolor}{$priority_class}');
		find_replace_templatesets("search_results_threads_inlinecheck", "#".preg_quote('{$bgcolor}')."#i", '{$bgcolor}{priority_class}');
		find_replace_templatesets("forumdisplay_inlinemoderation", "#".preg_quote('{$customthreadtools}')."#i", '{$customthreadtools}{$mysupport_inline_thread_moderation}');
		find_replace_templatesets("search_results_threads_inlinemoderation", "#".preg_quote('{$customthreadtools}')."#i", '{$customthreadtools}{$mysupport_inline_thread_moderation}');
		find_replace_templatesets("modcp_nav", "#".preg_quote('{$lang->mcp_nav_modlogs}</a></td></tr>')."#i", '{$lang->mcp_nav_modlogs}</a></td></tr>{mysupport_nav_option}');
		find_replace_templatesets("usercp_nav_misc", "#".preg_quote('{$lang->ucp_nav_forum_subscriptions}</a></td></tr>')."#i", '{$lang->ucp_nav_forum_subscriptions}</a></td></tr>{mysupport_nav_option}');
		find_replace_templatesets("usercp", "#".preg_quote('{$latest_warnings}')."#i", '{$latest_warnings}<br />{$threads_list}');
		find_replace_templatesets("member_profile", "#".preg_quote('{$profilefields}')."#i", '{$profilefields}{$mysupport_info}');
		find_replace_templatesets("newreply", "#".preg_quote('{$message}</textarea>')."#i", '{$mysupport_solved_bump_message}{$message}</textarea>');
		find_replace_templatesets("showthread_quickreply", "#".preg_quote('</textarea>')."#i", '{$mysupport_solved_bump_message}</textarea>');
		find_replace_templatesets("newthread", "#".preg_quote('{$multiquote_external}')."#i", '{$multiquote_external}{$mysupport_thread_options}');
	}
	else
	{
		find_replace_templatesets("showthread", "#".preg_quote('{$mysupport_options}')."#i", '', 0);
		find_replace_templatesets("showthread", "#".preg_quote('{$mysupport_js}')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}')."#i", '', 0);
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>')."#i", '', 0);
		find_replace_templatesets("postbit_classic", "#".preg_quote('<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'mysupport_status\']}')."#i", '', 0);
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'mysupport_status\']}')."#i", '', 0);
		find_replace_templatesets("showthread", "#".preg_quote('{$mysupport_status}')."#i", '', 0);
		find_replace_templatesets("header", "#".preg_quote('{$mysupport_tech_notice}{$mysupport_assign_notice}')."#i", '', 0);
		find_replace_templatesets("forumdisplay", "#".preg_quote('{$mysupport_priority_classes}')."#i", '', 0);
		find_replace_templatesets("search_results_threads ", "#".preg_quote('{$mysupport_priority_classes}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_thread ", "#".preg_quote('{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$priority_class}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_thread_rating", "#".preg_quote('{$priority_class}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_thread_modbit", "#".preg_quote('{$priority_class}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_thread", "#".preg_quote('{$priority_class}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_inlinecheck", "#".preg_quote('{priority_class}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_inlinemoderation", "#".preg_quote('{$mysupport_inline_thread_moderation}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_inlinemoderation", "#".preg_quote('{$mysupport_inline_thread_moderation}')."#i", '', 0);
		find_replace_templatesets("modcp_nav", "#".preg_quote('{mysupport_nav_option}')."#i", '', 0);
		find_replace_templatesets("usercp_nav_misc", "#".preg_quote('{mysupport_nav_option}')."#i", '', 0);
		find_replace_templatesets("usercp", "#".preg_quote('<br />{$threads_list}')."#i", '', 0);
		find_replace_templatesets("member_profile", "#".preg_quote('{$mysupport_info}')."#i", '', 0);
		find_replace_templatesets("newreply", "#".preg_quote('{$mysupport_solved_bump_message}')."#i", '', 0);
		find_replace_templatesets("showthread_quickreply", "#".preg_quote('{$mysupport_solved_bump_message}')."#i", '', 0);
		find_replace_templatesets("newthread", "#".preg_quote('{$mysupport_thread_options}')."#i", '', 0);
	}
}
