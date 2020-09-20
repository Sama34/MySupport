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

namespace MySupport\AdminHooks;

/*
function admin_config_plugins_begin01()
{
	global $mybb, $lang, $page, $db;

	if($mybb->get_input('action') != 'mysupport')
	{
		return;
	}

	\MySupport\Core\load_language();

	if($mybb->request_method != 'post')
	{
		$page->output_confirm_action('index.php?module=config-plugins&amp;action=mysupport', $lang->mysupport_myalerts_confirm);
	}

	if($mybb->get_input('no') || !\MySupport\MyAlerts\MyAlertsIsIntegrable())
	{
		admin_redirect('index.php?module=config-plugins');
	}

	$availableLocations = \MySupport\MyAlerts\getAvailableLocations();

	$installedLocations = \MySupport\MyAlerts\getInstalledLocations();

	foreach($availableLocations as $availableLocation)
	{
		\MySupport\MyAlerts\installLocation($availableLocation);
	}

	flash_message($lang->mysupport_myalerts_success, 'success');

	admin_redirect('index.php?module=config-plugins');
}*/

function admin_config_plugins_deactivate()
{
	global $mybb, $page;

	if(
		$mybb->get_input('action') != 'deactivate' ||
		$mybb->get_input('plugin') != 'mysupport' ||
		!$mybb->get_input('uninstall', \MyBB::INPUT_INT)
	)
	{
		return;
	}

	if($mybb->request_method != 'post')
	{
		$page->output_confirm_action('index.php?module=config-plugins&amp;action=deactivate&amp;uninstall=1&amp;plugin=mysupport');
	}

	if($mybb->get_input('no'))
	{
		admin_redirect('index.php?module=config-plugins');
	}
}

function admin_load()
{
	global $modules_dir, $run_module, $action_file, $run_module, $page, $modules_dir_backup, $run_module_backup, $action_file_backup;

	if($run_module != 'config' || $page->active_action != 'mysupport')
	{
		return;
	}

	$modules_dir_backup = $modules_dir;

	$run_module_backup = $run_module;

	$action_file_backup = $action_file;

	$modules_dir = MYSUPPORT_ROOT;

	$run_module = 'admin';

	$action_file = 'mysupport.php';
}

function admin_config_action_handler($actions)
{
	$actions['mysupport'] = array(
		"active" => "mysupport",
		"file" => "mysupport.php"
	);

	return $actions;
}

function admin_config_menu($sub_menu)
{
	global $lang;

	$lang->load("config_mysupport");

	$sub_menu[] = array("id" => "mysupport", "title" => $lang->mysupport, "link" => "index.php?module=config-mysupport");

	return $sub_menu;
}

function admin_config_permissions($admin_permissions)
{
	global $lang;

	$lang->load("config_mysupport");

	$admin_permissions['mysupport'] = $lang->can_manage_mysupport;

	return $admin_permissions;
}

function admin_page_output_footer()
{
	global $mybb, $db;
	// we're viewing the form to change settings but not submitting it
	if($mybb->input["action"] == "change" && $mybb->request_method != "post")
	{
		$gid = \MySupport\Admin\get_settings_gid();
		// if the settings group we're editing is the same as the gid for the MySupport group, or there's no gid (viewing all settings), echo the peekers
		if($mybb->input["gid"] == $gid || !$mybb->input['gid'])
		{
			echo '<script type="text/javascript">
	jQuery(document).ready(function() {
	loadMySupportPeekers();
});
function loadMySupportPeekers()
{
	new Peeker($(".setting_mysupport_enabletechnical"), $("#row_setting_mysupport_hidetechnical"), 1, true);
	new Peeker($(".setting_mysupport_enabletechnical"), $("#row_setting_mysupport_technicalnotice"), 1, true);
	new Peeker($(".setting_mysupport_enableassign"), $("#row_setting_mysupport_assignpm"), 1, true);
	new Peeker($(".setting_mysupport_enableassign"), $("#row_setting_mysupport_assignsubscribe"), 1, true);
	new Peeker($("#setting_mysupport_pointssystem"), $("#row_setting_mysupport_pointssystemname"), /other/, false);
	new Peeker($("#setting_mysupport_pointssystem"), $("#row_setting_mysupport_pointssystemcolumn"), /other/, false);
	new Peeker($("#setting_mysupport_pointssystem"), $("#row_setting_mysupport_bestanswerpoints"), /[^none]/, false);
}
</script>';
		}
	}
}

// Insert the require code in the group edit page.
function admin_formcontainer_end(&$args)
{
	global $run_module, $form_container, $lang, $form, $mybb, $mysupport;

	if($run_module == 'user' && !empty($lang->forums_posts) && $form_container->_title == $lang->forums_posts)
	{
		\MySupport\Core\load_language();
	
		$mysupport_options = $mysupport_mod_options = array();
		foreach(\MySupport\Admin\_db_columns()['usergroups'] as $field => $definition)
		{
			$modperms = 'mysupport_options';

			if($field == 'canmanagesupportdenial')
			{
				$modperms = 'mysupport_mod_options';
			}

			$lang_var = 'mysupport_usergroups_'.$field;
			${$modperms}[] = $form->generate_check_box($field, 1, $lang->$lang_var, array('id' => $field, 'checked' => $mybb->get_input($field, \MyBB::INPUT_INT)));
		}

		$form_container->output_row($lang->mysupport, '', '<div class="group_settings_bit">'.implode('</div><div class="group_settings_bit">', $mysupport_options).'</div>');

		$form_container->output_row($lang->mysupport_usergroups_moderator, '', '<div class="group_settings_bit">'.implode('</div><div class="group_settings_bit">', $mysupport_mod_options).'</div>');
	}
}

// Save group data
function admin_user_groups_edit_commit()
{
	global $updated_group, $mybb, $mysupport, $updated_group;

	foreach(\MySupport\Admin\_db_columns()['usergroups'] as $field => $definition)
	{
		$updated_group[$field] = $mybb->get_input($field, \MyBB::INPUT_INT);
	}
}

function admin_formcontainer_output_row(&$args)
{
	global $lang, $mybb, $form, $forum_data, $form_container, $mysupport, $mod_data;

	static $done = false;

	if($args['title'] == $lang->forum && $lang->forum && $mybb->get_input('module', \MyBB::INPUT_STRING) == 'forum-management' && $mybb->get_input('action', \MyBB::INPUT_STRING) == 'editmod')
	{

		\MySupport\Core\load_language();

		$mysupport_options = array();
		foreach(\MySupport\Admin\_db_columns()['moderators'] as $field => $definition)
		{
			$lang_var = 'mysupport_moderators_'.$field;
			$mysupport_options[] = $form->generate_check_box($field, 1, $lang->$lang_var, array('id' => $field, 'checked' => $mod_data[$field]));
		}

		$form_container->output_row($lang->mysupport, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $mysupport_options)."</div>");
	}

	if($args['title'] == $lang->misc_options && $lang->misc_options && $mybb->get_input('module', \MyBB::INPUT_STRING) == 'forum-management' && $mybb->get_input('action', \MyBB::INPUT_STRING) == 'edit' && !$done)
	{
		$done = true;

		\MySupport\Core\load_language();

		$mysupport_options = array();
		foreach(\MySupport\Admin\_db_columns()['forums'] as $field => $definition)
		{
			if($field == 'technicalthreads')
			{
				continue;
			}

			$lang_var = 'mysupport_forums_'.$field;
			$mysupport_options[] = $form->generate_check_box($field, 1, $lang->$lang_var, array('id' => $field, 'checked' => $forum_data[$field]));
		}

		$form_container->output_row($lang->mysupport, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $mysupport_options)."</div>");
	}
}

// Save forum data
function admin_forum_management_edit_commit()
{
	global $mybb, $mysupport, $db, $fid;

	$update_array = array();
	foreach(\MySupport\Admin\_db_columns()['forums'] as $field => $definition)
	{
		$update_array[$field] = $mybb->get_input($field, \MyBB::INPUT_INT);
	}

	$db->update_query("forums", $update_array, "fid='{$fid}'");

	$mybb->cache->update_forums();
}

// Save forum data
function admin_forum_management_editmod_commit()
{
	global $mybb, $mysupport, $update_array;

	foreach(\MySupport\Admin\_db_columns()['moderators'] as $field => $definition)
	{
		$update_array[$field] = $mybb->get_input($field, \MyBB::INPUT_INT);
	}
}

function admin_tools_cache_start()
{
	control_object($GLOBALS['cache'], '
		function update_mysupport()
		{
			\MySupport\Core\_cache();
		}
	');
}

function admin_tools_cache_rebuild()
{
	admin_tools_cache_start();
}