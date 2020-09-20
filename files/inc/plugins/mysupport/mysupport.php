<?php

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








































































// general functions

/**
 * Check is MySupport is enabled in this forum.
 *
 * @param int The FID of the thread.
 * @param bool Whether or not this is a MySupport forum.
**/
function mysupport_forum($fid)
{
	global $cache;

	$fid = intval($fid);
	$forum_info = get_forum($fid);

	// the parent list includes the ID of the forum itself so this will quickly check the forum and all it's parents
	// only slight issue is that the ID of this forum would be at the end of this list, so it'd check the parents first, but if it returns true, it returns true, doesn't really matter
	$forum_ids = explode(",", $forum_info['parentlist']);

	// load the forums cache
	$forums = $cache->read("forums");
	foreach($forums as $forum)
	{
		// if this forum is in the parent list
		if(in_array($forum['fid'], $forum_ids))
		{
			// if this is a MySupport forum, return true
			if($forum['mysupport'] == 1)
			{
				return true;
			}
		}
	}
	return false;
}

/**
 * Check the usergroups for MySupport permissions.
 *
 * @param string What permission we're checking.
 * @param int Usergroup of the user we're checking.
**/
function mysupport_usergroup($perm, $usergroups = array())
{
	global $mybb, $cache;

	// does this key even exist? Check here if it does
	if(!array_key_exists($perm, $mybb->usergroup))
	{
		return false;
	}

	// if no usergroups are specified, we're checking our own usergroups
	if(empty($usergroups))
	{
		$usergroups = array_merge(array($mybb->user['usergroup']), explode(",", $mybb->user['additionalgroups']));
	}

	// load the usergroups cache
	$groups = $cache->read("usergroups");
	foreach($groups as $group)
	{
		// if this user is in this group
		if(in_array($group['gid'], $usergroups))
		{
			// if this group can perform this action, return true
			if($group[$perm] == 1)
			{
				return true;
			}
		}
	}
	return false;
}

/**
 * Shows an error with a header indicating it's been created by MySupport, to save having to put a header into every call of error()
 *
 * @param string The error to show.
**/
function mysupport_error($error)
{
	global $lang;

	$lang->load("mysupport");

	error($error, $lang->mysupport_error);
	exit;
}

/**
 * Change the hold status of a thread.
 *
 * @param array Information about the thread.
 * @param int The new hold status.
 * @param bool If this is changing the hold status of multiple threads.
**/
function mysupport_change_hold($thread_info, $onhold = 0, $multiple = false)
{
	global $db, $cache, $lang;

	$tid = intval($thread_info['tid']);
	$onhold = intval($onhold);

	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}

	if($onhold == 0)
	{
		$update = array(
			"onhold" => 0
		);
		$db->update_query("threads", $update, $where_sql);

		if($multiple)
		{
			mysupport_mod_log_action(12, $lang->sprintf($lang->hold_off_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->hold_off_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(12, $lang->hold_off_success);
			mysupport_redirect_message($lang->hold_off_success);
		}
	}
	else
	{
		$update = array(
			"onhold" => 1
		);
		if($multiple)
		{
			// when changing the hold status via the form in a thread, you can't you can't change the hold status if the thread's solved
			// here, it's not as easy to check for that; instead, only change the hold status if the thread isn't solved
			$where_sql .= " AND status != '1'";
		}
		$db->update_query("threads", $update, $where_sql);

		if($multiple)
		{
			mysupport_mod_log_action(12, $lang->sprintf($lang->hold_on_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->hold_on_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(12, $lang->hold_on_success);
			mysupport_redirect_message($lang->hold_on_success);
		}
	}
}

/**
 * Change who a thread is assigned to.
 *
 * @param array Information about the thread.
 * @param int The UID of who we're assigning it to now.
 * @param bool If this is changing the assigned user of multiple threads.
**/
function mysupport_change_assign($thread_info, $assign, $multiple = false)
{
	global $mybb, $db, $lang;

	if($multiple)
	{
		$fid = -1;
		$tid = -1;
		$old_assign = -1;
	}
	else
	{
		$fid = intval($thread_info['fid']);
		$tid = intval($thread_info['tid']);
		$old_assign = intval($thread_info['assign']);
	}

	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}

	// because we can assign a thread to somebody if it's already assigned to somebody else, we need to get a list of all the users who have been assigned the threads we're dealing with, so we can recount the number of assigned threads for all these users after the assignment has been chnaged
	$query = $db->simple_select("threads", "DISTINCT assign", $where_sql." AND assign != '0'");
	$assign_users = array(
		$assign => $assign
	);
	while($user = $db->fetch_field($query, "assign"))
	{
		$assign_users[$user] = $user;
	}

	// if we're unassigning it
	if($assign == "-1")
	{
		$update = array(
			"assign" => 0,
			"assignuid" => 0
		);
		// remove the assignment on the thread
		$db->update_query("threads", $update, $where_sql);

		// get information on who it was assigned to
		$user = get_user($old_assign);

		if($multiple)
		{
			mysupport_mod_log_action(6, $lang->sprintf($lang->unassigned_from_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->unassigned_from_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(6, $lang->sprintf($lang->unassigned_from_success, $user['username']));
			mysupport_redirect_message($lang->sprintf($lang->unassigned_from_success, htmlspecialchars_uni($user['username'])));
		}
	}
	// if we're assigning it or changing the assignment
	else
	{
		$update = array(
			"assign" => intval($assign),
			"assignuid" => intval($mybb->user['uid'])
		);
		if($multiple)
		{
			// when assigning via the form in a thread, you can't assign a thread if it's solved
			// here, it's not as easy to check for that; instead, only assign a thread if it isn't solved
			$where_sql .= " AND status != '1'";
		}
		// assign the thread
		$db->update_query("threads", $update, $where_sql);

		$user = get_user($assign);
		$username = $db->escape_string($user['username']);

		if($mybb->settings['mysupport_assignpm'])
		{
			// send the PM
			mysupport_send_assign_pm($assign, $fid, $tid);
		}

		if($mybb->settings['mysupport_assignsubscribe'])
		{
			if($multiple)
			{
				$tids = $thread_info;
			}
			else
			{
				$tids = array($thread_info['tid']);
			}
			foreach($tids as $tid)
			{
				$query = $db->simple_select("threadsubscriptions", "*", "uid = '{$assign}' AND tid = '{$tid}'");
				// only do this if they're not already subscribed
				if($db->num_rows($query) == 0)
				{
					if($user['subscriptionmethod'] == 2)
					{
						$subscription_method = 2;
					}
					// this is if their subscription method is 1 OR 0
					// done like this because this setting forces a subscription, but we'll only subscribe them via email if the user wants it
					else
					{
						$subscription_method = 1;
					}
					require_once MYBB_ROOT."inc/functions_user.php";
					add_subscribed_thread($tid, $subscription_method, $assign);
				}
			}
		}

		if($multiple)
		{
			mysupport_mod_log_action(5, $lang->sprintf($lang->assigned_to_success_multi, count($thread_info), $user['username']));
			mysupport_redirect_message($lang->sprintf($lang->assigned_to_success_multi, count($thread_info), htmlspecialchars_uni($user['username'])));
		}
		else
		{
			mysupport_mod_log_action(5, $lang->sprintf($lang->assigned_to_success, $username));
			mysupport_redirect_message($lang->sprintf($lang->assigned_to_success, htmlspecialchars_uni($username)));
		}
	}

	foreach($assign_users as $user)
	{
		mysupport_recount_assigned_threads($user);
	}
}

/**
 * Change the priority of a thread
 *
 * @param array Information about the thread.
 * @param int The ID of the new priority.
 * @param bool If this is changing the priority of multiple threads.
**/
function mysupport_change_priority($thread_info, $priority, $multiple = false)
{
	global $db, $cache, $lang;

	$tid = intval($thread_info['tid']);
	$priority = $db->escape_string($priority);

	$mysupport_cache = $cache->read("mysupport");
	$priorities = array();
	if(!empty($mysupport_cache['priorities']))
	{
		foreach($mysupport_cache['priorities'] as $priority_info)
		{
			$priorities[$priority_info['mid']] = $priority_info['name'];
		}
	}

	$new_priority = $priorities[$priority];
	$old_priority = $priorities[$thread_info['priority']];

	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}

	if($priority == "-1")
	{
		$update = array(
			"priority" => 0
		);
		$db->update_query("threads", $update, $where_sql);

		if($multiple)
		{
			mysupport_mod_log_action(8, $lang->sprintf($lang->priority_remove_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->priority_remove_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(8, $lang->sprintf($lang->priority_remove_success, $old_priority));
			mysupport_redirect_message($lang->sprintf($lang->priority_remove_success, htmlspecialchars_uni($old_priority)));
		}
	}
	else
	{
		$update = array(
			"priority" => intval($priority)
		);
		if($multiple)
		{
			// when setting a priority via the form in a thread, you can't give a thread a priority if it's solved
			// here, it's not as easy to check for that; instead, only set the priority if the thread isn't solved
			$where_sql .= " AND status != '1'";
		}
		$db->update_query("threads", $update, $where_sql);

		if($multiple)
		{
			mysupport_mod_log_action(6, $lang->sprintf($lang->priority_change_success_to_multi, count($thread_info), $new_priority));
			mysupport_redirect_message($lang->sprintf($lang->priority_change_success_to_multi, count($thread_info), $new_priority));
		}
		else
		{
			if($thread['priority'] == 0)
			{
				mysupport_mod_log_action(7, $lang->sprintf($lang->priority_change_success_to, $new_priority));
				mysupport_redirect_message($lang->sprintf($lang->priority_change_success_to, htmlspecialchars_uni($new_priority)));
			}
			else
			{
				mysupport_mod_log_action(7, $lang->sprintf($lang->priority_change_success_fromto, $old_priority, $new_priority));
				mysupport_redirect_message($lang->sprintf($lang->priority_change_success_fromto, htmlspecialchars_uni($old_priority), htmlspecialchars_uni($new_priority)));
			}
		}
	}
}

/**
 * Change the category of a thread
 *
 * @param array Information about the thread.
 * @param int The ID of the new category.
 * @param bool If this is changing the priority of multiple threads.
**/
function mysupport_change_category($thread_info, $category, $multiple = false)
{
	global $db, $lang;

	$tid = intval($thread_info['tid']);
	$category = $db->escape_string($category);

	$query = $db->simple_select("threadprefixes", "pid, prefix");
	$categories = array();
	while($category_info = $db->fetch_array($query))
	{
		$categories[$category_info['pid']] = htmlspecialchars_uni($category_info['prefix']);
	}

	$new_category = $categories[$category];
	$old_category = $categories[$thread_info['prefix']];

	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}

	if($category == "-1")
	{
		$update = array(
			"prefix" => 0
		);
		$db->update_query("threads", $update, $where_sql);

		if($multiple)
		{
			mysupport_mod_log_action(10, $lang->sprintf($lang->category_remove_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->category_remove_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(10, $lang->sprintf($lang->category_remove_success, $old_category));
			mysupport_redirect_message($lang->sprintf($lang->category_remove_success, htmlspecialchars_uni($old_category)));
		}
	}
	else
	{
		$update = array(
			"prefix" => $category
		);
		$db->update_query("threads", $update, $where_sql);

		if($multiple)
		{
			mysupport_mod_log_action(9, $lang->sprintf($lang->category_change_success_to_multi, count($thread_info), $new_category));
			mysupport_redirect_message($lang->sprintf($lang->category_change_success_to_multi, count($thread_info), htmlspecialchars_uni($new_category)));
		}
		else
		{
			if($thread['prefix'] == 0)
			{
				mysupport_mod_log_action(9, $lang->sprintf($lang->category_change_success_to, $new_category));
				mysupport_redirect_message($lang->sprintf($lang->category_change_success_to, htmlspecialchars_uni($new_category)));
			}
			else
			{
				mysupport_mod_log_action(9, $lang->sprintf($lang->category_change_success_fromto, $old_category, $new_category));
				mysupport_redirect_message($lang->sprintf($lang->category_change_success_fromto, htmlspecialchars_uni($old_category), htmlspecialchars_uni($new_category)));
			}
		}
	}
}

/**
 * Change whether or not a thread is a support thread
 *
 * @param array Information about the thread.
 * @param int If this thread is a support thread or not (1/0)
 * @param bool If this is changing the priority of multiple threads.
**/
function mysupport_change_issupportthread($thread_info, $issupportthread, $multiple = false)
{
	global $db, $lang;

	$tid = intval($thread_info['tid']);
	$issupportthread = intval($issupportthread);

	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}

	if($issupportthread == 1)
	{
		$update = array(
			"issupportthread" => 1
		);
		$db->update_query("threads", $update, $where_sql);

		if($multiple)
		{
			mysupport_mod_log_action(13, $lang->sprintf($lang->issupportthread_1_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->issupportthread_1_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(13, $lang->issupportthread_1);
			mysupport_redirect_message($lang->issupportthread_1);
		}
	}
	else
	{
		$update = array(
			"issupportthread" => 0
		);
		$db->update_query("threads", $update, $where_sql);

		if($multiple)
		{
			mysupport_mod_log_action(13, $lang->sprintf($lang->issupportthread_0_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->issupportthread_0_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(13, $lang->issupportthread_0);
			mysupport_redirect_message($lang->issupportthread_0);
		}
	}
}

/**
 * Add to the moderator log message.
 *
 * @param int The ID of the log action.
 * @param string The message to add.
**/
function mysupport_mod_log_action($id, $message)
{
	global $mybb, $mod_log_action;

	$id = intval($id);
	$mysupportmodlog = explode(",", $mybb->settings['mysupport_modlog']);
	// if this action shouldn't be logged, return false
	if(!in_array($id, $mysupportmodlog))
	{
		return false;
	}
	// if the message isn't empty, add a space
	if(!empty($mod_log_action))
	{
		$mod_log_action .= " ";
	}
	$mod_log_action .= $message;
}

/**
 * Add to the redirect message.
 *
 * @param string The message to add.
**/
function mysupport_redirect_message($message)
{
	global $redirect;

	// if the message isn't empty, add a new line
	if(!empty($redirect))
	{
		$redirect .= "<br /><br />";
	}
	$redirect .= $message;
}

/**
 * Send a PM about a new assignment
 *
 * @param int The UID of who we're assigning it to now.
 * @param int The FID the thread is in.
 * @param int The TID of the thread.
**/
function mysupport_send_assign_pm($uid, $fid, $tid)
{
	global $mybb, $db, $lang;

	if($uid == $mybb->user['uid'])
	{
		//return;
	}

	$uid = intval($uid);
	$fid = intval($fid);
	$tid = intval($tid);

	$user_info = get_user($uid);
	$username = $user_info['username'];

	$forum_url = $mybb->settings['bburl']."/".get_forum_link($fid);
	$forum_info = get_forum($fid);
	$forum_name = $forum_info['name'];

	$thread_url = $mybb->settings['bburl']."/".get_thread_link($tid);
	$thread_info = get_thread($tid);
	$thread_name = $thread_info['subject'];

	$recipients_to = array($uid);
	$recipients_bcc = array();

	$assigned_by_user_url = $mybb->settings['bburl']."/".get_profile_link($mybb->user['uid']);
	$assigned_by = $lang->sprintf($lang->assigned_by, $assigned_by_user_url, htmlspecialchars_uni($mybb->user['username']));

	$message = $lang->sprintf($lang->assign_pm_message, htmlspecialchars_uni($username), $forum_url, htmlspecialchars_uni($forum_name), $thread_url, htmlspecialchars_uni($thread_name), $assigned_by, $mybb->settings['bburl']);

	$pm = array(
		"subject" => $lang->assign_pm_subject,
		"message" => $message,
		"icon" => -1,
		"fromid" => 0,
		"toid" => $recipients_to,
		"bccid" => $recipients_bcc,
		"do" => '',
		"pmid" => '',
		"saveasdraft" => 0,
		"options" => array(
			"signature" => 1,
			"disablesmilies" => 0,
			"savecopy" => 0,
			"readreceipt" => 0
		)
	);

	require_once MYBB_ROOT."inc/datahandlers/pm.php";
	$pmhandler = new PMDataHandler();

	$pmhandler->admin_override = 1;
	$pmhandler->set_data($pm);

	if($pmhandler->validate_pm())
	{
		$pmhandler->insert_pm();
	}
}

/**
 * Recount how many technical threads there are in each forum.
 *
**/
function mysupport_recount_technical_threads()
{
	global $db, $cache;

	$update = array(
		"technicalthreads" => 0
	);
	$db->update_query("forums", $update);

	$query = $db->simple_select("threads", "fid", "status = '2'");
	$techthreads = array();
	while($fid = $db->fetch_field($query, "fid"))
	{
		if(!$techthreads[$fid])
		{
			$techthreads[$fid] = 0;
		}
		$techthreads[$fid]++;
	}

	foreach($techthreads as $forum => $count)
	{
		$update = array(
			"technicalthreads" => intval($count)
		);
		$db->update_query("forums", $update, "fid = '".intval($forum)."'");
	}

	$cache->update_forums();
}

/**
 * Recount how many threads a user has been assigned.
**/
function mysupport_recount_assigned_threads($uid)
{
	global $db, $cache;

	$uid = intval($uid);

	$query = $db->simple_select("threads", "fid", "assign = '{$uid}' AND status != '1'");
	$assigned = array();
	while($fid = $db->fetch_field($query, "fid"))
	{
		if(!$assigned[$fid])
		{
			$assigned[$fid] = 0;
		}
		$assigned[$fid]++;
	}
	$assigned = serialize($assigned);

	$update = array(
		"assignedthreads" => $db->escape_string($assigned)
	);
	$db->update_query("users", $update, "uid = '{$uid}'");
}

/**
 * Update points for certain MySupport actions.
 *
 * @param int The number of points to add/remove.
 * @param int The UID of the user we're adding/removing points to/from.
 * @param bool Is this removing points? Defaults to false as we'd be adding them most of the time.
**/
function mysupport_update_points($points, $uid, $removing = false)
{
	global $mybb, $db;

	$points = intval($points);
	$uid = intval($uid);

	switch($mybb->settings['mysupport_pointssystem'])
	{
		case "myps":
			$column = "myps";
			break;
		case "newpoints":
			$column = "newpoints";
			break;
		case "other":
			$column = $db->escape_string($mybb->settings['mysupport_pointssystemcolumn']);
			break;
		default:
			$column = "";
	}

	// if it somehow had to resort to the default option above or 'other' was selected but no custom column name was specified, don't run the query because it's going to create an SQL error, no column to update
	if(!empty($column))
	{
		if($removing)
		{
			$operator = "-";
		}
		else
		{
			$operator = "+";
		}

		$query = $db->write_query("UPDATE ".TABLE_PREFIX."users SET {$column} = {$column} {$operator} '{$points}' WHERE uid = '{$uid}'");
	}
}