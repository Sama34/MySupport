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






































































// loads the dropdown menu for inline thread moderation
function mysupport_inline_thread_moderation()
{
	global $mybb, $db, $cache, $lang, $templates, $foruminfo, $mysupport_inline_thread_moderation;

	$lang->load("mysupport");

	$mysupport_solved = $mysupport_not_solved = $mysupport_solved_and_close = $mysupport_technical = $mysupport_not_technical = "";
	if(mysupport_usergroup("canmarksolved"))
	{
		$mysupport_solved = "<option value=\"mysupport_status_1\">-- ".$lang->solved."</option>";
		$mysupport_not_solved = "<option value=\"mysupport_status_0\">-- ".$lang->not_solved."</option>";
		if($mybb->settings['mysupport_closewhensolved'] != "never")
		{
			$mysupport_solved_and_close = "<option value=\"mysupport_status_3\">-- ".$lang->solved_close."</option>";
		}
	}
	if($mybb->settings['mysupport_enabletechnical'])
	{
		if(mysupport_usergroup("canmarktechnical"))
		{
			$mysupport_technical = "<option value=\"mysupport_status_2\">-- ".$lang->technical."</option>";
			$mysupport_not_technical = "<option value=\"mysupport_status_4\">-- ".$lang->not_technical."</option>";
		}
	}

	$mysupport_onhold = $mysupport_offhold = "";
	if($mybb->settings['mysupport_enableonhold'])
	{
		if(mysupport_usergroup("canmarksolved"))
		{
			$mysupport_onhold = "<option value=\"mysupport_onhold_1\">-- ".$lang->hold_status_onhold."</option>";
			$mysupport_offhold = "<option value=\"mysupport_onhold_0\">-- ".$lang->hold_status_offhold."</option>";
		}
	}

	if($mybb->settings['mysupport_enableassign'])
	{
		$mysupport_assign = "";
		$assign_users = mysupport_get_assign_users();
		// only continue if there's one or more users that can be assigned threads
		$mysupport_assign .= "<option value=\"mysupport_assign_find\">-- <i>{$lang->my_support_inline_find}</i></option>\n";
		if(!empty($assign_users))
		{
			foreach($assign_users as $assign_userid => $assign_username)
			{
				$mysupport_assign .= "<option value=\"mysupport_assign_".intval($assign_userid)."\">-- ".htmlspecialchars_uni($assign_username)."</option>\n";
			}
		}
	}

	if($mybb->settings['mysupport_enablepriorities'])
	{
		$mysupport_cache = $cache->read("mysupport");
		$mysupport_priorities = "";
		// only continue if there's any priorities
		if(!empty($mysupport_cache['priorities']))
		{
			foreach($mysupport_cache['priorities'] as $priority)
			{
				$mysupport_priorities .= "<option value=\"mysupport_priority_".intval($priority['mid'])."\">-- ".htmlspecialchars_uni($priority['name'])."</option>\n";
			}
		}
	}

	$mysupport_categories = "";
	$categories_users = mysupport_get_categories($foruminfo['fid']);
	// only continue if there's any priorities
	if(!empty($categories_users))
	{
		foreach($categories_users as $category_id => $category_name)
		{
			$mysupport_categories .= "<option value=\"mysupport_priority_".intval($category_id)."\">-- ".htmlspecialchars_uni($category_name)."</option>\n";
		}
	}

	$mysupport_inline_thread_moderation = eval($templates->render('mysupport_inline_thread_moderation'));
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
 * Generates a list of all forums that have MySupport enabled.
 *
 * @param array Array of forums that have MySupport enabled.
**/
function mysupport_forums()
{
	global $cache;

	$forums = $cache->read("forums");
	$mysupport_forums = array();

	foreach($forums as $forum)
	{
		// if this forum/category has MySupport enabled, add it to the array
		if($forum['mysupport'] == 1)
		{
			if(!in_array($forum['fid'], $mysupport_forums))
			{
				$mysupport_forums[] = $forum['fid'];
			}
		}
		// if this forum/category hasn't got MySupport enabled...
		else
		{
			// ... go through the parent list...
			$parentlist = explode(",", $forum['parentlist']);
			foreach($parentlist as $parent)
			{
				// ... if this parent has MySupport enabled...
				if($forums[$parent]['mysupport'] == 1)
				{
					// ... add the original forum we're looking at to the list
					if(!in_array($forum['fid'], $mysupport_forums))
					{
						$mysupport_forums[] = $forum['fid'];
						continue;
					}
					// this is for if we enable MySupport for a whole category; this will pick up all the forums inside that category and add them to the array
				}
			}
		}
	}

	return $mysupport_forums;
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
 * Change the status of a thread.
 *
 * @param array Information about the thread.
 * @param int The new status.
 * @param bool If this is changing the status of multiple threads.
**/
function mysupport_change_status($thread_info, $status = 0, $multiple = false)
{
	global $mybb, $db, $lang, $cache;

	$status = intval($status);
	if($status == 3)
	{
		// if it's 3, we're solving and closing, but we'll just check for regular solving in the list of things to log
		// saves needing to have a 3, for the solving and closing option, in the setting of what to log
		// then below it'll check if 1 is in the list of things to log; 1 is normal solving, so if that's in the list, it'll log this too
		$log_status = 1;
	}
	else
	{
		$log_status = $status;
	}

	if($multiple)
	{
		$tid = -1;
		$old_status = -1;
	}
	else
	{
		$tid = intval($thread_info['tid']);
		$old_status = intval($thread_info['status']);
	}

	$move_fid = "";
	/*
	$forums = $cache->read("forums");
	foreach($forums as $forum)
	{
		if(!empty($forum['mysupportmove']) && $forum['mysupportmove'] != 0)
		{
			$move_fid = intval($forum['fid']);
			break;
		}
	}
	*/
	// are we marking it as solved and is it being moved?
	if(!empty($move_fid) && ($status == 1 || $status == 3))
	{
		if($mybb->settings['mysupport_moveredirect'] == "none")
		{
			$move_type = "move";
			$redirect_time = 0;
		}
		else
		{
			$move_type = "redirect";
			if($mybb->settings['mysupport_moveredirect'] == "forever")
			{
				$redirect_time = 0;
			}
			else
			{
				$redirect_time = intval($mybb->settings['mysupport_moveredirect']);
			}
		}
		if($multiple)
		{
			$move_tids = $thread_info;
		}
		else
		{
			$move_tids = array($thread_info['tid']);
		}
		require_once MYBB_ROOT."inc/class_moderation.php";
		$moderation = new Moderation;
		// the reason it loops through using move_thread is because move_threads doesn't give the option for a redirect
		// if it's not a multiple thread it will just loop through once as there'd only be one value in the array
		foreach($move_tids as $move_tid)
		{
			$moderation->move_thread($move_tid, $move_fid, $move_type, $redirect_time);
		}
	}

	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}

	// we need to build an array of users who have been assigned threads before the assignment is removed
	if($status == 1 || $status == 3)
	{
		$query = $db->simple_select("threads", "DISTINCT assign", $where_sql." AND assign != '0'");
		$assign_users = array();
		while($user = $db->fetch_field($query, "assign"))
		{
			$assign_users[] = $user;
		}
	}

	if($status == 3 || ($status == 1 && $mybb->settings['mysupport_closewhensolved'] == "always"))
	{
		// the bit after || here is for if we're marking as solved via marking a post as the best answer, it will close if it's set to always close
		// the incoming status would be 1 but we need to close it if necessary
		$status_update = array(
			"closed" => 1,
			"status" => 1,
			"statusuid" => intval($mybb->user['uid']),
			"statustime" => TIME_NOW,
			"assign" => 0,
			"assignuid" => 0,
			"priority" => 0,
			"closedbymysupport" => 1,
			"onhold" => 0
		);
	}
	elseif($status == 0)
	{
		// if we're marking it as unsolved, a post may have been marked as the best answer when it was originally solved, best remove it, as well as rest everything else
		$status_update = array(
			"status" => 0,
			"statusuid" => 0,
			"statustime" => 0,
			"bestanswer" => 0
		);
	}
	elseif($status == 4)
	{
		/** if it's 4, it's because it was marked as being not technical after being marked technical
		 ** basically put back to the original status of not solved (0)
		 ** however it needs to be 4 so we can differentiate between this action (technical => not technical), and a user marking it as not solved
		 ** because both of these options eventually set it back to 0
		 ** so the mod log entry will say the correct action as the status was 4 and it used that
		 ** now that the log has been inserted we can set it to 0 again for the thread update query so it's marked as unsolved **/
		$status_update = array(
			"status" => 0,
			"statusuid" => 0,
			"statustime" => 0
		);
	}
	elseif($status == 2)
	{
		$status_update = array(
			"status" => 2,
			"statusuid" => intval($mybb->user['uid']),
			"statustime" => TIME_NOW
		);
	}
	// if not, it's being marked as solved
	else
	{
		$status_update = array(
			"status" => 1,
			"statusuid" => intval($mybb->user['uid']),
			"statustime" => TIME_NOW,
			"assign" => 0,
			"assignuid" => 0,
			"priority" => 0,
			"onhold" => 0
		);
	}

	$db->update_query("threads", $status_update, $where_sql);

	// if the thread is being marked as technical, being marked as something else after being marked technical, or we're changing the status of multiple threads, recount the number of technical threads
	if($status == 2 || $old_status == 2 || $multiple)
	{
		mysupport_recount_technical_threads();
	}
	// if the thread is being marked as solved, recount the number of assigned threads for any users who were assigned threads that are now being marked as solved
	if($status == 1 || $status == 3)
	{
		foreach($assign_users as $user)
		{
			mysupport_recount_assigned_threads($user);
		}
	}
	if($status == 0)
	{
		// if we're marking a thread(s) as unsolved, re-open any threads that were closed when they were marked as solved, but not any that were closed by denying support
		$update = array(
			"closed" => 0,
			"closedbymysupport" => 0
		);
		$db->update_query("threads", $update, $where_sql." AND closed = '1' AND closedbymysupport = '1'");
	}

	// get the friendly version of the status for the redirect message and mod log
	$friendly_old_status = "'".mysupport_get_friendly_status($old_status)."'";
	$friendly_new_status = "'".mysupport_get_friendly_status($status)."'";

	if($multiple)
	{
		mysupport_mod_log_action($log_status, $lang->sprintf($lang->status_change_mod_log_multi, count($thread_info), $friendly_new_status));
		mysupport_redirect_message($lang->sprintf($lang->status_change_success_multi, count($thread_info), htmlspecialchars_uni($friendly_new_status)));
	}
	else
	{
		mysupport_mod_log_action($log_status, $lang->sprintf($lang->status_change_mod_log, $friendly_new_status));
		mysupport_redirect_message($lang->sprintf($lang->status_change_success, htmlspecialchars_uni($friendly_old_status), htmlspecialchars_uni($friendly_new_status)));
	}
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
 * Get the relative time of when a thread was solved.
 *
 * @param int Timestamp of when the thread was solved.
 * @return string Relative time of when the thread was solved.
**/
function mysupport_relative_time($statustime)
{
	return my_date('relative', $statustime);
}

/**
 * Get the count of technical or assigned threads.
 *
 * @param int The FID we're in.
 * @return int The number of technical or assigned threads in this forum.
**/
function mysupport_get_count($type, $fid = 0)
{
	global $mybb, $db, $cache;

	$fid = intval($fid);
	$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));

	$count = 0;
	$forums = $cache->read("forums");
	if($type == "technical")
	{
		// there's no FID given so this is loading the total number of technical threads
		if($fid == 0)
		{
			foreach($forums as $forum => $info)
			{
				$count += $info['technicalthreads'];
			}
		}
		// we have an FID, so count the number of technical threads in this specific forum and all it's parents
		else
		{
			$forums_list = array();
			foreach($forums as $forum => $info)
			{
				$parentlist = $info['parentlist'];
				if(strpos(",".$parentlist.",", ",".$fid.",") !== false)
				{
					$forums_list[] = $forum;
				}
			}
			foreach($forums_list as $forum)
			{
				$count += $forums[$forum]['technicalthreads'];
			}
		}
	}
	elseif($type == "assigned")
	{
		$assigned = unserialize($mybb->user['assignedthreads']);
		if(!is_array($assigned))
		{
			return 0;
		}
		// there's no FID given so this is loading the total number of assigned threads
		if($fid == 0)
		{
			foreach($assigned as $fid => $threads)
			{
				$count += $threads;
			}
		}
		// we have an FID, so count the number of assigned threads in this specific forum
		else
		{
			$forums_list = array();
			foreach($forums as $forum => $info)
			{
				$parentlist = $info['parentlist'];
				if(strpos(",".$parentlist.",", ",".$fid.",") !== false)
				{
					$forums_list[] = $forum;
				}
			}
			foreach($forums_list as $forum)
			{
				$count += $assigned[$forum];
			}
		}
	}

	return $count;
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
 * Check if a points system is enabled for points system integration.
 *
 * @return bool Whether or not your chosen points system is enabled.
**/
function mysupport_points_system_enabled()
{
	global $mybb, $cache;

	$plugins = $cache->read("plugins");

	if($mybb->settings['mysupport_pointssystem'] != "none")
	{
		if($mybb->settings['mysupport_pointssystem'] == "other")
		{
			$mybb->settings['mysupportpointssystem'] = $mybb->settings['mysupportpointssystemname'];
		}
		return in_array($mybb->settings['mysupport_pointssystem'], $plugins['active']);
	}
	return false;
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

/**
 * Build an array of who can be assigned threads. Used to build the dropdown menus, and also check a valid user has been chosen.
 *
 * @return array Array of available categories.
**/
function mysupport_get_assign_users()
{
	global $db, $cache;

	// who can be assigned threads?
	$groups = $cache->read("usergroups");
	$assign_groups = array();
	foreach($groups as $group)
	{
		if($group['canbeassigned'] == 1)
		{
			$assign_groups[] = intval($group['gid']);
		}
	}

	// only continue if there's one or more groups that can be assigned threads
	if(!empty($assign_groups))
	{
		$assigngroups = "";
		$assigngroups = implode(",", array_map("intval", $assign_groups));
		$assign_concat_sql = "";
		foreach($assign_groups as $assign_group)
		{
			if(!empty($assign_concat_sql))
			{
				$assign_concat_sql .= " OR ";
			}
			$assign_concat_sql .= "CONCAT(',',additionalgroups,',') LIKE '%,{$assign_group},%'";
		}

		$query = $db->simple_select("users", "uid, username", "usergroup IN (".$db->escape_string($assigngroups).") OR displaygroup IN (".$db->escape_string($assigngroups).") OR {$assign_concat_sql}");
		$assign_users = array();
		while($assigned = $db->fetch_array($query))
		{
			$assign_users[$assigned['uid']] = $assigned['username'];
		}
	}
	return $assign_users;
}

/**
 * Build an array of available categories (thread prefixes). Used to build the dropdown menus, and also check a valid category has been chosen.
 *
 * @param array Info on the forum.
 * @return array Array of available categories.
**/
function mysupport_get_categories($forum)
{
	global $mybb, $db;

	$forums_concat_sql = $groups_concat_sql = "";

	$parent_list = explode(",", $forum['parentlist']);
	foreach($parent_list as $parent)
	{
		if(!empty($forums_concat_sql))
		{
			$forums_concat_sql .= " OR ";
		}
		$forums_concat_sql .= "CONCAT(',',forums,',') LIKE '%,".intval($parent).",%'";
	}
	$forums_concat_sql = "(".$forums_concat_sql." OR forums = '-1')";

	$usergroup_list = $mybb->user['usergroup'];
	if(!empty($mybb->user['additionalgroups']))
	{
		$usergroup_list .= ",".$mybb->user['additionalgroups'];
	}
	$usergroup_list = explode(",", $usergroup_list);
	foreach($usergroup_list as $usergroup)
	{
		if(!empty($groups_concat_sql))
		{
			$groups_concat_sql .= " OR ";
		}
		$groups_concat_sql .= "CONCAT(',',groups,',') LIKE '%,".intval($usergroup).",%'";
	}
	$groups_concat_sql = "(".$groups_concat_sql." OR groups = '-1')";

	$query = $db->simple_select("threadprefixes", "pid, prefix", "{$forums_concat_sql} AND {$groups_concat_sql}");
	$categories = array();
	while($category = $db->fetch_array($query))
	{
		$categories[$category['pid']] = $category['prefix'];
	}
	return $categories;
}

/**
 * Show the status of a thread.
 *
 * @param int The status of the thread.
 * @param int The time the thread was solved.
 * @param int The TID of the thread.
**/
function mysupport_get_display_status($status, $onhold = 0, $statustime = 0, $thread_author = 0)
{
	global $mybb, $lang, $templates, $theme, $mysupport_status;

	$thread_author = intval($thread_author);

	// if this user is logged in, we want to override the global setting for display with their own setting
	if($mybb->user['uid'] != 0 && $mybb->settings['mysupport_displaytypeuserchange'])
	{
		if($mybb->user['mysupportdisplayastext'] == 1)
		{
			$mybb->settings['mysupport_displaytype'] = "text";
		}
		else
		{
			$mybb->settings['mysupport_displaytype'] = "image";
		}
	}

	// big check to see if either the status is to be show to everybody, only to people who can mark as solved, or to people who can mark as solved or who authored the thread
	if($mybb->settings['mysupport_displayto'] == "all" || ($mybb->settings['mysupport_displayto'] == "canmas" && mysupport_usergroup("canmarksolved")) || ($mybb->settings['mysupport_displayto'] == "canmasauthor" && (mysupport_usergroup("canmarksolved") || $mybb->user['uid'] == $thread_author)))
	{
		if($mybb->settings['mysupport_relativetime'])
		{
			$date_time = mysupport_relative_time($statustime);
			$status_title = htmlspecialchars_uni($lang->sprintf($lang->technical_time, $date_time_technical));
		}
		else
		{
			$date = my_date(intval($mybb->settings['dateformat']), intval($statustime));
			$time = my_date(intval($mybb->settings['timeformat']), intval($statustime));
			$date_time = $date." ".$time;
		}

		if($mybb->settings['mysupport_displaytype'] == "text")
		{
			// if this user cannot mark a thread as technical and people who can't mark as technical can't see that a technical thread is technical, don't execute this
			// I used the word technical 4 times in that sentence didn't I? sorry about that
			if($status == 2 && !($mybb->settings['mysupport_hidetechnical'] && !mysupport_usergroup("canmarktechnical")))
			{
				$status_class = "technical";
				$status_text = $lang->technical;
				$status_title = htmlspecialchars_uni($lang->sprintf($lang->technical_time, $date_time));
			}
			elseif($status == 1)
			{
				$status_class = "solved";
				$status_text = $lang->solved;
				$status_title = htmlspecialchars_uni($lang->sprintf($lang->solved_time, $date_time));
			}
			else
			{
				$status_class = "notsolved";
				$status_text = $status_title = $lang->not_solved;
			}

			if($onhold == 1)
			{
				$status_class = "onhold";
				$status_text = $lang->onhold;
				$status_title = $lang->onhold." - ".$status_title;
			}

			$mysupport_status = eval($templates->render('mysupport_status_text'));
		}
		else
		{
			// if this user cannot mark a thread as technical and people who can't mark as technical can't see that a technical thread is technical, don't execute this
			// I used the word technical 4 times in that sentence didn't I? sorry about that
			if($status == 2 && !($mybb->settings['mysupport_hidetechnical'] && !mysupport_usergroup("canmarktechnical")))
			{
				$status_img = "technical";
				$status_title = htmlspecialchars_uni($lang->sprintf($lang->technical_time, $date_time));
			}
			elseif($status == 1)
			{
				$status_img = "solved";
				$status_title = htmlspecialchars_uni($lang->sprintf($lang->solved_time, $date_time));
			}
			else
			{
				$status_img = "notsolved";
				$status_title = $lang->not_solved;
			}

			if($onhold == 1)
			{
				$status_img = "onhold";
				$status_title = $lang->onhold." - ".$status_title;
			}

			$mysupport_status = eval($templates->render('mysupport_status_image'));
		}

		return $mysupport_status;
	}
}

/**
 * Get the text version of the status of a thread.
 *
 * @param int The status of the thread.
 * @param string The text version of the status of the thread.
**/
function mysupport_get_friendly_status($status = 0)
{
	global $lang;

	$lang->load("mysupport");

	$status = intval($status);
	switch($status)
	{
		// has it been marked as not techincal?
		case 4:
			$friendlystatus = $lang->not_technical;
			break;
		// is it a technical thread?
		case 2:
			$friendlystatus = $lang->technical;
			break;
		// no, is it a solved thread?
		case 3:
		case 1:
			$friendlystatus = $lang->solved;
			break;
		// must be not solved then
		default:
			$friendlystatus = $lang->not_solved;
	}

	return $friendlystatus;
}