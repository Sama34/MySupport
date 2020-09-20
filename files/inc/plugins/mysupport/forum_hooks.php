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

namespace MySupport\ForumHooks;

function global_start()
{
	global $templatelist, $mybb;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	$templatelist .= ',';

	if(defined('THIS_SCRIPT'))
	{
		if(THIS_SCRIPT == 'showthread.php')
		{
			$templatelist .= ', ';
		}

		if(THIS_SCRIPT == 'editpost.php' || THIS_SCRIPT == 'newthread.php')
		{
			$templatelist .= ', ';
		}

		if(THIS_SCRIPT == 'forumdisplay.php')
		{
			$templatelist .= ', ';
		}
	}

	if(\MySupport\MyAlerts\myalertsIsIntegrable())
	{
		if($mybb->user['uid'])
		{
            \MySupport\MyAlerts\registerMyalertsFormatters();
        }
    }
	
	global $cache;
	$cache->update_most_replied_threads();
	$cache->update_most_viewed_threads();
}

function build_friendly_wol_location_end(&$plugin_array)
{
	global $lang;

	if($plugin_array['user_activity']['activity'] == "modcp_techthreads")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_technical;
	}
	elseif($plugin_array['user_activity']['activity'] == "usercp_supportthreads")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_support;
	}
	elseif($plugin_array['user_activity']['activity'] == "modcp_supportdenial")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_support_denial;
	}
	elseif($plugin_array['user_activity']['activity'] == "modcp_supportdenial_deny")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_support_denial_deny;
	}
}

function datahandler_post_validate_post($data)
{
	global $db, $posthandler, $mysupport;

	if(!$mysupport->plugin_enabled || count($posthandler->get_errors()) > 0)
	{
		return;
	}
	// if we're editing a post, see if it's the last post in the thread and was written by the thread poster
	if($posthandler->method == "update")
	{
		$post = get_post($posthandler->data['pid']);
		$thread_tid = $post['tid'];
		$thread_uid = $post['uid'];

		$query = $db->simple_select("posts", "pid", "tid = '".intval($thread_tid)."'", array("order_by" => "dateline", "order_dir" => "DESC", "limit" => 1));
		$pid = $db->fetch_field($query, "pid");
		$posthandler->data['uid'] = $posthandler->data['edit_uid'];
	}
	else
	{
		$thread = get_thread($posthandler->data['tid']);
		$thread_tid = $posthandler->data['tid'];
		$thread_uid = $thread['uid'];
	}

	// the user submitting this data is the author of the thread
	// and they're either making a new reply
	// or they're editing the last post in the thread, which is theirs
	// take the thread off hold, as they've made an update
	if($posthandler->data['uid'] == $thread_uid && ($posthandler->method == "insert" || ($posthandler->method == "update" && $posthandler->data['pid'] == $pid)))
	{
		$update = array(
			"onhold" => 0
		);
	}
	else
	{
		$update = array(
			"onhold" => 1
		);
	}

	$db->update_query("threads", $update, "tid = '".intval($thread_tid)."'");
}

function fetch_wol_activity_end(&$user_activity)
{
	global $user;

	if(my_strpos($user['location'], "modcp.php?action=technicalthreads") !== false)
	{
		$user_activity['activity'] = "modcp_techthreads";
	}
	elseif(my_strpos($user['location'], "usercp.php?action=supportthreads") !== false)
	{
		$user_activity['activity'] = "usercp_supportthreads";
	}
	elseif(my_strpos($user['location'], "modcp.php?action=supportdenial") !== false)
	{
		if(my_strpos($user['location'], "do=denysupport") !== false || my_strpos($user['location'], "do=do_denysupport") !== false)
		{
			$user_activity['activity'] = "modcp_supportdenial_deny";
		}
		else
		{
			$user_activity['activity'] = "modcp_supportdenial";
		}
	}
}

// generate CSS classes for the priorities and select the categories, and load inline thread moderation
function forumdisplay_start()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $db, $cache, $foruminfo, $priorities, $mysupport_priority_classes;

	// basically it's much easier (and neater) to generate makeshift classes for priorities for highlighting threads than adding inline styles
	$mysupport_cache = $cache->read("mysupport");
	if(!empty($mysupport_cache['priorities']))
	{
		// build an array of all the priorities
		$priorities = array();
		// start the CSS classes
		$mysupport_priority_classes = "";
		$mysupport_priority_classes .= "\n<style type=\"text/css\">\n";
		foreach($mysupport_cache['priorities'] as $priority)
		{
			// add the name to the array, then we can get the relevant name for each priority when looping through the threads
			$priorities[$priority['mid']] = strtolower(htmlspecialchars_uni($priority['name']));
			// add the CSS class
			if(!empty($priority['extra']))
			{
				$mysupport_priority_classes .= ".mysupport_priority_".strtolower(htmlspecialchars_uni(str_replace(" ", "_", $priority['name'])))." {\n";
				$mysupport_priority_classes .= "\tbackground: #".htmlspecialchars_uni($priority['extra']).";\n";
				$mysupport_priority_classes .= "}\n";
			}
		}
		$mysupport_priority_classes .= "</style>\n";
	}

	$mysupport_forums = mysupport_forums();
	// if we're viewing a forum which has MySupport enabled, or we're viewing search results and there's at least 1 MySupport forum, show the MySupport options in the inline moderation menu
	if((THIS_SCRIPT == "forumdisplay.php" && mysupport_forum($mybb->input['fid'])) || (THIS_SCRIPT == "search.php" && !empty($mysupport_forums)))
	{
		mysupport_inline_thread_moderation();
	}
}

function search_results_start()
{
	forumdisplay_start();
}

// show the status of a thread for each thread on the forum display or a list of search results
function forumdisplay_thread()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $db, $lang, $templates, $theme, $foruminfo, $thread, $is_mysupport_forum, $mysupport_status, $mysupport_assigned, $mysupport_bestanswer, $priorities, $priority_class, $inline_mod_checkbox;

	// need to reset these outside of the the check for if it's a MySupport forum, otherwise they don't get unset in search results where the forum of the next thread may not be a MySupport forum
	$mysupport_status = "";
	$priority_class = "";
	$mysupport_assigned = "";
	$mysupport_bestanswer = "";

	if($thread['issupportthread'] != 1 || strpos($thread['closed'], "moved") !== false)
	{
		return;
	}

	// this function is called for the thread list on the forum display and the list of threads for search results, however the source of the fid is different
	// if this is the forum display, get it from the info on the forum we're in
	if(THIS_SCRIPT == "forumdisplay.php")
	{
		$fid = $foruminfo['fid'];
	}
	// if this is a list of search results, get it from the array of info about the thread we're looking at
	// this means that out of all the results, only threads in MySupport forums will show this information
	elseif(THIS_SCRIPT == "search.php")
	{
		$fid = $thread['fid'];
	}

	if(mysupport_forum($fid))
	{
		if($thread['priority'] != 0 && $thread['visible'] == 1)
		{
			$priority_class = " mysupport_priority_".htmlspecialchars_uni(str_replace(" ", "_", $priorities[$thread['priority']]));
		}
		if(THIS_SCRIPT == "search.php")
		{
			$inline_mod_checkbox = str_replace("{priority_class}", $priority_class, $inline_mod_checkbox);
		}

		// the only thing we might want to do with sticky threads is to give them a priority, to highlight them; they're not going to have a status or be assigned to anybody
		// after we've done the priority, we can exit
		if($thread['sticky'] == 1)
		{
			return;
		}

		$mysupport_status = mysupport_get_display_status($thread['status'], $thread['onhold'], $thread['statustime'], $thread['uid']);

		if($thread['assign'] != 0)
		{
			if($thread['assign'] == $mybb->user['uid'])
			{
				$mysupport_assigned = eval($templates->render('mysupport_assigned_toyou'));
			}
			else
			{
				$mysupport_assigned = eval($templates->render('mysupport_assigned'));
			}
		}

		if($mybb->settings['mysupport_enablebestanswer'])
		{
			if($thread['bestanswer'] != 0)
			{
				$post = intval($thread['bestanswer']);
				$jumpto_bestanswer_url = get_post_link($post, $tid)."#pid".$post;
				$bestanswer_image = "mysupport_bestanswer.gif";
				$mysupport_bestanswer = eval($templates->render('mysupport_jumpto_bestanswer'));
			}
		}
	}
	else
	{
		$inline_mod_checkbox = str_replace("{priority_class}", "", $inline_mod_checkbox);
	}
}

function search_results_thread()
{
	forumdisplay_thread();
}

// show a notice for technical and/or assigned threads
function global_intermediate()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $db, $cache, $lang, $theme, $templates, $forum, $thread, $mysupport_tech_notice, $mysupport_assign_notice;

	$lang->load("mysupport");

	// this function does both the technical threads alert and the assigned threads alert
	// both similar enough to keep in one function but different enough to be separated into two chunks

	// some code that's used in both, work out now

	// check for THIS_SCRIPT so it doesn't execute if we're viewing the technical threads list in the MCP or support threads in the UCP with an FID
	if(($mybb->input['fid'] || $mybb->input['tid']) && THIS_SCRIPT != "modcp.php" && THIS_SCRIPT != "usercp.php")
	{
		if($mybb->input['fid'])
		{
			$fid = intval($mybb->input['fid']);
		}
		else
		{
			$tid = intval($mybb->input['tid']);
			$thread_info = get_thread($tid);
			$fid = $thread_info['fid'];
		}
	}
	else
	{
		$fid = "";
	}

	// the technical threads notice
	$mysupport_tech_notice = "";
	// is it enabled?
	if($mybb->settings['mysupport_enabletechnical'] && $mybb->settings['mysupport_technicalnotice'] != "off")
	{
		// this user is in an allowed usergroup?
		if(mysupport_usergroup("canseetechnotice"))
		{
			// the notice is showing on all pages
			if($mybb->settings['mysupport_technicalnotice'] == "global")
			{
				// count for the entire forum
				$technical_count_global = mysupport_get_count("technical");
			}

			// if the notice is enabled, it'll at least show in the forums containing technical threads
			if(!empty($fid))
			{
				// count for the forum we're in now
				$technical_count_forum = mysupport_get_count("technical", $fid);
			}

			$notice_url = "modcp.php?action=technicalthreads";

			if($technical_count_forum > 0)
			{
				$notice_url .= "&amp;fid=".$fid;
			}

			// now to show the notice itself
			// it's showing globally
			if($mybb->settings['mysupport_technicalnotice'] == "global")
			{
				if($technical_count_global == 1)
				{
					$threads_text = $lang->mysupport_thread;
				}
				else
				{
					$threads_text = $lang->mysupport_threads;
				}

				// we're in a forum/thread, and the count for this forum, generated above, is more than 0, show the global count and forum count
				if(!empty($fid) && $technical_count_forum > 0)
				{
					$notice_text = $lang->sprintf($lang->technical_global_forum, intval($technical_count_global), $threads_text, intval($technical_count_forum));
				}
				// either there's no forum/thread, or there is but there's no tech threads in this forum, just show the global count
				else
				{
					$notice_text = $lang->sprintf($lang->technical_global, intval($technical_count_global), $threads_text);
				}

				if($technical_count_global > 0)
				{
					$mysupport_tech_notice = eval($templates->render('mysupport_notice'));
				}
			}
			// it's only showing in the relevant forums, if necessary
			elseif($mybb->settings['mysupport_technicalnotice'] == "specific")
			{
				if($technical_count_forum == 1)
				{
					$threads_text = $lang->mysupport_thread;
				}
				else
				{
					$threads_text = $lang->mysupport_threads;
				}

				// we're inside a forum/thread and the count for this forum, generated above, is more than 0, show the forum count
				if(!empty($fid) && $technical_count_forum > 0)
				{
					$notice_text = $lang->sprintf($lang->technical_forum, intval($technical_count_forum), $threads_text);
					$mysupport_tech_notice = eval($templates->render('mysupport_notice'));
				}
			}
		}
	}

	if($mybb->settings['mysupport_enableassign'])
	{
		// this user is in an allowed usergroup?
		if(mysupport_usergroup("canbeassigned"))
		{
			$assigned = mysupport_get_count("assigned");
			if($assigned > 0)
			{
				if($assigned == 1)
				{
					$threads_text = $lang->mysupport_thread;
				}
				else
				{
					$threads_text = $lang->mysupport_threads;
				}

				$notice_url = "usercp.php?action=assignedthreads";

				if(!empty($fid))
				{
					$assigned_forum = mysupport_get_count("assigned", $fid);
				}
				if($assigned_forum > 0)
				{
					$notice_text = $lang->sprintf($lang->assign_forum, intval($assigned), $threads_text, intval($assigned_forum));
					$notice_url .= "&amp;fid=".$fid;
				}
				else
				{
					$notice_text = $lang->sprintf($lang->assign_global, intval($assigned), $threads_text);
				}

				$mysupport_assign_notice = eval($templates->render('mysupport_notice'));
			}
		}
	}
}

// show MySupport information on a user's profile
function member_profile_end()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $db, $cache, $lang, $templates, $theme, $memprofile, $mysupport_info;

	$lang->load("mysupport");

	$something_to_show = false;

	if($mybb->settings['mysupport_enablebestanswer'])
	{
		$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));
		$query = $db->write_query("
			SELECT COUNT(*) AS bestanswers
			FROM ".TABLE_PREFIX."threads t
			LEFT JOIN ".TABLE_PREFIX."posts p
			ON (t.bestanswer = p.pid)
			WHERE t.fid IN (".$db->escape_string($mysupport_forums).")
			AND p.uid = '".intval($memprofile['uid'])."'
		");
		$bestanswers = $db->fetch_field($query, "bestanswers");
		$bestanswers = "<tr><td class=\"trow1\" width=\"50%\"><strong>".$lang->best_answers_given."</strong></td><td class=\"trow1\" width=\"50%\">".$bestanswers."</td></tr>";
		$something_to_show = true;
	}

	if($mybb->settings['mysupport_enablesupportdenial'])
	{
		if($memprofile['deniedsupport'] == 1)
		{
			$denied_text = $lang->denied_support_profile;
			if(mysupport_usergroup("canmanagesupportdenial"))
			{
				$mysupport_cache = $cache->read("mysupport");
				if(array_key_exists($memprofile['deniedsupportreason'], $mysupport_cache['deniedreasons']))
				{
					$deniedsupportreason = $mysupport_cache['deniedreasons'][$memprofile['deniedsupportreason']]['name'];
					$denied_text .= " ".$lang->sprintf($lang->deniedsupport_reason, htmlspecialchars_uni($deniedsupportreason));
				}
				$denied_text = "<a href=\"{$mybb->settings['bburl']}/modcp.php?action=supportdenial&do=denysupport&uid=".$memprofile['uid']."\">".$denied_text."</a>";
			}
			$denied_text = "<tr><td colspan=\"2\" class=\"trow2\">".$denied_text."</td></tr>";
			$something_to_show = true;
		}
	}

	if($something_to_show)
	{
		$mysupport_info = eval($templates->render('mysupport_member_profile'));
	}
}

function modcp_start()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $db, $cache, $lang, $theme, $templates, $headerinclude, $header, $footer, $modcp_nav, $mod_log_action, $redirect;

	$lang->load("mysupport");

	if($mybb->input['action'] == "supportdenial")
	{
		if(!mysupport_usergroup("canmanagesupportdenial"))
		{
			error_no_permission();
		}

		add_breadcrumb($lang->nav_modcp, "modcp.php");
		add_breadcrumb($lang->support_denial, "modcp.php?action=supportdenial");

		if($mybb->input['do'] == "do_denysupport")
		{
			verify_post_check($mybb->input['my_post_key']);

			if($mybb->settings['mysupport_enablesupportdenial'])
			{
				mysupport_error($lang->support_denial_not_enabled);
				exit;
			}

			// get username from UID
			// this is if we're revoking via the list of denied users, we specify a UID here
			if($mybb->input['uid'])
			{
				$uid = intval($mybb->input['uid']);
				$user = get_user($uid);
				$username = $user['username'];
			}
			// get UID from username
			// this is if we're denying support via the form, where we give a username
			elseif($mybb->input['username'])
			{
				$username = $db->escape_string($mybb->input['username']);
				$query = $db->simple_select("users", "uid", "username = '{$username}'");
				$uid = $db->fetch_field($query, "uid");
			}
			if(!$uid || !$username)
			{
				mysupport_error($lang->support_denial_reason_invalid_user);
				exit;
			}

			if(isset($mybb->input['deniedsupportreason']))
			{
				$deniedsupportreason = intval($mybb->input['deniedsupportreason']);
			}
			else
			{
				$deniedsupportreason = 0;
			}

			if($mybb->input['tid'] != 0)
			{
				$tid = intval($mybb->input['tid']);
				$thread_info = get_thread($tid);
				$fid = $thread_info['fid'];

				$redirect_url = get_thread_link($tid);
			}
			else
			{
				$redirect_url = "modcp.php?action=supportdenial";
			}

			$mod_log_action = "";
			$redirect = "";

			$mysupport_cache = $cache->read("mysupport");
			// -1 is if we're revoking and 0 is no reason, so those are exempt
			if(!array_key_exists($deniedsupportreason, $mysupport_cache['deniedreasons']) && $deniedsupportreason != -1 && $deniedsupportreason != 0)
			{
				mysupport_error($lang->support_denial_reason_invalid_reason);
				exit;
			}
			elseif($deniedsupportreason == -1)
			{
				$update = array(
					"deniedsupport" => 0,
					"deniedsupportreason" => 0,
					"deniedsupportuid" => 0
				);
				$db->update_query("users", $update, "uid = '".intval($uid)."'");

				$update = array(
					"closed" => 0,
					"closedbymysupport" => 0
				);
				$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));
				$db->update_query("threads", $update, "uid = '".intval($uid)."' AND fid IN (".$db->escape_string($mysupport_forums).") AND closed = '1' AND closedbymysupport = '2'");

				mysupport_mod_log_action(11, $lang->sprintf($lang->deny_support_revoke_mod_log, $username));
				mysupport_redirect_message($lang->sprintf($lang->deny_support_revoke_success, htmlspecialchars_uni($username)));
			}
			else
			{
				$update = array(
					"deniedsupport" => 1,
					"deniedsupportreason" => intval($deniedsupportreason),
					"deniedsupportuid" => intval($mybb->user['uid'])
				);
				$db->update_query("users", $update, "uid = '".intval($uid)."'");

				if($mybb->settings['mysupport_closewhendenied'])
				{
					$update = array(
						"closed" => 1,
						"closedbymysupport" => 2
					);
					$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));

					$db->update_query("threads", $update, "uid = '".intval($uid)."' AND fid IN (".$db->escape_string($mysupport_forums).") AND closed = '0'");
				}

				if($deniedsupportreason != 0)
				{
					$deniedsupportreason = $db->fetch_field($query, "name");
					mysupport_mod_log_action(11, $lang->sprintf($lang->deny_support_mod_log_reason, $username, $deniedsupportreason));
				}
				else
				{
					mysupport_mod_log_action(11, $lang->sprintf($lang->deny_support_mod_log, $username));
				}
				mysupport_redirect_message($lang->sprintf($lang->deny_support_success, htmlspecialchars_uni($username)));
			}
			if(!empty($mod_log_action))
			{
				$mod_log_data = array(
					"fid" => intval($fid),
					"tid" => intval($tid)
				);
				log_moderator_action($mod_log_data, $mod_log_action);
			}
			redirect($redirect_url, $redirect);
		}
		elseif($mybb->input['do'] == "denysupport")
		{
			if($mybb->settings['mysupport_enablesupportdenial'])
			{
				mysupport_error($lang->support_denial_not_enabled);
				exit;
			}

			$uid = intval($mybb->input['uid']);
			$tid = intval($mybb->input['tid']);

			$user = get_user($uid);
			$username = $user['username'];
			$user_link = build_profile_link(htmlspecialchars_uni($username), intval($uid), "blank");

			if($mybb->input['uid'])
			{
				$deny_support_to = $lang->sprintf($lang->deny_support_to, htmlspecialchars_uni($username));
			}
			else
			{
				$deny_support_to = $lang->deny_support_to_user;
			}

			add_breadcrumb($deny_support_to);

			$deniedreasons = "";
			$deniedreasons .= "<label for=\"deniedsupportreason\">{$lang->reason}:</label> <select name=\"deniedsupportreason\" id=\"deniedsupportreason\">\n";
			// if they've not been denied support yet or no reason was given, show an empty option that will be selected
			if($user['deniedsupport'] == 0 || $user['deniedsupportreason'] == 0)
			{
				$deniedreasons .= "<option value=\"0\"></option>\n";
			}

			$mysupport_cache = $cache->read("mysupport");
			if(!empty($mysupport_cache['deniedreasons']))
			{
				// if there's one or more reasons set, show them in a dropdown
				foreach($mysupport_cache['deniedreasons'] as $deniedreasons)
				{
					$selected = "";
					// if a reason has been given, we'd be editing it, so this would select the current one
					if($user['deniedsupport'] == 1 && $user['deniedsupportreason'] == $deniedreason['mid'])
					{
						$selected = " selected=\"selected\"";
					}
					$deniedreasons .= "<option value=\"".intval($deniedreason['mid'])."\"{$selected}>".htmlspecialchars_uni($deniedreason['name'])."</option>\n";
				}
			}
			$deniedreasons .= "<option value=\"0\">{$lang->support_denial_reasons_none}</option>\n";
			// if they've been denied support, give an option to revoke it
			if($user['deniedsupport'] == 1)
			{
				$deniedreasons .= "<option value=\"0\">-----</option>\n";
				$deniedreasons .= "<option value=\"-1\">{$lang->revoke}</option>\n";
			}
			$deniedreasons .= "</select>\n";

			$deny_support = eval($templates->render('mysupport_deny_support_deny'));
			$deny_support_page = eval($templates->render('mysupport_deny_support'));
			output_page($deny_support_page);
		}
		else
		{
			$url = 'modcp.php?action=supportdenial';

			$limit = (int)$mybb->settings['threadsperpage'];
			if($mybb->get_input('limit', 1))
			{
				$limit = $mybb->get_input('limit', 1);
				$url .= '&amp;limit='.$limit;
			}
			$limit = $limit > 100 ? 100 : ($limit < 1 ? 1 : $limit);

			$query = $db->simple_select('users', 'COUNT(uid) AS users', 'deniedsupport=1');
			$userscount = $db->fetch_field($query, 'users');

			if($mybb->get_input('page', 1) > 0)
			{
				$start = ($mybb->get_input('page', 1)-1)*$limit;
				$pages = ceil($userscount/$limit);
				if($mybb->get_input('page', 1) > $pages)
				{
					$start = 0;
					$mybb->input['page'] = 1;
				}
			}
			else
			{
				$start = 0;
				$mybb->input['page'] = 1;
			}

			$query = $db->write_query("
				SELECT u1.username AS support_denied_username, u1.uid AS support_denied_uid, u2.username AS support_denier_username, u2.uid AS support_denier_uid, m.name AS support_denied_reason
				FROM ".TABLE_PREFIX."users u
				LEFT JOIN ".TABLE_PREFIX."mysupport m ON (u.deniedsupportreason = m.mid)
				LEFT JOIN ".TABLE_PREFIX."users u1 ON (u1.uid = u.uid)
				LEFT JOIN ".TABLE_PREFIX."users u2 ON (u2.uid = u.deniedsupportuid)
				WHERE u.deniedsupport = '1'
				ORDER BY u1.username ASC
				LIMIT {$start}, {$limit}
			");

			$multipage = (string)multipage($userscount, $limit, $mybb->get_input('page', 1), $url);

			if($db->num_rows($query) > 0)
			{
				while($denieduser = $db->fetch_array($query))
				{
					$bgcolor = alt_trow();

					$support_denied_user = build_profile_link(htmlspecialchars_uni($denieduser['support_denied_username']), intval($denieduser['support_denied_uid']));
					$support_denier_user = build_profile_link(htmlspecialchars_uni($denieduser['support_denier_username']), intval($denieduser['support_denier_uid']));
					if(empty($denieduser['support_denied_reason']))
					{
						$support_denial_reason = $lang->support_denial_no_reason;
					}
					else
					{
						$support_denial_reason = $denieduser['support_denied_reason'];
					}
					$denied_users .= eval($templates->render('mysupport_deny_support_list_user'));
				}
			}
			else
			{
				$denied_users = "<tr><td class=\"trow1\" align=\"center\" colspan=\"5\">{$lang->support_denial_no_users}</td></tr>";
			}

			$deny_support = eval($templates->render('mysupport_deny_support_list'));
			$deny_support_page = eval($templates->render('mysupport_deny_support'));
			output_page($deny_support_page);
		}
	}
}

function modcp_start09()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		global $usercpnav, $modcp_nav;

		// if MySupport is turned off, we need to replace these with nothing otherwise they'll show up in the menu
		$modcp_nav = str_replace("{mysupport_nav_option}", "", $modcp_nav);
		$usercpnav = str_replace("{mysupport_nav_option}", "", $usercpnav);

		return;
	}

	global $lang, $templates, $usercpnav, $modcp_nav, $mysupport_nav_option;

	$lang->load("mysupport");

	if(THIS_SCRIPT == "modcp.php")
	{
		$mysupport_nav_option = "";
		$something_to_show = false;
		// is the technical threads feature enabled?
		if($mybb->settings['mysupport_enabletechnical'])
		{
			$class1 = "modcp_nav_item";
			$class2 = "modcp_nav_tech_threads";
			$nav_link = "modcp.php?action=technicalthreads";
			$nav_text = $lang->thread_list_title_tech;
			// we need to eval this template now to generate the nav row with the correct details in it
			$mysupport_nav_option .= eval($templates->render('mysupport_nav_option'));
			$something_to_show = true;
		}
		// is support denial enabled?
		if($mybb->settings['mysupport_enablesupportdenial'])
		{
			$class1 = "modcp_nav_item";
			$class2 = "modcp_nav_deny_support";
			$nav_link = "modcp.php?action=supportdenial";
			$nav_text = $lang->support_denial;
			// we need to eval this template now to generate the nav row with the correct details in it
			$mysupport_nav_option .= eval($templates->render('mysupport_nav_option'));
			$something_to_show = true;
		}

		if($something_to_show)
		{
			// do a str_replace on the nav to display it; need to do a string replace as the hook we're using here is after $modcp_nav has been eval'd
			$modcp_nav = str_replace("{mysupport_nav_option}", $mysupport_nav_option, $modcp_nav);
		}
		else
		{
			// if the technical threads or support denial feature isn't enabled, replace the code in the template with nothing
			$modcp_nav = str_replace("{mysupport_nav_option}", "", $modcp_nav);
		}
	}
	// need to check for private.php too so it shows in the PM system - the usercp_menu_built hook is run after $mysupport_nav_option has been made so this will work for both
	elseif(THIS_SCRIPT == "usercp.php" || THIS_SCRIPT == "usercp2.php" || THIS_SCRIPT == "private.php")
	{
		$mysupport_nav_option = "";
		$something_to_show = false;
		// is the list of support threads enabled?
		if($mybb->settings['mysupport_threadlist'])
		{
			$class1 = "usercp_nav_item";
			$class2 = "usercp_nav_support_threads";
			$nav_link = "usercp.php?action=supportthreads";
			$nav_text = $lang->thread_list_title_solved;
			// add to the code for the option
			$mysupport_nav_option .= eval($templates->render('mysupport_nav_option'));
			$something_to_show = true;
		}
		// is assigning threads enabled?
		if($mybb->settings['mysupport_enableassign'] && mysupport_usergroup("canbeassigned"))
		{
			$class1 = "usercp_nav_item";
			$class2 = "usercp_nav_assigned_threads";
			$nav_link = "usercp.php?action=assignedthreads";
			$nav_text = $lang->thread_list_title_assign;
			// add to the code for the option
			$mysupport_nav_option .= eval($templates->render('mysupport_nav_option'));
			$something_to_show = true;
		}

		if($something_to_show)
		{
			// if we added either or both of the nav options above, do a str_replace on the nav to display it
			// need to do a string replace as the hook we're using here is after $usercpnav has been eval'd
			$usercpnav = str_replace("{mysupport_nav_option}", $mysupport_nav_option, $usercpnav);
		}
		else
		{
			// if we didn't add either of the nav options above, replace the code in the template with nothing
			$usercpnav = str_replace("{mysupport_nav_option}", "", $usercpnav);
		}
	}
}

function usercp_menu_built09()
{
	modcp_start09();
}

// show a list of threads requiring technical attention, assigned threads, or support threads
function modcp_start20()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $db, $cache, $lang, $theme, $templates, $forum, $headerinclude, $header, $footer, $usercpnav, $modcp_nav, $threads_list, $priorities, $mysupport_priority_classes;

	$lang->load("mysupport");

	// checks if we're in the Mod CP, technical threads are enabled, and we're viewing the technical threads list...
	// ... or we're in the User CP, the ability to view a list of support threads is enabled, and we're viewing that list
	if((THIS_SCRIPT == "modcp.php" && $mybb->settings['mysupport_enabletechnical'] && $mybb->input['action'] == "technicalthreads") || (THIS_SCRIPT == "usercp.php" && (($mybb->settings['mysupport_threadlist'] && ($mybb->input['action'] == "supportthreads" || !$mybb->input['action'])) || ($mybb->settings['mysupport_enableassign'] && $mybb->input['action'] == "assignedthreads"))))
	{
		// add to navigation
		if(THIS_SCRIPT == "modcp.php")
		{
			add_breadcrumb($lang->nav_modcp, "modcp.php");
			add_breadcrumb($lang->thread_list_title_tech, "modcp.php?action=technicalthreads");
		}
		elseif(THIS_SCRIPT == "usercp.php")
		{
			add_breadcrumb($lang->nav_usercp, "usercp.php");
			if($mybb->input['action'] == "assignedthreads")
			{
				add_breadcrumb($lang->thread_list_title_assign, "usercp.php?action=assignedthreads");
			}
			elseif($mybb->input['action'] == "supportthreads")
			{
				add_breadcrumb($lang->thread_list_title_solved, "usercp.php?action=supportthreads");
			}
		}

		// load the priorities and generate the CSS classes
		mysupport_forumdisplay_searchresults();

		// if we have a forum in the URL, we're only dealing with threads in that forum
		// set some stuff for this forum that will be used in various places in this function
		if($mybb->input['fid'])
		{
			// https://github.com/JordanMussi/MySupport/commit/7c9dcbb0143d89fe61e3e553c2fc5997f7f0f6f1
			$fid = intval($mybb->input['fid']);
			$forumpermissions = forum_permissions();
			$fpermissions = $forumpermissions[$fid];

			if($fpermissions['canview'] != 1)
			{
				error_no_permission();
			}

			$forum_info = get_forum(intval($mybb->input['fid']));
			$list_where_sql = " AND t.fid = ".intval($mybb->input['fid']);
			$stats_where_sql = " AND fid = ".intval($mybb->input['fid']);
			// if we're viewing threads from a specific forum, add that to the nav too
			if(THIS_SCRIPT == "modcp.php")
			{
				add_breadcrumb($lang->sprintf($lang->thread_list_heading_tech_forum, htmlspecialchars_uni($forum_info['name'])), "modcp.php?action=technicalthreads&fid={$fid}");
			}
			elseif(THIS_SCRIPT == "usercp.php")
			{
				if($mybb->input['action'] == "assignedthreads")
				{
					add_breadcrumb($lang->sprintf($lang->thread_list_heading_assign_forum, htmlspecialchars_uni($forum_info['name'])), "usercp.php?action=supportthreads&fid={$fid}");
				}
				elseif($mybb->input['action'] == "supportthreads")
				{
					add_breadcrumb($lang->sprintf($lang->thread_list_heading_solved_forum, htmlspecialchars_uni($forum_info['name'])), "usercp.php?action=supportthreads&fid={$fid}");
				}
			}
		}
		else
		{
			$list_where_sql = "";
			$stats_where_sql = "";
		}

		// what forums is this allowed in?
		$mysupport_forums = mysupport_forums();
		$mysupport_forums = implode(",", array_map("intval", $mysupport_forums));
		// if this string isn't empty, generate a variable to go in the query
		if(!empty($mysupport_forums))
		{
			$list_in_sql = " AND t.fid IN (".$db->escape_string($mysupport_forums).")";
			$stats_in_sql = " AND fid IN (".$db->escape_string($mysupport_forums).")";
		}
		else
		{
			$list_in_sql = " AND t.fid IN (0)";
			$stats_in_sql = " AND fid IN (0)";
		}

		if($mybb->settings['mysupport_stats'])
		{
			// only want to do this if we're viewing the list of support threads or technical threads
			if((THIS_SCRIPT == "usercp.php" && $mybb->input['action'] == "supportthreads") || (THIS_SCRIPT == "modcp.php" && $mybb->input['action'] == "technicalthreads"))
			{
				// show a small stats section
				if(THIS_SCRIPT == "modcp.php")
				{
					$query = $db->simple_select("threads", "status", "1=1{$stats_in_sql}{$stats_where_sql}");
					// 1=1 here because both of these variables could start with AND, so if there's nothing before that, there'll be an SQL error
				}
				elseif(THIS_SCRIPT == "usercp.php")
				{
					$query = $db->simple_select("threads", "status", "uid = '{$mybb->user['uid']}'{$stats_in_sql}{$stats_where_sql}");
				}
				if($db->num_rows($query) > 0)
				{
					$total_count = $solved_count = $notsolved_count = $technical_count = 0;
					while($threads = $db->fetch_array($query))
					{
						switch($threads['status'])
						{
							case 2:
								// we have a technical thread, count it
								++$technical_count;
								break;
							case 1:
								// we have a solved thread, count it
								++$solved_count;
								break;
								// we have an unsolved thread, count it
							default:
								++$notsolved_count;
						}
						// count the total
						++$total_count;
					}
					// if the total count is 0, set all the percentages to 0
					// otherwise we'd get 'division by zero' errors as it would try to divide by zero, and dividing by zero would cause the universe to implode
					if($total_count == 0)
					{
						$solved_percentage = $notsolved_percentage = $technical_percentage = 0;
					}
					// work out the percentages, so we know how big to make each bar
					else
					{
						$solved_percentage = round(($solved_count / $total_count) * 100);
						if($solved_percentage > 0)
						{
							$solved_row = "<td class=\"mysupport_bar_solved\" width=\"{$solved_percentage}%\"></td>";
						}

						$notsolved_percentage = round(($notsolved_count / $total_count) * 100);
						if($notsolved_percentage > 0)
						{
							$notsolved_row = "<td class=\"mysupport_bar_notsolved\" width=\"{$notsolved_percentage}%\"></td>";
						}

						$technical_percentage = round(($technical_count / $total_count) * 100);
						if($technical_percentage > 0)
						{
							$technical_row = "<td class=\"mysupport_bar_technical\" width=\"{$technical_percentage}%\"></td>";
						}
					}

					// get the title for the stats table
					if(THIS_SCRIPT == "modcp.php")
					{
						if($mybb->input['fid'])
						{
							$title_text = $lang->sprintf($lang->thread_list_stats_overview_heading_tech_forum, htmlspecialchars_uni($forum_info['name']));
						}
						else
						{
							$title_text = $lang->thread_list_stats_overview_heading_tech;
						}
					}
					elseif(THIS_SCRIPT == "usercp.php")
					{
						if($mybb->input['fid'])
						{
							$title_text = $lang->sprintf($lang->thread_list_stats_overview_heading_solved_forum, htmlspecialchars_uni($forum_info['name']));
						}
						else
						{
							$title_text = $lang->thread_list_stats_overview_heading_solved;
						}
					}

					// fill out the counts of the statuses of threads
					$overview_text = $lang->sprintf($lang->thread_list_stats_overview, $total_count, $solved_count, $notsolved_count, $technical_count);

					if(THIS_SCRIPT == "usercp.php")
					{
						$query = $db->simple_select("threads", "COUNT(*) AS newthreads", "lastpost > '".intval($mybb->user['lastvisit'])."' OR statustime > '".intval($mybb->user['lastvisit'])."'");
						$newthreads = $db->fetch_field($query, "newthreads");
						// there's 'new' support threads (reply or action since last visit) so show a link to give a list of just those
						if($newthreads != 0)
						{
							$newthreads_text = $lang->sprintf($lang->thread_list_newthreads, intval($newthreads));
							$newthreads = "<tr><td class=\"trow1\" align=\"center\"><a href=\"{$mybb->settings['bburl']}/usercp.php?action=supportthreads&amp;do=new\">{$newthreads_text}</a></td></tr>";
						}
						else
						{
							$newthreads = "";
						}
					}

					$stats = eval($templates->render('mysupport_threadlist_stats'));
				}
			}
		}

		// now get the relevant threads
		// the query for if we're in the Mod CP, getting all technical threads
		if(THIS_SCRIPT == "modcp.php")
		{
			$query = $db->query("
				SELECT t.tid, t.subject, t.fid, t.uid, t.username, t.lastpost, t.lastposter, t.lastposteruid, t.status, t.statusuid, t.statustime, t.priority, f.name
				FROM ".TABLE_PREFIX."threads t
				INNER JOIN ".TABLE_PREFIX."forums f
				ON(t.fid = f.fid AND t.status = '2'{$list_in_sql}{$list_where_sql})
				ORDER BY t.lastpost DESC
			");
		}
		// the query for if we're in the User CP, getting all support threads
		elseif(THIS_SCRIPT == "usercp.php")
		{
			$list_limit_sql = "";
			if($mybb->input['action'] == "assignedthreads")
			{
				// viewing assigned threads
				$column = "t.assign";
			}
			elseif($mybb->input['action'] == "supportthreads")
			{
				// viewing support threads
				$column = "t.uid";
				$list_where_sql .= " AND t.visible = '1'";
				if($mybb->input['do'] == "new")
				{
					$list_where_sql .= " AND (t.lastpost > '".intval($mybb->user['lastvisit'])."' OR t.statustime > '".intval($mybb->user['lastvisit'])."')";
				}
			}
			else
			{
				$column = "t.uid";
				$list_where_sql .= " AND t.visible = '1'";
				$list_limit_sql = "LIMIT 0, 5";
			}
			$query = $db->query("
				SELECT t.tid, t.subject, t.fid, t.uid, t.username, t.lastpost, t.lastposter, t.lastposteruid, t.status, t.statusuid, t.statustime, t.assignuid, t.priority, f.name
				FROM ".TABLE_PREFIX."threads t
				INNER JOIN ".TABLE_PREFIX."forums f
				ON(t.fid = f.fid AND {$column} = '{$mybb->user['uid']}'{$list_in_sql}{$list_where_sql})
				ORDER BY t.lastpost DESC
				{$list_limit_sql}
			");
		}

		// sort out multipage
		if(!$mybb->settings['postsperpage'])
		{
			$mybb->settings['postperpage'] = 20;
		}
		$perpage = $mybb->settings['postsperpage'];
		if(intval($mybb->input['page']) > 0)
		{
			$page = intval($mybb->input['page']);
			$start = ($page-1) * $perpage;
			$pages = $threadcount / $perpage;
			$pages = ceil($pages);
			if($page > $pages || $page <= 0)
			{
				$start = 0;
				$page = 1;
			}
		}
		else
		{
			$start = 0;
			$page = 1;
		}
		$end = $start + $perpage;
		$lower = $start + 1;
		$upper = $end;
		if($upper > $threadcount)
		{
			$upper = $threadcount;
		}

		$threads = "";
		if($db->num_rows($query) == 0)
		{
			$threads = "<tr><td class=\"trow1\" colspan=\"4\" align=\"center\">{$lang->thread_list_no_results}</td></tr>";
		}
		else
		{
			while($thread = $db->fetch_array($query))
			{
				$bgcolor = alt_trow();
				$priority_class = "";
				if($thread['priority'] != 0)
				{
					$priority_class = " class=\"mysupport_priority_".strtolower(htmlspecialchars_uni(str_replace(" ", "_", $priorities[$thread['priority']])))."\"";
				}

				$thread['subject'] = htmlspecialchars_uni($thread['subject']);
				$thread['threadlink'] = get_thread_link($thread['tid']);
				$thread['forumlink'] = "<a href=\"".get_forum_link($thread['fid'])."\">".htmlspecialchars_uni($thread['name'])."</a>";
				$thread['profilelink'] = build_profile_link(htmlspecialchars_uni($thread['username']), intval($thread['uid']));

				$status_time_date = my_date($mybb->settings['dateformat'], intval($thread['statustime']));
				$status_time_time = my_date($mybb->settings['timeformat'], intval($thread['statustime']));
				// if we're in the Mod CP we only need the date and time it was marked technical, don't need the status on every line
				if(THIS_SCRIPT == "modcp.php")
				{
					if($mybb->settings['mysupport_relativetime'])
					{
						$status_time = mysupport_relative_time($thread['statustime']);
					}
					else
					{
						$status_time = $status_time_date." ".$status_time_time;
					}
					// we're viewing technical threads, show who marked it as technical
					$status_uid = intval($thread['statusuid']);
					$status_user = get_user($status_uid);
					$status_username = $status_user['username'];
					$status_user_link = build_profile_link(htmlspecialchars_uni($status_username), intval($status_uid));
					$status_time .= ", ".$lang->sprintf($lang->mysupport_by, $status_user_link);

					$view_all_forum_text = $lang->sprintf($lang->thread_list_link_tech, htmlspecialchars_uni($thread['name']));
					$view_all_forum_link = "modcp.php?action=technicalthreads&amp;fid=".intval($thread['fid']);
				}
				// if we're in the User CP we want to get the status...
				elseif(THIS_SCRIPT == "usercp.php")
				{
					$status = mysupport_get_friendly_status(intval($thread['status']));
					switch($thread['status'])
					{
						case 2:
							$class = "technical";
							break;
						case 1:
							$class = "solved";
							break;
						default:
							$class = "notsolved";
					}
					$status = "<span class=\"mysupport_status_{$class}\">".htmlspecialchars_uni($status)."</span>";
					// ... but we only want to show the time if the status is something other than Not Solved...
					if($thread['status'] != 0)
					{
						if($mybb->settings['mysupport_relativetime'])
						{
							$status_time = $status." - ".mysupport_relative_time($thread['statustime']);
						}
						else
						{
							$status_time = $status." - ".$status_time_date." ".$status_time_time;
						}
					}
					// ... otherwise, if it is not solved, just show that
					else
					{
						$status_time = $status;
					}
					//if(!($mybb->input['action'] == "supportthreads" && $thread['status'] == 0))
					// we wouldn't want to do this if a thread was unsolved
					if((($mybb->input['action'] == "supportthreads" || !$mybb->input['action']) && $thread['status'] != 0) || $mybb->input['action'] == "assignedthreads")
					{
						if($mybb->input['action'] == "supportthreads" || !$mybb->input['action'])
						{
							// we're viewing support threads, show who marked it as solved or technical
							$status_uid = intval($thread['statusuid']);
							$by_lang = "mysupport_by";
						}
						else
						{
							// we're viewing assigned threads, show who assigned this thread to you
							$status_uid = intval($thread['assignuid']);
							$by_lang = "mysupport_assigned_by";
						}
						if($status_uid)
						{
							$status_user = get_user($status_uid);
							$status_user_link = build_profile_link(htmlspecialchars_uni($status_user['username']), intval($status_uid));
							$status_time .= ", ".$lang->sprintf($lang->$by_lang, $status_user_link);
						}
					}

					if($mybb->input['action'] == "assignedthreads")
					{
						$view_all_forum_text = $lang->sprintf($lang->thread_list_link_assign, htmlspecialchars_uni($thread['name']));
						$view_all_forum_link = "usercp.php?action=assignedthreads&amp;fid=".intval($thread['fid']);
					}
					else
					{
						$view_all_forum_text = $lang->sprintf($lang->thread_list_link_solved, htmlspecialchars_uni($thread['name']));
						$view_all_forum_link = "usercp.php?action=supportthreads&amp;fid=".intval($thread['fid']);
					}
				}

				$thread['lastpostlink'] = get_thread_link($thread['tid'], 0, "lastpost");
				$lastpostdate = my_date($mybb->settings['dateformat'], intval($thread['lastpost']));
				$lastposttime = my_date($mybb->settings['timeformat'], intval($thread['lastpost']));
				$lastposterlink = build_profile_link(htmlspecialchars_uni($thread['lastposter']), intval($thread['lastposteruid']));

				$threads .= eval($templates->render('mysupport_threadlist_thread'));
			}
		}

		// if we have a forum in the URL, add a table footer with a link to all the threads
		if($mybb->input['fid'] || (THIS_SCRIPT == "usercp.php" && !$mybb->input['action']))
		{
			if(THIS_SCRIPT == "modcp.php")
			{
				$thread_list_heading = $lang->sprintf($lang->thread_list_heading_tech_forum, htmlspecialchars_uni($forum_info['name']));
				$view_all = $lang->thread_list_view_all_tech;
				$view_all_url = "modcp.php?action=technicalthreads";
			}
			elseif(THIS_SCRIPT == "usercp.php")
			{
				if($mybb->input['action'] == "assignedthreads")
				{
					$thread_list_heading = $lang->sprintf($lang->thread_list_heading_assign_forum, htmlspecialchars_uni($forum_info['name']));
					$view_all = $lang->thread_list_view_all_assign;
					$view_all_url = "usercp.php?action=assignedthreads";
				}
				else
				{
					if($mybb->input['action'] == "supportthreads")
					{
						$thread_list_heading = $lang->sprintf($lang->thread_list_heading_solved_forum, htmlspecialchars_uni($forum_info['name']));
					}
					elseif(!$mybb->input['action'])
					{
						$thread_list_heading = $lang->thread_list_heading_solved_latest;
					}
					$view_all = $lang->thread_list_view_all_solved;
					$view_all_url = "usercp.php?action=supportthreads";
				}
			}
			$view_all = eval($templates->render('mysupport_threadlist_footer'));
		}
		// if there's no forum in the URL, just get the standard table heading
		else
		{
			if(THIS_SCRIPT == "modcp.php")
			{
				$thread_list_heading = $lang->thread_list_heading_tech;
			}
			elseif(THIS_SCRIPT == "usercp.php")
			{
				if($mybb->input['action'] == "assignedthreads")
				{
					$thread_list_heading = $lang->thread_list_heading_assign;
				}
				else
				{
					if($mybb->input['do'] == "new")
					{
						$thread_list_heading = $lang->thread_list_heading_solved_new;
					}
					else
					{
						$thread_list_heading = $lang->thread_list_heading_solved;
					}
				}
			}
		}

		//get the page title, heading for the status of the thread column, and the relevant sidebar navigation
		if(THIS_SCRIPT == "modcp.php")
		{
			$thread_list_title = $lang->thread_list_title_tech;
			$status_heading = $lang->thread_list_time_tech;
			$navigation = "$modcp_nav";
		}
		elseif(THIS_SCRIPT == "usercp.php")
		{
			if($mybb->input['action'] == "assignedthreads")
			{
				$thread_list_title = $lang->thread_list_title_assign;
				$status_heading = $lang->thread_list_time_solved;
			}
			else
			{
				$thread_list_title = $lang->thread_list_title_solved;
				$status_heading = $lang->thread_list_time_assign;
			}
			$navigation = "$usercpnav";
		}

		$threadlist_filter_form = "";
		$threadlist_filter_form .= "<form action=\"".THIS_SCRIPT."?action=".$mybb->input['action']."\" method=\"get\">";
		$threadlist_filter_form .= $lang->filter_by;
		$threadlist_filter_form .= "<option value=\"0\">".$lang->status."</option>";
		$threadlist_filter_form .= "<option value=\"-1\">".$lang->not_solved."</option>";
		$threadlist_filter_form .= "<option value=\"1\">".$lang->solved."</option>";
		//if
		$threadlist_filter_form .= "</form>";

		$threads_list = eval($templates->render('mysupport_threadlist_list'));
		// we only want to output the page if we've got an action; i.e. we're not viewing the list on the User CP home page
		if($mybb->input['action'])
		{
			$threads_page = eval($templates->render('mysupport_threadlist'));
			output_page($threads_page);
		}
	}
}

function usercp_start()
{
	modcp_start20();
}

// perform inline thread moderation on multiple threads
function moderation_start()
{
	global $mybb;

	// we're hooking into the start of moderation.php, so if we're not submitting a MySupport action, exit now
	if(strpos($mybb->input['action'], "mysupport") === false)
	{
		return false;
	}

	verify_post_check($mybb->input['my_post_key']);

	global $db, $cache, $lang, $mod_log_action, $redirect;

	$lang->load("mysupport");

	$fid = intval($mybb->input['fid']);
	if(!is_moderator($fid, 'canmanagethreads'))
	{
		error_no_permission();
	}
	if($mybb->input['inlinetype'] == "search")
	{
		$type = "search";
		$id = $mybb->input['searchid'];
		$redirect_url = "search.php?action=results&sid=".rawurlencode($id);
	}
	else
	{
		$type = "forum";
		$id = $fid;
		$redirect_url = get_forum_link($fid);
	}
	$threads = getids($id, $type);
	if(count($threads) < 1)
	{
		mysupport_error($lang->error_inline_nothreadsselected);
		exit;
	}
	clearinline($id, $type);

	$tids = implode(",", array_map("intval", $threads));
	$mysupport_threads = array();
	// in a list of search results, you could see threads that aren't from a MySupport forum, but the MySupport options will always show in the inline moderation options regardless of this
	// this is a way of determining which of the selected threads from a list of search results are in a MySupport forum
	// this isn't necessary for inline moderation via the forum display, as the options only show in MySupport forums to begin with
	if($type == "search")
	{
		// list of MySupport forums
		$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));
		// query all the threads that are in the list of TIDs and where the FID is also in the list of MySupport forums and where the thread is set to be a support thread
		// this will knock out the non-MySupport threads
		$query = $db->simple_select("threads", "tid", "fid IN (".$db->escape_string($mysupport_forums).") AND tid IN (".$db->escape_string($tids).") AND issupportthread = '1'");
		while($tid = $db->fetch_field($query, "tid"))
		{
			$mysupport_threads[] = intval($tid);
		}
		$threads = $mysupport_threads;
		// if the new list of threads is empty, no MySupport threads have been selected
		if(count($threads) < 1)
		{
			mysupport_error($lang->no_mysupport_threads_selected);
			exit;
		}
	}
	// make sure we only have threads that are set to be support threads
	elseif($type == "forum")
	{
		$query = $db->simple_select("threads", "tid", "tid IN (".$db->escape_string($tids).") AND issupportthread = '1'");
		while($tid = $db->fetch_field($query, "tid"))
		{
			$mysupport_threads[] = intval($tid);
		}
		$threads = $mysupport_threads;
		// if the new list of threads is empty, no MySupport threads have been selected
		if(count($threads) < 1)
		{
			mysupport_error($lang->no_mysupport_threads_selected);
			exit;
		}
	}

	$mod_log_action = "";
	$redirect = "";

	if(strpos($mybb->input['action'], "status") !== false)
	{
		$status = str_replace("mysupport_status_", "", $mybb->input['action']);
		if($status == 2 || $status == 4)
		{
			$perm = "canmarktechnical";
		}
		else
		{
			$perm = "canmarksolved";
		}
		// they don't have permission to perform this action, so go through the different statuses and show an error for the right one
		if(!mysupport_usergroup($perm))
		{
			switch($status)
			{
				case 1:
					mysupport_error($lang->no_permission_mark_solved_multi);
					break;
				case 2:
					mysupport_error($lang->no_permission_mark_technical_multi);
					break;
				case 3:
					mysupport_error($lang->no_permission_mark_solved_close_multi);
					break;
				case 4:
					mysupport_error($lang->no_permission_mark_nottechnical_multi);
					break;
				default:
					mysupport_error($lang->no_permission_mark_notsolved_multi);
			}
		}

		mysupport_change_status($threads, $status, true);
	}
	if(strpos($mybb->input['action'], "onhold") !== false)
	{
		$hold = str_replace("mysupport_onhold_", "", $mybb->input['action']);

		if(!mysupport_usergroup("canmarksolved"))
		{
			mysupport_error($lang->no_permission_thread_hold_multi);
			exit;
		}

		mysupport_change_hold($threads, $hold, true);
	}
	elseif(strpos($mybb->input['action'], "assign") !== false)
	{
		if(!mysupport_usergroup("canassign"))
		{
			mysupport_error($lang->assign_no_perms);
			exit;
		}
		$assign = str_replace("mysupport_assign_", "", $mybb->input['action']);
		if($assign == 0)
		{
			// in the function to change the assigned user, -1 means removing; 0 is easier to put into the form than -1, so change it back here
			$assign = -1;
		}
		else
		{
			$assign_users = mysupport_get_assign_users();
			// -1 is what's used to unassign a thread so we need to exclude that
			if(!array_key_exists($assign, $assign_users))
			{
				mysupport_error($lang->assign_invalid);
				exit;
			}
		}

		mysupport_change_assign($threads, $assign, true);
	}
	elseif(strpos($mybb->input['action'], "priority") !== false)
	{
		if(!mysupport_usergroup("cansetpriorities"))
		{
			mysupport_error($lang->priority_no_perms);
			exit;
		}
		$priority = str_replace("mysupport_priority_", "", $mybb->input['action']);
		if($priority == 0)
		{
			// in the function to change the priority, -1 means removing; 0 is easier to put into the form than -1, so change it back here
			$priority = -1;
		}
		else
		{
			$mysupport_cache = $cache->read("mysupport");
			$mids = array();
			if(!empty($mysupport_cache['priorities']))
			{
				foreach($mysupport_cache['priorities'] as $priority_info)
				{
					$mids[] = intval($priority_info['mid']);
				}
			}
			if(!in_array($priority, $mids))
			{
				mysupport_error($lang->priority_invalid);
				exit;
			}
		}

		mysupport_change_priority($threads, $priority, true);
	}
	elseif(strpos($mybb->input['action'], "category") !== false)
	{
		$category = str_replace("mysupport_category_", "", $mybb->input['action']);
		if($category == 0)
		{
			// in the function to change the category, -1 means removing; 0 is easier to put into the form than -1, so change it back here
			$category = -1;
		}
		else
		{
			$categories = mysupport_get_categories($forum);
			if(!array_key_exists($category, $categories) && $category != "-1")
			{
				mysupport_error($lang->category_invalid);
				exit;
			}
		}

		mysupport_change_category($threads, $category, true);
	}
	$mod_log_data = array(
		"fid" => intval($fid)
	);
	log_moderator_action($mod_log_data, $mod_log_action);
	redirect($redirect_url, $redirect);
}

// show a message if someone is going to bump a thread that is solved and isn't their thread
function newreply_start()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $lang, $thread, $forum, $mysupport_solved_bump_message;

	if(mysupport_forum($forum['fid']))
	{
		if($mybb->settings['mysupport_bumpnotice'])
		{
			if($thread['status'] == 1 && $thread['uid'] != $mybb->user['uid'] && !(mysupport_usergroup("canmarksolved", $post_groups) || is_moderator($forum['fid'], "", $post['uid'])))
			{
				$mysupport_solved_bump_message = $lang->mysupport_solved_bump_message."\n\n";
			}
		}
	}
}

function showthread_start()
{
	newreply_start();
}

function newthread_do_newthread_end()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $db, $thread_info;

	if(mysupport_forum($thread_info['fid']))
	{
		if($mybb->settings['mysupport_enablenotsupportthread'] == 2)
		{
			$update = array(
				"issupportthread" => 0
			);
			$db->update_query("threads", $update, "tid = '".intval($thread_info['tid'])."'");
		}
	}
}

// check if a user is denied support when they're trying to make a new thread
function newthread_start()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $db, $cache, $lang, $forum;

	// this is a MySupport forum and this user has been denied support
	if(mysupport_forum($forum['fid']) && $forum['mysupportdenial'] == 1 && $mybb->user['deniedsupport'] == 1)
	{
		// start the standard error message to show
		$deniedsupport_message = $lang->deniedsupport;
		// if a reason has been set for this user
		if($mybb->user['deniedsupportreason'] != 0)
		{
			$query = $db->simple_select("mysupport", "name, description", "mid = '".intval($mybb->user['deniedsupportreason'])."'");
			$deniedsupportreason = $db->fetch_array($query);
			$deniedsupport_message .= "<br /><br />".$lang->sprintf($lang->deniedsupport_reason, htmlspecialchars_uni($deniedsupportreason['name']));
			if($deniedsupportreason['description'] != "")
			{
				$deniedsupport_message .= "<br />".$lang->sprintf($lang->deniedsupport_reason_extra, htmlspecialchars_uni($deniedsupportreason['description']));
			}
		}
		mysupport_error($deniedsupport_message);
		exit;
	}
}

// highlight the best answer from the thread and show the status of the thread in each post
function postbit(&$post)
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $db, $cache, $lang, $theme, $templates, $thread, $forum, $support_denial_reasons;

	$lang->load("mysupport");

	if(mysupport_forum($forum['fid']))
	{
		$post['mysupport_bestanswer'] = "";
		$post['mysupport_bestanswer_highlight'] = "";
		$post['mysupport_staff_highlight'] = "";
		if($post['visible'] == 1)
		{
			if($mybb->settings['mysupport_enablebestanswer'])
			{
				if($thread['bestanswer'] == $post['pid'])
				{
					$post['mysupport_bestanswer_highlight'] = " mysupport_bestanswer_highlight";
				}

				if($mybb->user['uid'] == $thread['uid'])
				{
					if($thread['bestanswer'] == $post['pid'])
					{
						$bestanswer_img = "mysupport_bestanswer";
						$bestanswer_alt = $lang->unbestanswer_img_alt;
						$bestanswer_title = $lang->unbestanswer_img_title;
						$bestanswer_desc = $lang->unbestanswer_img_alt;
					}
					else
					{
						$bestanswer_img = "mysupport_unbestanswer";
						$bestanswer_alt = $lang->bestanswer_img_alt;
						$bestanswer_title = $lang->bestanswer_img_title;
						$bestanswer_desc = $lang->bestanswer_img_alt;
					}

					$post['mysupport_bestanswer'] = eval($templates->render('mysupport_bestanswer'));
				}
			}

			// we only want to do this if it's not been highlighted as the best answer; that takes priority over this
			if(empty($post['mysupport_bestanswer_highlight']))
			{
				if($mybb->settings['mysupport_highlightstaffposts'])
				{
					$post_groups = array_merge(array($post['usergroup']), explode(",", $post['additionalgroups']));
					// various checks to see if they should be considered staff or not
					if(mysupport_usergroup("canmarksolved", $post_groups) || is_moderator($forum['fid'], "", $post['uid']))
					{
						$post['mysupport_staff_highlight'] = " mysupport_staff_highlight";
					}
				}
			}
		}

		if($mybb->settings['mysupport_enablesupportdenial'] && $forum['mysupportdenial'])
		{
			$post['mysupport_deny_support_post'] = "";
			$denied_text = $denied_text_desc = "";

			if($post['deniedsupport'] == 1)
			{
				$denied_text = $lang->denied_support;
				if(mysupport_usergroup("canmanagesupportdenial"))
				{
					$denied_text_desc = $lang->sprintf($lang->revoke_from, htmlspecialchars_uni($post['username']));
					if(array_key_exists($post['deniedsupportreason'], $support_denial_reasons))
					{
						$denied_text .= ": ".htmlspecialchars_uni($support_denial_reasons[$post['deniedsupportreason']]);
					}
					$denied_text .= " ".$lang->denied_support_click_to_edit_revoke;
					$post['mysupport_deny_support_post'] = eval($templates->render('mysupport_deny_support_post_linked'));
				}
				else
				{
					$denied_text_desc = $lang->denied_support;
					$post['mysupport_deny_support_post'] = eval($templates->render('mysupport_deny_support_post'));
				}
			}
			else
			{
				if(mysupport_usergroup("canmanagesupportdenial"))
				{
					$post_groups = array_merge(array($post['usergroup']), explode(",", $post['additionalgroups']));
					// various checks to see if they should be considered staff or not - if they are, don't show this for this user
					if(!(mysupport_usergroup("canmarksolved", $post_groups) || is_moderator($forum['fid'], "", $post['uid'])))
					{
						$denied_text_desc = $lang->sprintf($lang->deny_support_to, htmlspecialchars_uni($post['username']));
						$post['mysupport_deny_support_post'] = eval($templates->render('mysupport_deny_support_post_linked'));
					}
				}
			}
		}

		if($thread['issupportthread'] == 1 && $thread['firstpost'] == $post['pid'])
		{
			$post['mysupport_status'] = mysupport_get_display_status($thread['status'], $thread['onhold'], $thread['statustime'], $thread['uid']);
		}
	}
}

// show the form in the thread to change the status of the thread
function showthread_start20()
{
	global $mybb, $mysupport;

	if(!$mysupport->plugin_enabled)
	{
		return;
	}

	global $db, $cache, $lang, $templates, $theme, $thread, $forum, $mysupport_status, $mysupport_options, $mysupport_js, $support_denial_reasons, $mod_log_action, $redirect;

	$lang->load("mysupport");

	$tid = intval($thread['tid']);
	$fid = intval($thread['fid']);

	if(mysupport_forum($forum['fid']) && $mybb->input['action'] != "mysupport" && $mybb->input['action'] != "bestanswer")
	{
		// load the denied reasons so we can display them to staff if necessary
		if($mybb->settings['mysupport_enablesupportdenial'] && $forum['mysupportdenial'] && mysupport_usergroup("canmanagesupportdenial"))
		{
			$support_denial_reasons = array();
			$mysupport_cache = $cache->read("mysupport");
			if(!empty($mysupport_cache['deniedreasons']))
			{
				foreach($mysupport_cache['deniedreasons'] as $deniedreasons)
				{
					$support_denial_reasons[$deniedreason['mid']] = htmlspecialchars_uni($deniedreason['name']);
				}
			}
		}

		if($thread['issupportthread'] == 1)
		{
			$mysupport_options = "";
			$count = 0;
			$mysupport_solved = $mysupport_solved_and_close = $mysupport_technical = $mysupport_not_solved = $on_hold = $assigned_list = $priorities_list = $categories_list = $is_support_thread = "";
			// if it's not already solved
			if($thread['status'] != 1)
			{
				// can they mark as solved?
				if(mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupport_author'] && $thread['uid'] == $mybb->user['uid']))
				{
					// closing when solved is either optional, or off
					if($mybb->settings['mysupport_closewhensolved'] != "always")
					{
						if($mybb->input['mysupport_full'])
						{
							$selected = '';
							$value = 1;
							$label = $lang->solved;
							$mysupport_solved = eval($templates->render('mysupport_form_select_option'));
						}
						else
						{
							$text = $lang->sprintf($lang->markas_link, $lang->solved);
							$class = "mysupport_tab_solved";
							$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;status=1&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
							$mysupport_options .= eval($templates->render('mysupport_tab'));
						}
						++$count;
					}

					// is the ability to close turned on?
					if($mybb->settings['mysupport_closewhensolved'] != "never" && !$thread['closed'])
					{
						// if the close setting isn't never, this option would show regardless of whether it's set to always or optional
						if($mybb->input['mysupport_full'])
						{
							$selected = '';
							$value = 3;
							$label = $lang->solved_close;
							$mysupport_solved_and_close = eval($templates->render('mysupport_form_select_option'));
						}
						else
						{
							$text = $lang->sprintf($lang->markas_link, $lang->solved_close);
							$class = "mysupport_tab_solved";
							$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;status=3&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
							$mysupport_options .= eval($templates->render('mysupport_tab'));
						}
						++$count;
					}
				}

				// is the technical threads feature on?
				if($mybb->settings['mysupport_enabletechnical'])
				{
					// can they mark as techincal?
					if(mysupport_usergroup("canmarktechnical"))
					{
						if($thread['status'] != 2)
						{
							// if it's not marked as technical, give an option to mark it as such
							if($mybb->input['mysupport_full'])
							{
								$selected = '';
								$value = 2;
								$label = $lang->technical;
								$mysupport_technical = eval($templates->render('mysupport_form_select_option'));
							}
							else
							{
								$text = $lang->sprintf($lang->markas_link, $lang->technical);
								$class = "mysupport_tab_technical";
								$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;status=2&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
								$mysupport_options .= eval($templates->render('mysupport_tab'));
							}
						}
						else
						{
							// if it's already marked as technical, have an option to put it back to normal
							if($mybb->input['mysupport_full'])
							{
								$selected = '';
								$value = 4;
								$label = $lang->not_technical;
								$mysupport_technical = eval($templates->render('mysupport_form_select_option'));
							}
							else
							{
								$text = $lang->sprintf($lang->markas_link, $lang->not_technical);
								$class = "mysupport_tab_technical";
								$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;status=4&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
								$mysupport_options .= eval($templates->render('mysupport_tab'));
							}
						}
						++$count;
					}
				}
			}
			// if it's solved, all you can do is mark it as not solved
			else
			{
				// are they allowed to mark it as not solved if it's been marked solved already?
				if($mybb->settings['mysupport_unsolve'] && (mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupport_author'] && $thread['uid'] == $mybb->user['uid'])))
				{
					if($mybb->input['mysupport_full'])
					{
						$selected = '';
						$value = 0;
						$label = $lang->not_solved;
						$mysupport_not_solved = eval($templates->render('mysupport_form_select_option'));
					}
					else
					{
						$text = $lang->sprintf($lang->markas_link, $lang->not_solved);
						$class = "mysupport_tab_not_solved";
						$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;status=0&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
						$mysupport_options .= eval($templates->render('mysupport_tab'));
					}
					++$count;
				}
			}

			$status_list = "";
			// if the current count is more than 0 there's some status options to show
			if($count > 0)
			{
				$current_status = mysupport_get_friendly_status($thread['status']);
				$status_list .= "<label for=\"status\">".$lang->markas."</label> <select name=\"status\">\n";
				// show the current status but have the value as -1 so it's treated as not submitting a status
				// doing this because the assigning and priority menus show their current values, so do it here too for consistency
				$selected = '';
				$value = -1;
				$label = htmlspecialchars_uni($current_status);
				$status_list .= eval($templates->render('mysupport_form_select_option'));
				// also show a blank option with a value of -1
				$selected = '';
				$value = -1;
				$label = '';
				$status_list .= eval($templates->render('mysupport_form_select_option'));
				$status_list .= $mysupport_not_solved."\n";
				$status_list .= $mysupport_solved."\n";
				$status_list .= $mysupport_solved_and_close."\n";
				$status_list .= $mysupport_technical."\n";
				$status_list .= "</select>\n";
				if($mybb->input['ajax'])
				{
					$status_list = "<tr>\n<td class=\"trow1\" align=\"center\">".$status_list."\n</td>\n</tr>";
				}
			}

			if($mybb->settings['mysupport_enablebestanswer'])
			{
				// this doesn't need to show when viewing the 'full' form, as only staff will be seeing that
				if($thread['bestanswer'] != 0 && !$mybb->input['mysupport_full'])
				{
					$post = intval($thread['bestanswer']);
					$text = $lang->jump_to_bestanswer_tab;
					$class = "mysupport_tab_best_answer";
					$url = $mybb->settings['bburl']."/".get_post_link($post, $tid)."#pid".$post;
					$mysupport_options .= eval($templates->render('mysupport_tab'));
				}
			}

			if($thread['status'] != 1 && (mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupport_author'] && $thread['uid'] == $mybb->user['uid'])))
			{
				if($mybb->settings['mysupport_enableonhold'])
				{
					if($mybb->input['mysupport_full'])
					{
						$checked = "";
						if($thread['onhold'] == 1)
						{
							$checked = " checked=\"checked\"";
						}
						$on_hold = "<label for=\"onhold\">".$lang->onhold_form."</label> <input type=\"checkbox\" name=\"onhold\" id=\"onhold\" value=\"1\"{$checked} />";
						if($mybb->input['ajax'])
						{
							$on_hold = "<tr>\n<td class=\"trow1\" align=\"center\">".$on_hold."\n</td>\n</tr>";
						}
					}
					else
					{
						if($thread['onhold'] == 1)
						{
							$text = $lang->hold_off;
							$class = "mysupport_tab_hold";
							$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;onhold=0&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
						}
						else
						{
							$text = $lang->hold_on;
							$class = "mysupport_tab_hold";
							$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;onhold=1&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
						}
						$mysupport_options .= eval($templates->render('mysupport_tab'));
					}
				}
			}

			// do we need to show the link to show the additional options?
			// for assigning users, and setting priorities and categories, check the permission; if we're requesting the information, show it, if not, set this to true
			$show_more_link = false;
			// check if assigning threads is enabled and make sure you can assign threads to people
			// also check if the thread is currently not solved, or if it's solved but you can unsolve it; if any of those are true, you may want to assign it
			if($mybb->settings['mysupport_enableassign'] && mysupport_usergroup("canassign") && ($thread['status'] != 1 || ($thread['status'] == 1 && $mybb->settings['mysupport_unsolve'])))
			{
				if($mybb->input['mysupport_full'])
				{
					$assign_users = mysupport_get_assign_users();

					// only continue if there's one or more users that can be assigned threads
					if(!empty($assign_users))
					{
						$assigned_list .= "<label for=\"assign\">".$lang->assign_to."</label> <select name=\"assign\">\n";
						$assigned_list .= "<option value=\"0\"></option>\n";

						foreach($assign_users as $assign_userid => $assign_username)
						{
							$selected = "";
							if($thread['assign'] == $assign_userid)
							{
								$selected = " selected=\"selected\"";
							}
							$value = intval($assign_userid);
							$label = htmlspecialchars_uni($assign_username);
							$assigned_list .= eval($templates->render('mysupport_form_select_option'));
							++$count;
						}
						if($thread['assign'] != 0)
						{
							$selected = '';
							$value = -1;
							$label = $lang->assign_to_nobody;
							$assigned_list .= eval($templates->render('mysupport_form_select_option'));
						}

						$assigned_list .= "</select>\n";
						if($mybb->input['ajax'])
						{
							$assigned_list = "<tr>\n<td class=\"trow1\" align=\"center\">".$assigned_list."\n</td>\n</tr>";
						}
					}
				}
				$show_more_link = true;
			}

			// are priorities enabled and can this user set priorities?
			if($mybb->settings['mysupport_enablepriorities'] && mysupport_usergroup("cansetpriorities"))
			{
				if($mybb->input['mysupport_full'])
				{
					$mysupport_cache = $cache->read("mysupport");
					if(!empty($mysupport_cache['priorities']))
					{
						$priorities_list .= "<label for=\"priority\">".$lang->priority."</label> <select name=\"priority\">\n";
						$priorities_list .= "<option value=\"0\"></option>\n";

						foreach($mysupport_cache['priorities'] as $priority)
						{
							$option_style = "";
							if(!empty($priority['extra']))
							{
								$option_style = " style=\"background: #".htmlspecialchars_uni($priority['extra'])."\"";
							}
							$selected = "";
							if($thread['priority'] == $priority['mid'])
							{
								$selected = " selected=\"selected\"";
							}
							$priorities_list .= "<option value=\"".intval($priority['mid'])."\"{$option_style}{$selected}>".htmlspecialchars_uni($priority['name'])."</option>\n";
							++$count;
						}
						if($thread['priority'] != 0)
						{
							$priorities_list .= "<option value=\"-1\">".$lang->priority_none."</option>\n";
						}
						$priorities_list .= "</select>\n";
						if($mybb->input['ajax'])
						{
							$priorities_list = "<tr>\n<td class=\"trow1\" align=\"center\">".$priorities_list."\n</td>\n</tr>";
						}
					}
				}
				$show_more_link = true;
			}

			if(mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupport_author'] && $thread['uid'] == $mybb->user['uid']))
			{
				if($mybb->input['mysupport_full'])
				{
					$categories = mysupport_get_categories($forum);
					if(!empty($categories))
					{
						$categories_list .= "<label for=\"category\">".$lang->category."</label> <select name=\"category\">\n";
						$categories_list .= "<option value=\"0\"></option>\n";

						foreach($categories as $category_id => $category)
						{
							$selected = "";
							if($thread['prefix'] == $category_id)
							{
								$selected = " selected=\"selected\"";
							}
							$categories_list .= "<option value=\"".intval($category_id)."\"{$selected}>".htmlspecialchars_uni($category)."</option>\n";
							++$count;
						}
						if($thread['prefix'] != 0)
						{
							$categories_list .= "<option value=\"-1\">".$lang->category_none."</option>\n";
						}
						$categories_list .= "</select>\n";
						if($mybb->input['ajax'])
						{
							$categories_list = "<tr>\n<td class=\"trow1\" align=\"center\">".$categories_list."\n</td>\n</tr>";
						}
					}
				}
				$show_more_link = true;
			}

			if(mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupport_author'] && $thread['uid'] == $mybb->user['uid']) && $mybb->settings['mysupport_enablenotsupportthread'])
			{
				if($mybb->input['mysupport_full'])
				{
					$checked = "";
					if($thread['issupportthread'] == 1)
					{
						$checked = " checked=\"checked\"";
					}
					$is_support_thread .= "<label for=\"issupportthread\">".$lang->issupportthread."</label>\n";
					$is_support_thread .= "<input type=\"checkbox\" name=\"issupportthread\" id=\"issupportthread\"{$checked} value=\"1\" />\n";
					if($mybb->input['ajax'])
					{
						$is_support_thread = "<tr>\n<td class=\"trow1\" align=\"center\">".$is_support_thread."\n</td>\n</tr>";
					}
				}
				$show_more_link = true;
			}

			if($show_more_link)
			{
				$thread_url = $mybb->settings['bburl'].'/'.get_thread_link($tid);
				$thread_url .= (strpos('?', $thread_url) ? '?' : '&').'mysupport_full=1';

				$mysupport_js = '';

				$text = $lang->mysupport_tab_more;
				$class = "mysupport_tab_misc";
				$url = 'javascript:void(0);';
				$onclick = ' onclick="MyBB.popupWindow(\''.$thread_url.'&ajax=1\', null, true); return false;"';
				$mysupport_options .= eval($templates->render('mysupport_tab'));
			}
		}
		else
		{
			$text = $lang->issupportthread_mark_as_support_thread;
			$class = "mysupport_tab_misc";
			$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;issupportthread=1&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
			$mysupport_options .= eval($templates->render('mysupport_tab'));
		}

		if($mybb->input['mysupport_full'])
		{
			// are there actually any options to show for this user?
			if($count > 0)
			{
				if($mybb->input['ajax'] == 1)
				{
					$mysupport_options = eval($templates->render('mysupport_form_ajax', true, false));
					// this is an AJAX request, echo and exit, GO GO GO
					echo $mysupport_options;
					exit;
				}
				else
				{
					$mysupport_options = eval($templates->render('mysupport_form'));
				}
			}
		}
		else
		{
			$mysupport_options = "<br /><div class=\"mysupport_tabs\">{$mysupport_options}</div>";
		}

		if($thread['issupportthread'] == 1)
		{
			$mysupport_status = mysupport_get_display_status($thread['status'], $thread['onhold'], $thread['statustime'], $thread['uid']);
		}
	}

	if($mybb->input['action'] == "mysupport")
	{
		verify_post_check($mybb->input['my_post_key']);
		$status = $db->escape_string($mybb->input['status']);
		$assign = $db->escape_string($mybb->input['assign']);
		$priority = $db->escape_string($mybb->input['priority']);
		$category = $db->escape_string($mybb->input['category']);
		$onhold = $db->escape_string($mybb->input['onhold']);
		$issupportthread = $db->escape_string($mybb->input['issupportthread']);
		$tid = intval($thread['tid']);
		$fid = intval($thread['fid']);
		$old_status = intval($thread['status']);
		$old_assign = intval($thread['assign']);
		$old_priority = intval($thread['priority']);
		$old_category = intval($thread['prefix']);
		$old_onhold = intval($thread['onhold']);
		$old_issupportthread = intval($thread['issupportthread']);

		// we need to make sure they haven't edited the form to try to perform an action they're not allowed to do
		// we check everything in the entire form, if any part of it is wrong, it won't do anything
		if(!mysupport_forum($fid))
		{
			mysupport_error($lang->error_not_mysupport_forum);
			exit;
		}
		// are they trying to assign the same status it already has?
		if($status == $old_status && !isset($mybb->input['onhold']) && !isset($mybb->input['issupportthread']))
		{
			$duplicate_status = mysupport_get_friendly_status($status);
			mysupport_error($lang->sprintf($lang->error_same_status, $duplicate_status));
			exit;
		}
		elseif($status == 0)
		{
			// either the ability to unsolve is turned off,
			// they don't have permission to mark as not solved via group permissions, or they're not allowed to mark it as not solved even though they authored it
			if($mybb->settings['mysupport_unsolve'] || (!mysupport_usergroup("canmarksolved") && !($mybb->settings['mysupport_author'] && $thread['uid'] == $mybb->user['uid'])))
			{
				mysupport_error($lang->no_permission_mark_notsolved);
				exit;
			}

			$valid_action = true;
		}
		elseif($status == 1)
		{
			// either they're not in a group that can mark as solved
			// or they're not allowed to mark it as solved even though they authored it
			if(!mysupport_usergroup("canmarksolved") && !($mybb->settings['mysupport_author'] && $thread['uid'] == $mybb->user['uid']))
			{
				mysupport_error($lang->no_permission_mark_solved);
				exit;
			}

			$valid_action = true;
		}
		elseif($status == 2)
		{
			if($mybb->settings['mysupport_enabletechnical'])
			{
				mysupport_error($lang->technical_not_enabled);
				exit;
			}

			// they don't have the ability to mark threads as technical
			if(!mysupport_usergroup("canmarktechnical"))
			{
				mysupport_error($lang->no_permission_mark_technical);
				exit;
			}

			$valid_action = true;
		}
		elseif($status == 3)
		{
			// either closing of threads is turned off altogether
			// or it's on, but they're not in a group that can't mark as solved
			if($thread['closed'] == 1 || $mybb->settings['mysupport_closewhensolved'] == "never" || ($mybb->settings['mysupport_closewhensolved'] != "never" && (!mysupport_usergroup("canmarksolved") && !($mybb->settings['mysupport_author'] && $thread['uid'] == $mybb->user['uid']))))
			{
				mysupport_error($lang->no_permission_mark_solved_close);
				exit;
			}

			$valid_action = true;
		}
		elseif($status == 4)
		{
			// they don't have the ability to mark threads as not technical
			if(!mysupport_usergroup("canmarktechnical"))
			{
				mysupport_error($lang->no_permission_mark_nottechnical);
				exit;
			}

			$valid_action = true;
		}
		// check if the thread is being put on/taken off hold
		// check here is a bit weird as it'll be 1/-1 if coming from the tab link, and 1 or nothing if coming from the checkbox in the form
		// if it's coming from the form, check if it's being put on hold and wasn't on hold before (put on hold), or the box wasn't checked and it was on hold before (taken off hold)
		// or, if it's coming from the link, check if it's being put on hold and wasn't on hold before (put on hold), or it's being taken off hold and was on hold before
		if(($mybb->input['via_form'] == 1 && (($onhold == 1 && $old_onhold == 0) || (!$onhold && $old_onhold == 1))) || (!$mybb->input['via_form'] && (($onhold == 1 && $old_onhold == 0) || ($onhold == -1 && $old_onhold == 1))))
		{
			if(!$mybb->settings['mysupport_enableonhold'])
			{
				mysupport_error($lang->onhold_not_enabled);
				exit;
			}

			if($thread['status'] == 1)
			{
				mysupport_error($lang->onhold_solved);
				exit;
			}

			if(!mysupport_usergroup("canmarksolved") && !($mybb->settings['mysupport_author'] && $thread['uid'] == $mybb->user['uid']))
			{
				mysupport_error($lang->no_permission_thread_hold);
				exit;
			}

			// we don't need to perform the big check above again, as if we're in here we know it's being changed
			// it'll either be 1, 0 or -1, if it's anything other than 1, we're taking it off hold
			if($onhold != 1)
			{
				$ohold = 0;
			}

			$valid_action = true;
		}
		if(($mybb->input['via_form'] == 1 && (($issupportthread == 1 && $old_issupportthread == 0) || (!$issupportthread && $old_issupportthread == 1))))
		{
			if(!$mybb->settings['mysupport_enablenotsupportthread'])
			{
				mysupport_error($lang->issupportthread_not_enabled);
				exit;
			}

			if(!mysupport_usergroup("canmarksolved") && !($mybb->settings['mysupport_author'] && $thread['uid'] == $mybb->user['uid']))
			{
				mysupport_error($lang->no_permission_issupportthread);
			}
		}
		// trying to assign a thread to someone
		if($assign != 0)
		{
			if(!$mybb->settings['mysupport_enableassign'])
			{
				mysupport_error($lang->assign_not_enabled);
				exit;
			}
			// trying to assign a solved thread
			// this is needed to see if we're trying to assign a currently solved thread whilst at the same time changing the status of it
			// the option to assign will still be there if it's solved as you may want to unsolve it and assign it again, but we can't assign it if it's staying solved, we have to be unsolving it
			if($thread['status'] == 1 && $status != 0)
			{
				mysupport_error($lang->assign_solved);
				exit;
			}

			if(!mysupport_usergroup("canassign"))
			{
				mysupport_error($lang->assign_no_perms);
				exit;
			}

			$assign_users = mysupport_get_assign_users();
			// -1 is what's used to unassign a thread so we need to exclude that
			if(!array_key_exists($assign, $assign_users) && $assign != "-1")
			{
				mysupport_error($lang->assign_invalid);
				exit;
			}

			$valid_action = true;
		}
		// setting a priority
		if($priority != 0)
		{
			if(!$mybb->settings['mysupport_enablepriorities'])
			{
				mysupport_error($lang->priority_not_enabled);
				exit;
			}

			if(!mysupport_usergroup("cansetpriorities"))
			{
				mysupport_error($lang->priority_no_perms);
				exit;
			}

			if($thread['status'] == 1 && $status != 0)
			{
				mysupport_error($lang->priority_solved);
				exit;
			}

			$mysupport_cache = $cache->read("mysupport");
			$mids = array();
			if(!empty($mysupport_cache['priorities']))
			{
				foreach($mysupport_cache['priorities'] as $priority_info)
				{
					$mids[] = intval($priority_info['mid']);
				}
			}
			if(!in_array($priority, $mids) && $priority != "-1")
			{
				mysupport_error($lang->priority_invalid);
				exit;
			}

			$valid_action = true;
		}
		// setting a category
		if($category != 0)
		{
			$categories = mysupport_get_categories($forum);
			if(!array_key_exists($category, $categories) && $category != "-1")
			{
				mysupport_error($lang->category_invalid);
				exit;
			}

			$valid_action = true;
		}
		// it didn't hit an error with any of the above, it's a valid action
		if($valid_action !== false)
		{
			// if you're choosing the same status or choosing none
			// and assigning the same user or assigning none (as in the empty option, not choosing 'Nobody' to remove an assignment)
			// and setting the same priority or setting none (as in the empty option, not choosing 'None' to remove a priority)
			// and setting the same hold status, and setting the same issupportthread status
			// then you're not actually doing anything, because you're either choosing the same stuff, or choosing nothing at all
			if(($status == $old_status || $status == "-1") && ($assign == $old_assign || $assign == 0) && ($priority == $old_priority || $priority == 0) && ($category == $old_category || $category == 0) && ($onhold == $old_onhold) && ($issupportthread == $old_issupportthread))
			{
				mysupport_error($lang->error_no_action);
				exit;
			}

			$mod_log_action = "";
			$redirect = "";

			if($issupportthread != $old_issupportthread)
			{
				mysupport_change_issupportthread($thread, $issupportthread);
			}
			else
			{
				// change the status and move/close
				if($status != $old_status && $status != "-1")
				{
					mysupport_change_status($thread, $status);
				}

				if($onhold != $old_onhold)
				{
					mysupport_change_hold($thread, $onhold);
				}

				// we need to see if the same user has been submitted so it doesn't run this for no reason
				// we also need to check if it's being marked as solved, if it is we don't need to do anything with assignments, it'll just be ignored
				if($assign != $old_assign && ($assign != 0 && $status != 1 && $status != 3))
				{
					mysupport_change_assign($thread, $assign);
				}

				// we need to see if the same priority has been submitted so it doesn't run this for no reason
				// we also need to check if it's being marked as solved, if it is we don't need to do anything with priorities, it'll just be ignored
				if($priority != $old_priority && ($priority != 0 && $status != 1))
				{
					mysupport_change_priority($thread, $priority);
				}

				// we need to see if the same category has been submitted so it doesn't run this for no reason
				if($category != $old_category && ($category != 0 && $status != 1))
				{
					mysupport_change_category($thread, $category);
				}
			}

			if(!empty($mod_log_action))
			{
				$mod_log_data = array(
					"fid" => intval($fid),
					"tid" => intval($tid)
				);
				log_moderator_action($mod_log_data, $mod_log_action);
			}
			// where should they go to afterwards?
			$thread_url = get_thread_link($tid);
			redirect($thread_url, $redirect);
		}
	}
	elseif($mybb->input['action'] == "bestanswer")
	{
		verify_post_check($mybb->input['my_post_key']);
		if(!$mybb->settings['mysupport_enablebestanswer'])
		{
			mysupport_error($lang->bestanswer_not_enabled);
			exit;
		}

		$pid = intval($mybb->input['pid']);
		// we only have a pid so we need to get the tid, fid, uid, and mysupport information of the thread it belongs to
		$query = $db->query("
			SELECT t.fid, t.tid, t.uid AS author_uid, p.uid AS bestanswer_uid, t.status, t.bestanswer
			FROM ".TABLE_PREFIX."threads t
			INNER JOIN ".TABLE_PREFIX."forums f
			INNER JOIN ".TABLE_PREFIX."posts p
			ON (t.tid = p.tid AND t.fid = f.fid AND p.pid = '".$pid."')
		");
		$post_info = $db->fetch_array($query);

		// is this post in a thread that isn't within an allowed forum?
		if(!mysupport_forum($post_info['fid']))
		{
			mysupport_error($lang->bestanswer_invalid_forum);
			exit;
		}
		// did this user author this thread?
		elseif($mybb->user['uid'] != $post_info['author_uid'])
		{
			mysupport_error($lang->bestanswer_not_author);
			exit;
		}
		// is this post already the best answer?
		elseif($pid == $post_info['bestanswer'])
		{
			// this will mark it as the best answer
			$status_update = array(
				"bestanswer" => 0
			);
			// update the bestanswer column for this thread with 0
			$db->update_query("threads", $status_update, "tid = '".intval($post_info['tid'])."'");

			// are we removing points for this?
			if(mysupport_points_system_enabled())
			{
				if(!empty($mybb->settings['mysupport_bestanswerpoints']) && $mybb->settings['mysupport_bestanswerpoints'])
				{
					mysupport_update_points($mybb->settings['mysupport_bestanswerpoints'], $post_info['bestanswer_uid'], true);
				}
			}

			$redirect = "";
			mysupport_redirect_message($lang->unbestanswer_redirect);

			// where should they go to afterwards?
			$thread_url = get_thread_link($post_info['tid']);
			redirect($thread_url, $redirect);
		}
		// mark it as the best answer
		else
		{
			$status_update = array(
				"bestanswer" => intval($pid)
			);
			// update the bestanswer column for this thread with the pid of the best answer
			$db->update_query("threads", $status_update, "tid = '".intval($post_info['tid'])."'");

			// are we adding points for this?
			if(mysupport_points_system_enabled())
			{
				if(!empty($mybb->settings['mysupport_bestanswerpoints']) && $mybb->settings['mysupport_bestanswerpoints'])
				{
					mysupport_update_points($mybb->settings['mysupport_bestanswerpoints'], $post_info['bestanswer_uid']);
				}
			}

			// if this thread isn't solved yet, do that too whilst we're here
			// if they're marking a post as the best answer, it must have solved the thread, so save them marking it as solved manually
			if($post_info['status'] != 1 && (mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupport_author'] && $post_info['author_uid'] == $mybb->user['uid'])))
			{
				$mod_log_action = "";
				$redirect = "";

				// change the status
				mysupport_change_status($post_info, 1);

				if(!empty($mod_log_action))
				{
					$mod_log_data = array(
						"fid" => intval($post_info['fid']),
						"tid" => intval($post_info['tid'])
					);
					log_moderator_action($mod_log_data, $mod_log_action);
				}
				mysupport_redirect_message($lang->bestanswer_redirect);
			}
			else
			{
				$redirect = "";
				mysupport_redirect_message($lang->bestanswer_redirect);
			}

			// where should they go to afterwards?
			$thread_url = get_thread_link($post_info['tid']);
			redirect($thread_url, $redirect);
		}
	}
}

function usercp_start20()
{
	global $mybb, $db, $lang, $templates, $mysupport_usercp_options, $mysupport;

	if($mybb->settings['mysupport_displaytypeuserchange'])
	{
		if($mybb->input['action'] == "do_options")
		{
			$update = array(
				"mysupportdisplayastext" => intval($mybb->input['mysupportdisplayastext'])
			);

			$db->update_query("users", $update, "uid = '".intval($mybb->user['uid'])."'");
		}
		elseif($mybb->input['action'] == "options")
		{
			$lang->load("mysupport");

			if($mysupport->plugin_enabled)
			{
				$mysupportdisplayastextcheck = "";
				if($mybb->user['mysupportdisplayastext'] == 1)
				{
					$mysupportdisplayastextcheck = " checked=\"checked\"";
				}
			}

			$mysupport_usercp_options = eval($templates->render('mysupport_usercp_options'));
		}
	}
}