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

namespace MySupport\Admin;

function _info()
{
	global $lang;

	\MySupport\Core\load_language();

	$myalerts_desc = '';

	/*if(_is_installed() && \MySupport\MyAlerts\MyAlertsIsIntegrable())
	{
		$myalerts_desc .= $lang->mysupport_myalerts_desc;
	}*/

	return [
		'name'			=> 'MySupport',
		'description'	=> $lang->mysupport_desc.$myalerts_desc,
		'website'		=> 'http://mattrogowski.co.uk/mybb/plugins/plugin/mysupport',
		'author'		=> 'MattRogowski',
		'authorsite'	=> 'http://mattrogowski.co.uk/mybb/',
		'version'		=> MYSUPPORT_VERSION,
		'versioncode'	=> MYSUPPORT_VERSION_CODE,
		'compatibility'	=> '18*',
		'codename'		=> 'mysupport',
		'pl'			=> [
			'version'	=> 13,
			'url'		=> 'https://community.mybb.com/mods.php?action=view&pid=573'
		],
		'myalerts'			=> [
			'version'	=> '2.0.4',
			'url'		=> 'https://community.mybb.com/thread-171301.html'
		]
	];
}

function _activate()
{
	global $PL, $lang, $cache, $db;

	\MySupport\Core\load_pluginlibrary();

	// Add our settings
	$PL->settings('mysupport', $lang->mysupport, $lang->mysupport_desc, array(
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
	
	// Add styleheets
    $stylesheetsDirIterator = new \DirectoryIterator(MYSUPPORT_ROOT.'/stylesheets');

	$stylesheets = [];

    foreach($stylesheetsDirIterator as $stylesheet)
    {
		if(!$stylesheet->isFile())
		{
			continue;
		}

		$pathName = $stylesheet->getPathname();

        $pathInfo = pathinfo($pathName);

		if($pathInfo['extension'] === 'css')
		{
            $stylesheets[$pathInfo['filename']] = file_get_contents($pathName);
		}
    }

	foreach($stylesheets as  $stylesheet)
	{
		$PL->stylesheet('mysupport', $stylesheet, 'showthread.php|forumdisplay.php|usercp.php|usercp2.php|modcp.php');
	}

	// Add template group
    $templatesDirIterator = new \DirectoryIterator(MYSUPPORT_ROOT.'/templates');

	$templates = [];

    foreach($templatesDirIterator as $template)
    {
		if(!$template->isFile())
		{
			continue;
		}

		$pathName = $template->getPathname();

        $pathInfo = pathinfo($pathName);

		if($pathInfo['extension'] === 'html')
		{
            $templates[$pathInfo['filename']] = file_get_contents($pathName);
		}
    }

	if($templates)
	{
		$PL->templates('mysupport', 'MySupport', $templates);
	}

	_db_verify_tables();

	_db_verify_columns();

	_install_task();

	change_admin_permission('config', 'mysupport');

	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

	find_replace_templatesets("showthread", "#".preg_quote('{$multipage}')."#i", '{$multipage}{$mysupport_options}');

	find_replace_templatesets("showthread", "#".preg_quote('{$footer}')."#i", '{$mysupport_js}{$footer}');

	find_replace_templatesets("postbit", "#".preg_quote('post_content')."#i", 'post_content{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');

	find_replace_templatesets("postbit_classic", "#".preg_quote('post_content')."#i", 'post_content{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');

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

	find_replace_templatesets("modcp_nav", "#".preg_quote('{$modcp_nav_users}')."#i", '{$modcp_nav_users}<!--mysupport_nav_option-->');

	find_replace_templatesets("usercp_nav_misc", "#".preg_quote('{$lang->ucp_nav_forum_subscriptions}</a></td></tr>')."#i", '{$lang->ucp_nav_forum_subscriptions}</a></td></tr><!--mysupport_nav_option-->');

	find_replace_templatesets("usercp", "#".preg_quote('{$latest_warnings}')."#i", '{$latest_warnings}<br />{$threads_list}');

	find_replace_templatesets("member_profile", "#".preg_quote('{$profilefields}')."#i", '{$profilefields}{$mysupport_info}');

	find_replace_templatesets("newreply", "#".preg_quote('{$message}</textarea>')."#i", '{$mysupport_solved_bump_message}{$message}</textarea>');

	find_replace_templatesets("showthread_quickreply", "#".preg_quote('</textarea>')."#i", '{$mysupport_solved_bump_message}</textarea>');

	find_replace_templatesets("newthread", "#".preg_quote('{$multiquote_external}')."#i", '{$multiquote_external}{$mysupport_thread_options}');

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$cache->update_forums();

	$cache->update_usergroups();

	\MySupport\Core\_cache();
}

function _deactivate()
{
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

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

	find_replace_templatesets("modcp_nav", "#".preg_quote('<!--mysupport_nav_option-->')."#i", '', 0);

	find_replace_templatesets("usercp_nav_misc", "#".preg_quote('<!--mysupport_nav_option-->')."#i", '', 0);

	find_replace_templatesets("usercp", "#".preg_quote('<br />{$threads_list}')."#i", '', 0);

	find_replace_templatesets("member_profile", "#".preg_quote('{$mysupport_info}')."#i", '', 0);

	find_replace_templatesets("newreply", "#".preg_quote('{$mysupport_solved_bump_message}')."#i", '', 0);

	find_replace_templatesets("showthread_quickreply", "#".preg_quote('{$mysupport_solved_bump_message}')."#i", '', 0);

	find_replace_templatesets("newthread", "#".preg_quote('{$mysupport_thread_options}')."#i", '', 0);

	_deactivate_task();

	// Update administrator permissions
	change_admin_permission('config', 'mysupport', 0);
}

function _install()
{
	global $cache, $db;

	_db_verify_tables();

	_db_verify_columns();

	// insert some default priorities
	$priorities = [
		[
			'type' => 'priority',
			'name' => 'Low',
			'description' => 'Low priority threads.',
			'extra' => 'ADCBE7'
		],
		[
			'type' => 'priority',
			'name' => 'Normal',
			'description' => 'Normal priority threads.',
			'extra' => 'D6ECA6'
		],
		[
			'type' => 'High',
			'name' => 'Low',
			'description' => 'High priority  threads.',
			'extra' => 'FFF6BF'
		],
		[
			'type' => 'priority',
			'name' => 'Urgent',
			'description' => 'Urgent priority threads.',
			'extra' => 'FFE4E1'
		],
	];

	foreach($priorities as $priority)
	{
		$db->insert_query('mysupport', $priority);
	}

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

	$update_data = [];

	foreach(_db_columns()['usergroups'] as $name => $definition)
	{
		$update_data[$name] = 1;
	}

	$db->update_query('usergroups', $update_data, 'gid IN (3,4,6)');
/*
    // MyAlerts
    $MyAlertLocationsInstalled = array_filter(
        \MySupport\MyAlerts\getAvailableLocations(),
        '\\MySupport\MyAlerts\\isLocationAlertTypePresent'
    );

    $cache->update('mysupport', [
        'MyAlertLocationsInstalled' => $MyAlertLocationsInstalled,
    ]);*/
}

function _is_installed()
{
	global $db;

	static $installed = null;

	if($installed === null)
	{
		foreach(_db_tables() as $name => $table)
		{
			$installed = $db->table_exists($name);

			break;
		}
	}

	return $installed;
}

function _uninstall()
{
	global $db, $PL, $cache;

	\MySupport\Core\load_pluginlibrary();

	// Drop DB entries
	foreach(_db_tables() as $name => $table)
	{
		$db->drop_table($name);
	}

	foreach(_db_columns() as $table => $columns)
	{
		foreach($columns as $name => $definition)
		{
			!$db->field_exists($name, $table) || $db->drop_column($table, $name);
		}
	}

	$PL->stylesheet_delete('mysupport');

	$PL->settings_delete('mysupport');

	$PL->templates_delete('mysupport');

	_uninstall_task();

	// Remove administrator permissions
	change_admin_permission('config', 'mysupport', -1);

	$cache->update_forums();

	$cache->update_usergroups();

	$cache->update_moderators();

	// Delete version from cache
	$cache->delete('mysupport');
}

// List of tables
function _db_tables()
{
	global $db;

	$collation = $db->build_create_table_collation();

	return [
		'mysupport'	=> [
			'mid'				=> "SMALLINT(5) NOT NULL AUTO_INCREMENT",
			'type'				=> "VARCHAR(20) NOT NULL DEFAULT ''",
			'name'				=> "VARCHAR(255) NOT NULL DEFAULT ''",
			'description'		=> "VARCHAR(500) NOT NULL DEFAULT ''",
			'extra'				=> "VARCHAR(255) NOT NULL default ''",
			'groups'			=> "VARCHAR(255) NOT NULL default ''",
			'forums'			=> "VARCHAR(255) NOT NULL default ''",
			'primary_key'		=> "mid"
		],
		//'unique_key' => ['uid' => 'uid']
	];
}

// List of columns
function _db_columns()
{
	return [
		'forums'	=> [
			'mysupport' => "INT(1) NOT NULL DEFAULT '0'",
			'mysupportmove' => "INT(1) NOT NULL DEFAULT '1'",
			'mysupportdenial' => "INT(1) NOT NULL DEFAULT '1'",
			'technicalthreads' => "INT(5) NOT NULL DEFAULT '0'",
			'allowsolvestatus' => "INT(1) NOT NULL DEFAULT '1'",
			'allowtechnicalstatus' => "INT(1) NOT NULL DEFAULT '1'",
			'allowbestanswerstatus' => "INT(1) NOT NULL DEFAULT '1'",
			'allowonholdstatus' => "INT(1) NOT NULL DEFAULT '1'",
			'allowhighlight' => "INT(1) NOT NULL DEFAULT '1'",
			'allownonsupportthreads' => "INT(1) NOT NULL DEFAULT '0'",
		],
		//mysupportmove should probably be left for moderation tools
		'threads'	=> [
			'status' => "INT(1) NOT NULL DEFAULT '0'",
			'statusuid' => "INT(10) NOT NULL DEFAULT '0'",
			'statustime' => "INT(10) NOT NULL DEFAULT '0'",
			'onhold' => "INT(1) NOT NULL DEFAULT '0'",
			'bestanswer' => "INT(10) NOT NULL DEFAULT '0'",
			'assign' => "INT(10) NOT NULL DEFAULT '0'",
			'assignuid' => "INT(10) NOT NULL DEFAULT '0'",
			'priority' => "INT(5) NOT NULL DEFAULT '0'",
			'closedbymysupport' => "INT(1) NOT NULL DEFAULT '0'",
			'issupportthread' => "INT(1) NOT NULL DEFAULT '1'",
		],
		'users'	=> [
			'assignedthreads' => "VARCHAR(500) NOT NULL DEFAULT ''",
			'deniedsupport' => "INT(1) NOT NULL DEFAULT '0'",
			'deniedsupportreason' => "INT(5) NOT NULL DEFAULT '0'",
			'deniedsupportuid' => "INT(10) NOT NULL DEFAULT '0'",
			'mysupportdisplayastext' => "INT(1) NOT NULL DEFAULT '0'"
		],
		'usergroups'	=> [
			'canmarksolved' => "INT(1) NOT NULL DEFAULT '1'",
			//'canmarktechnical' => "INT(1) NOT NULL DEFAULT '0'",
			'canseetechnotice' => "INT(1) NOT NULL DEFAULT '1'",
			//'canassign' => "INT(1) NOT NULL DEFAULT '0'",
			'canbeassigned' => "INT(1) NOT NULL DEFAULT '1'",
			//'cansetpriorities' => "INT(1) NOT NULL DEFAULT '0'",
			'canseepriorities' => "INT(1) NOT NULL DEFAULT '0'",
			'canmarkbestanswer' => "INT(1) NOT NULL DEFAULT '1'",
			'canmarkonhold' => "INT(1) NOT NULL DEFAULT '1'",
			'canmarkasnonsupport' => "INT(1) NOT NULL DEFAULT '0'",
			'canmanagesupportdenial' => "INT(1) NOT NULL DEFAULT '0'",
		],
		// we will use the is_moderator() function to allow moderators run some tools so we leave only tools at least the author will be able to use
		'moderators'	=> [
			'canmarksolved' => "INT(1) NOT NULL DEFAULT '1'",
			'canmarktechnical' => "INT(1) NOT NULL DEFAULT '1'",
			'canassign' => "INT(1) NOT NULL DEFAULT '1'",
			'cansetpriorities' => "INT(1) NOT NULL DEFAULT '1'",
			'canmanagesupportdenial' => "INT(1) NOT NULL DEFAULT '1'", // this will be forum specific (mods) or all forums (super mods)
			'canmarkbestanswer' => "INT(1) NOT NULL DEFAULT '1'",
			'canmarkonhold' => "INT(1) NOT NULL DEFAULT '1'",
			'canmarkasnonsupport' => "INT(1) NOT NULL DEFAULT '1'",
		]
	];
}

// Verify DB indexes
function _db_verify_indexes()
{
	global $db;

	foreach(_db_tables() as $table => $fields)
	{
		if(!$db->table_exists($table))
		{
			continue;
		}

		if(isset($fields['unique_key']))
		{
			foreach($fields['unique_key'] as $k => $v)
			{
				if($db->index_exists($table, $k))
				{
					continue;
				}
	
				$db->write_query("ALTER TABLE {$db->table_prefix}{$table} ADD UNIQUE KEY {$k} ({$v})");
			}
		}
	}
}

// Verify DB tables
function _db_verify_tables()
{
	global $db;

	$collation = $db->build_create_table_collation();

	foreach(_db_tables() as $table => $fields)
	{
		if($db->table_exists($table))
		{
			foreach($fields as $field => $definition)
			{
				if($field == 'primary_key' || $field == 'unique_key')
				{
					continue;
				}

				if($db->field_exists($field, $table))
				{
					$db->modify_column($table, "`{$field}`", $definition);
				}
				else
				{
					$db->add_column($table, $field, $definition);
				}
			}
		}
		else
		{
			$query = "CREATE TABLE IF NOT EXISTS `{$db->table_prefix}{$table}` (";

			foreach($fields as $field => $definition)
			{
				if($field == 'primary_key')
				{
					$query .= "PRIMARY KEY (`{$definition}`)";
				}
				elseif($field != 'unique_key')
				{
					$query .= "`{$field}` {$definition},";
				}
			}

			$query .= ") ENGINE=MyISAM{$collation};";

			$db->write_query($query);
		}
	}

	_db_verify_indexes();
}

// Verify DB columns
function _db_verify_columns()
{
	global $db;

	foreach(_db_columns() as $table => $columns)
	{
		foreach($columns as $field => $definition)
		{
			if($db->field_exists($field, $table))
			{
				$db->modify_column($table, "`{$field}`", $definition);
			}
			else
			{
				$db->add_column($table, $field, $definition);
			}
		}
	}
}

// Install task file
function _install_task($action=1)
{
	global $db, $lang;

	\MySupport\Core\load_language();

	$query = $db->simple_select('tasks', '*', "file='mysupport'", ['limit' => 1]);

	$task = $db->fetch_array($query);

	if($task)
	{
		$db->update_query('tasks', ['enabled' => $action], "file='mysupport'");
	}
	else
	{
		include_once MYBB_ROOT.'inc/functions_task.php';

		$_ = $db->escape_string('*');

		$new_task = [
			'title'			=> $db->escape_string($lang->mysupport),
			'description'	=> $db->escape_string($lang->mysupport_task_description),
			'file'			=> $db->escape_string('mysupport'),
			'minute'		=> 0,
			'hour'			=> 0,
			'day'			=> $_,
			'weekday'		=> $_,
			'month'			=> $_,
			'enabled'		=> 1,
			'logging'		=> 1
		];

		$new_task['nextrun'] = fetch_next_run($new_task);

		$db->insert_query('tasks', $new_task);
	}
}

function _uninstall_task()
{
	global $db;

	$db->delete_query('tasks', "file='mysupport'");
}

function _deactivate_task()
{
	_install_task(0);
}

function get_settings_gid()
{
	global $db;

	$query = $db->simple_select('settinggroups', 'gid', "name = 'mysupport'", ['limit' => 1]);

	return (int)$db->fetch_field($query, 'gid');
}