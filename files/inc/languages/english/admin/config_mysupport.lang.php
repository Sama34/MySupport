<?php
/**
 * MySupport 1.8.0 - Admin Language File

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

$l['mysupport'] = "MySupport";
$l['mysupport_desc'] = "Add features to your forum to help with giving support. Allows you to mark a thread as solved or technical, assign threads to users, give threads priorities, mark a post as the best answer in a thread, and more to help you run a support forum.";

$l['mysupport_task_description'] = "MySupport Task";

$l['solved'] = "Solved";
$l['not_solved'] = "Not Solved";
$l['technical'] = "Technical";
$l['thread'] = "Thread";
$l['forum'] = "Forum";
$l['started_by'] = "Started by";
$l['status'] = "Status";

$l['mysupport_uninstall_warning'] = "Are you sure you want to uninstall MySupport? You will permanently lose all thread statuses.";

$l['home'] = "Home";
$l['home_header'] = "General Information";
$l['home_nav'] = "Basic information on MySupport.";
$l['general'] = "General";
$l['general_header'] = "General Configuration";
$l['general_nav'] = "Manage general settings such as which forums to enable MySupport in, and set permissions for usergroups.";
$l['technical_assign'] = "Technical/Assigning Threads";
$l['technical_assign_nav'] = "Manage settings for marking threads as technical/assigning threads.";
$l['technical_header'] = "Technical Threads Configuration";
$l['assign_header'] = "Assigning Threads Configuration";
$l['categories'] = "Categories";
$l['priorities'] = "Priorities";
$l['priorities_current'] = "Current Priorities";
$l['priorities_header'] = "Priority Configuration";
$l['priorities_thread_list_header'] = "Threads with a priority of <em>'{1}'</em>.";
$l['priorities_add'] = "Add a Priority";
$l['priorities_edit'] = "Edit a Priority";
$l['priorities_nav'] = "Manage what priorities you can give to threads.";
$l['support_denial'] = "Support Denial";
$l['support_denial_header'] = "Support Denial Configuration";
$l['support_denial_nav'] = "Manage reasons for denying support and who is denied support.";
$l['support_denial_reason_current'] = "Current Reasons";
$l['support_denial_reason_users'] = "Users denied support";
$l['support_denial_reason_add'] = "Add a reason";
$l['support_denial_reason_edit'] = "Edit a reason";
$l['support_denial_reason_edit_user'] = "Edit a user";
$l['mysupport_settings'] = "Settings";

$l['support_threads'] = "Support Threads Overview";
$l['support_threads_total'] = "<strong>Total Support Threads:</strong> {1}";
$l['support_threads_solved'] = "<strong>Solved:</strong> {1} ({2})";
$l['support_threads_unsolved'] = "<strong>Unsolved:</strong> {1} ({2})";
$l['support_threads_new'] = "<strong>Support Threads today:</strong> {1}";
$l['technical_threads_total'] = "<strong>Technical Threads:</strong> {1}";
$l['technical_threads_new'] = "<strong>Technical Threads today:</strong> {1}";
$l['assigned_threads_total'] = "<strong>Assigned Threads:</strong> {1}";
$l['assigned_threads_new'] = "<strong>Assigned Threads today:</strong> {1}";
$l['mysupport_name'] = "Name";
$l['mysupport_description'] = "Description";
$l['mysupport_view_threads'] = "View Threads";

$l['mysupport_forums'] = "Where to enable MySupport?";
$l['mysupportdenial_forums'] = "Where to enable MySupport Denial??";
$l['mysupportdenial_forums_desc'] = "Note that this only affects forums where MySupport is enabled in above setting.";
$l['mysupport_move_forum'] = "Where to move threads when solved?";
$l['mysupport_move_forum_desc'] = "<strong>Note:</strong> if a thread is moved when it is marked as solved, 'unsolving' the thread will <strong>not</strong> move the thread back to it's original forum.";
$l['mysupport_canmarksolved'] = "Who can mark threads as solved?";
$l['mysupport_canmarktechnical'] = "Who can mark threads as technical?";
$l['mysupport_canseetechnotice'] = "Who can see the technical threads notice?";
$l['mysupport_canassign'] = "Who can assign threads?";
$l['mysupport_canbeassigned'] = "Who can be assigned threads?";
$l['mysupport_cansetpriorities'] = "Who can set priorities?";
$l['mysupport_canseepriorities'] = "Who can see priorities?";
$l['mysupport_cansetcategories'] = "Who can set categories?";
$l['mysupport_canmanagesupportdenial'] = "Who can manage support denial?";
$l['mysupport_what_to_log'] = "What actions to log?";
$l['mysupport_what_to_log_desc'] = "What MySupport actions should have a moderator entry created? This is an alias of the 'Add moderator log entry' setting in the normal MySupport settings; updating here will update that setting and vice versa.";

$l['can_manage_mysupport'] = "Can manage MySupport";

$l['categories_prefixes_redirect'] = "Use the Thread Prefixes feature to create categories to be used with MySupport.";

$l['mysupport_submit'] = "Save MySupport Settings";
$l['mysupport_add_priority_submit'] = "Add Priority";
$l['mysupport_edit_priority_submit'] = "Edit Priority";
$l['mysupport_add_support_denial_reason_submit'] = "Add Reason";
$l['mysupport_edit_support_denial_reason_submit'] = "Edit Reason";
$l['mysupport_edit_user_submit'] = "Edit user";
$l['success_general'] = "General MySupport settings updated.";
$l['error_general_move_forum'] = "Invalid forum to move threads to. Please select a forum, not a category.";
$l['success_technical'] = "Technical threads settings updated.";
$l['success_assign'] = "Assigning threads settings updated.";
$l['success_priorities'] = "Priority settings updated.";
$l['priority_added'] = "Priority successfully added.";
$l['priority_deleted'] = "Priority successfully deleted.";
$l['priority_delete_confirm'] = "Are you sure you want to delete this priority?";
$l['priority_delete_confirm_count'] = "It is currently being used by {1} threads.";
$l['priority_edited'] = "Priority successfully edited.";
$l['priority_invalid'] = "Invalid priority.";
$l['priority_no_name'] = "Please enter a name for this priority.";
$l['priorities_thread_list_none'] = "There are no threads with a priority of <em>'{1}'</em>.";
$l['priority_style'] = "Style";
$l['priority_style_description'] = "Enter the HEX code of the colour to highlight threads with this priority. This colour will be used to highlight threads on the forum display pages. If a thread has been unapproved, this colour will not override the unapproved colour.";
$l['success_support_denial'] = "Support denial settings updated.";
$l['support_denial_reason_added'] = "Reason successfully added.";
$l['support_denial_reason_deleted'] = "Reason successfully deleted.";
$l['support_denial_reason_edited'] = "Reason successfully edited.";
$l['support_denial_reason_no_name'] = "Please enter a name for this reason.";
$l['support_denial_reason_no_description'] = "Please enter a description for this reason.";
$l['support_denial_reason_invalid'] = "Invalid reason.";
$l['support_denial_reason_delete_confirm'] = "Are you sure you want to delete this reason?";
$l['support_denial_reason_delete_confirm_count'] = "It has been given as a reason to {1} user(s). Deleting this reason will simply not show any reason to this user, it won't allow them to receive support again.";
$l['support_denial_reason_description_description'] = "This is what will be displayed to a user when they are denied support.";

$l['mysupport_display_style_forced'] = "Successfully forced the current status display style to current users.";

$l['mysupport_mod_log_action_0'] = "Mark as Not Solved";
$l['mysupport_mod_log_action_1'] = "Mark as Solved";
$l['mysupport_mod_log_action_2'] = "Mark as Technical";
$l['mysupport_mod_log_action_4'] = "Mark as Not Technical";
$l['mysupport_mod_log_action_5'] = "Add/change assign";
$l['mysupport_mod_log_action_6'] = "Remove assign";
$l['mysupport_mod_log_action_7'] = "Add/change priority";
$l['mysupport_mod_log_action_8'] = "Remove priority";
$l['mysupport_mod_log_action_9'] = "Add/change category";
$l['mysupport_mod_log_action_10'] = "Remove category";
$l['mysupport_mod_log_action_11'] = "Deny support/revoke support";
$l['mysupport_mod_log_action_12'] = "Put thread on/take thread off hold";
$l['mysupport_mod_log_action_13'] = "Mark as support thread/not support thread";

// Settings
$l['setting_mysupport_enabled'] = "Global On/Off setting.";
$l['setting_mysupport_enabled_desc'] = "Turn MySupport on or off here.";
$l['setting_mysupport_displaytype'] = "How to display the status of a thread?";
$l['setting_mysupport_displaytype_desc'] = "'Image' will show a red, green, or blue icon depending on whether a thread is unsolved, solved, or marked as technical. If '[Solved]' is selected, the text '[Solved]' will be displayed before the thread titles (or '[Technical]' if marked as such), while not editing the thread title itself. 'Image' is default as it is intended to be clear but unobtrusive. This setting will be overwridden by a user's personal setting if you've let them change it with the setting below; to force the current setting to all current users, <a href='index.php?module=config-mysupport&amp;action=forcedisplaytype'>click here</a>.";
$l['setting_mysupport_displaytypeuserchange'] = "Let users change how threads are displayed?";
$l['setting_mysupport_displaytypeuserchange_desc'] = "Do you want to allow users to change how the status is displayed? If yes, they will have a setting in their User CP Options to choose how the status will be shown, which will override the setting you choose above.";
$l['setting_mysupport_displayto'] = "Who should the status of a thread be shown to?";
$l['setting_mysupport_displayto_desc'] = "This setting enables you to show the statuses of threads globally, only to people who can mark as solved, or to people who can mark as solved and the author of a thread. This means you can only show people the statuses of their own threads (to save clutter for everybody else) or hide them from view completely so users won't even know the system is in place.";
$l['setting_mysupport_author'] = "Can the author mark their own threads as solved?";
$l['setting_mysupport_author_desc'] = "If this is set to Yes, they will be able to mark their own threads as solved even if their usergroup cannot mark threads as solved.";
$l['setting_mysupport_closewhensolved'] = "Close threads when marked as solved?";
$l['setting_mysupport_closewhensolved_desc'] = "Should the thread be closed when it is marked as solved? If the thread gets marked as not solved, the thread will be reopened, provided it was closed by marking it as solved.";
$l['setting_mysupport_moveredirect'] = "Move Redirect";
$l['setting_mysupport_moveredirect_desc'] = "How long to leave a thread redirect in the original forum for? For this to do anything you must have chosen a forum to move threads to, by going to ACP > Configuration > MySupport > General.";
$l['setting_mysupport_unsolve'] = "Can a user 'unsolve' a thread?";
$l['setting_mysupport_unsolve_desc'] = "If a user marks a thread as solved but then still needs help, can the thread author mark it as not solved? <strong>Note:</strong> if the thread was moved when it was originally marked as solved, this will <strong>not</strong> move it back to it's original forum, therefore it is not recommended to allow this if you choose to move a thread when it is solved.";
$l['setting_mysupport_bumpnotice'] = "Show a 'bump notice' for solved threads?";
$l['setting_mysupport_bumpnotice_desc'] = "If a thread is solved, do you want to show a warning to people to make their own thread rather than bumping the thread they're looking at? The message will be in the textarea on the new reply and quick reply box on the showthread page, so can just be removed should the user still choose to bump the thread. The warning will not be shown to the poster of the thread, or staff.";
$l['setting_mysupport_enableonhold'] = "Enable putting threads 'on hold'";
$l['setting_mysupport_enableonhold_desc'] = "This will enable you to put a thread 'on hold'. This means the thread is pending a reply from the user, or you're waiting for feedback from the user, etc. It is designed to make it easier to see what is being dealt with and what needs attention. This will change the usual not solved/technical status indicator to a yellow indicator instead. This will change automatically; when a thread is replied to it will be placed on hold, and when the thread's creator replies, it will be taken off hold and will show its old status again. A thread can also be manually put on hold or taken off hold at any time. Furthermore, if a user is the last to post in a thread and the thread is put on hold, if the user edits their post, or replies again, even if it auto-merges, it will take the thread off hold again.";
$l['setting_mysupport_enablebestanswer'] = "Enable ability to highlight the best answer?";
$l['setting_mysupport_enablebestanswer_desc'] = "When a thread is solved, can the author choose to highlight the best answer in the thread, i.e. the post that solved the thread for them? Only the thread author can do this, it can be undone, and will highlight the post with the 'mysupport_bestanswer_highlight' class in global.css. If this feature is used when a thread has not yet been marked as solved, choosing to highlight a post will mark it as solved as well, provided they have the ability to.";
$l['setting_mysupport_bestanswerrep'] = "Reputation on best answer";
$l['setting_mysupport_bestanswerrep_desc'] = "This will give a reputation to the poster of the best answer of the thread, unless the user marks one of their own posts as the best answer. The reputation will be linked with the post. Set to 0 or leave blank to disable. <strong>Note:</strong> For this to work, the 'Allow Multiple Reputation' setting must be enabled in the Reputation settings. Unmarking a post as the best answer will remove the reputation.";
$l['setting_mysupport_enabletechnical'] = "Enable the 'Mark as Technical' feature?";
$l['setting_mysupport_enabletechnical_desc'] = "This will mark a thread as requiring technical attention. This is useful if a thread would be better answered by someone with more knowledge/experience than the standard support team. Configurable below.";
$l['setting_mysupport_hidetechnical'] = "'Hide' technical status if cannot mark as technical?";
$l['setting_mysupport_hidetechnical_desc'] = "Do you want to only show a thread as being technical if the logged in user can mark as technical? Users who cannot mark as technical will see the thread as 'Not Solved'. For example, if a moderator can mark threads as technical and regular users cannot, when a thread is marked technical, moderators will see it as technical but regular users will see it as 'Not Solved'. This can be useful if you want to hide the fact the technical threads feature is in use or that a thread has been marked technical.";
$l['setting_mysupport_technicalnotice'] = "Where should the technical threads notice be shown?";
$l['setting_mysupport_technicalnotice_desc'] = "If set to global, it will show in the header on every page. If set to specific, it will only show in the relevant forums; for example, if fid=2 has two technical threads, the notice will only show in that forum.";
$l['setting_mysupport_enableassign'] = "Enable the ability to assign threads?";
$l['setting_mysupport_enableassign_desc'] = "If set to yes, you will be able to assign threads to people. They will have access to a list of threads assigned to them, a header notification message, and there's the ability to send them a PM when they are assigned a new thread. All configurable below.";
$l['setting_mysupport_assignpm'] = "PM when assigned thread";
$l['setting_mysupport_assignpm_desc'] = "Should users receive a PM when they are assigned a thread? They will not get one if they assign a thread to themselves.";
$l['setting_mysupport_assignsubscribe'] = "Subscribe when assigned";
$l['setting_mysupport_assignsubscribe_desc'] = "Should a user be automatically subscribed to a thread when it's assigned to them? If the user's options are setup to receive email notifications for subscriptions then they will be subscribed to the thread by email, otherwise they will be subscribed to the thread without email.";
$l['setting_mysupport_enablepriorities'] = "Enable the ability to add a priority to threads?";
$l['setting_mysupport_enablepriorities_desc'] = "If set to yes, you will be able to give threads priorities, which will highlight threads in a specified colour on the forum display.";
$l['setting_mysupport_enablenotsupportthread'] = "Enable the ability to mark threads as not support threads?";
$l['setting_mysupport_enablenotsupportthread_desc'] = "There are times when you may have a thread in a support forum which isn't really classed as a support thread, or something that can't be 'solved' per se. The thread would then behave like a normal thread and would not have any of the MySupport options show up in it.";
$l['setting_mysupport_enablesupportdenial'] = "Enable support denial?";
$l['setting_mysupport_enablesupportdenial_desc'] = "If set to yes, you will be able to deny support to selected users, meaning they won't be able to make threads in MySupport forums.";
$l['setting_mysupport_closewhendenied'] = "Close all support threads when denied support?";
$l['setting_mysupport_closewhendenied_desc'] = "This will close all support thread made by a user when you deny them support. If you revoke support denial, all threads that were closed will be reopened, and any threads that were already closed will stay closed.";
$l['setting_mysupport_modlog'] = "Add moderator log entry?";
$l['setting_mysupport_modlog_desc'] = "Do you want to log changes to the status of a thread? These will show in the Moderator CP Moderator Logs list. Separate with a comma. Leave blank for no logging.<br /><strong>Note:</strong> <strong>0</strong> = Mark as Not Solved, <strong>1</strong> = Mark as Solved, <strong>2</strong> = Mark as Technical, <strong>4</strong> = Mark as Not Technical, <strong>5</strong> = Add/change assign, <strong>6</strong> = Remove assign, <strong>7</strong> = Add/change priority, <strong>8</strong> = Remove priority, <strong>9</strong> = Add/change category, <strong>10</strong> = Remove category, <strong>11</strong> = Deny support/revoke support denial, <strong>12</strong> = Put thread on/take thread off hold, <strong>13</strong> = Mark thread as support thread/not support thread. <strong>For a better method of managing this setting, <a href=\"index.php?module=config-mysupport&action=general\">click here</a>.</strong>";
$l['setting_mysupport_highlightstaffposts'] = "Highlight staff posts?";
$l['setting_mysupport_highlightstaffposts_desc'] = "This will highlight posts made by staff, using the 'mysupport_staff_highlight' class in global.css.";
$l['setting_mysupport_threadlist'] = "Enable the list of support threads?";
$l['setting_mysupport_threadlist_desc'] = "If this is enabled, users will have an option in their User CP showing them all their threads in any forums where the Mark as Solved feature is enabled, and will include the status of each thread.";
$l['setting_mysupport_highlightstaffposts'] = "Highlight staff posts?";
$l['setting_mysupport_highlightstaffposts_desc'] = "This will highlight posts made by staff, using the 'mysupport_staff_highlight' class in global.css.";
$l['setting_mysupport_stats'] = "Small stats section on support/technical lists";
$l['setting_mysupport_stats_desc'] = "This will show a small stats section at the top of the list of support/technical threads. It will show a simple bar and counts of the amount of solved/unsolved/techncial threads.";
$l['setting_mysupport_relativetime'] = "Display status times with a relative date?";
$l['setting_mysupport_relativetime_desc'] = "If this is enabled, the time of a status will be shown as a relative time, e.g. 'X Months, Y Days ago' or 'X Hours, Y Minutes ago', rather than a specific date.";
$l['setting_mysupport_taskautosolvetime'] = "Task auto-solve cut-off time";
$l['setting_mysupport_taskautosolvetime_desc'] = "A task will auto-solve threads that have had no posts and no MySupport actions applied on them for a certain period of time; choose that period of time here.";
$l['setting_mysupport_taskbackup'] = "Backup MySupport data automatically";
$l['setting_mysupport_taskbackup_desc'] = "A task will automatically backup all MySupport data to the backups folder in your admin directory. It must be writeable for this to be possible. No more than 3 backups will be stored, older backups will be automatically deleted.";
$l['setting_mysupport_pointssystem'] = "Points System";
$l['setting_mysupport_pointssystem_desc'] = "Which points system do you want to integrate with MySupport? MyPS and NewPoints are available. If you have another points system you would like to use, choose 'Other' and fill in the new options that will appear.";
$l['setting_mysupport_pointssystemname'] = "Custom Points System name";
$l['setting_mysupport_pointssystemname_desc'] = "If you want to use a points system that is not supported in MySupport by default, put the name of it here. The name is the same as the name of the file for the plugin in <em>./inc/plugins/</em>. For example, if the plugin file was called <strong>mypoints.php</strong>, you would put <strong>mypoints</strong> into this setting.";
$l['setting_mysupport_pointssystemcolumn'] = "Custom Points System database column";
$l['setting_mysupport_pointssystemcolumn_desc'] = "If you want to use a points system that is not supported in MySupport by default, put the name of the column from the users table which stores the number of points here. if you are unsure what to put here, please contact the author of the points plugin you want to use.";
$l['setting_mysupport_bestanswerpoints'] = "Give points to the author of the best answer?";
$l['setting_mysupport_bestanswerpoints_desc'] = "How many points do you want to give to the author of the best answer? The same amount of points will be removed should the post be removed as the best answer. Leave blank to give none.";
