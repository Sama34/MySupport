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

namespace MySupport\Core;

function load_language()
{
	global $lang;

	isset($lang->mysupport) || $lang->load(defined('IN_ADMINCP') ? 'config_mysupport' : 'mysupport');
}

function load_pluginlibrary($check=true)
{
	global $PL, $lang;

	\MySupport\Core\load_language();

	if($file_exists = file_exists(PLUGINLIBRARY))
	{
		global $PL;
	
		$PL or require_once PLUGINLIBRARY;
	}

	if(!$check)
	{
		return;
	}

	$_info = \MySupport\Admin\_info();

	if(!$file_exists || $PL->version < $_info['pl']['version'])
	{
		flash_message($lang->sprintf($lang->mysupport_pluginlibrary, $_info['pl']['url'], $_info['pl']['version']), 'error');

		admin_redirect('index.php?module=config-plugins');
	}
}

function addHooks(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

	foreach($definedUserFunctions as $callable)
	{
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

		if(substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase.'\\')
		{
            $hookName = substr_replace($callable, null, 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

			if(is_numeric(substr($hookName, -2)))
			{
                $hookName = substr($hookName, 0, -2);
			}
			else
			{
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

// Send a Private Message to a user  (Copied from MyBB 1.7)
function send_pm($pm, $fromid=0, $admin_override=false)
{
	global $mybb;

	if(!$mybb->settings['mysupport_notifications'] || !$mybb->settings['enablepms'] || !is_array($pm))
	{
		return false;
	}

	if(!$pm['subject'] || !$pm['message'] || (!$pm['receivepms'] && !$admin_override))
	{
		return false;
	}

	global $lang, $db, $session;

	$lang->load((defined('IN_ADMINCP') ? '../' : '').'messages');

	static $pmhandler = null;

	if($pmhandler === null)
	{
		require_once MYBB_ROOT."inc/datahandlers/pm.php";
	
		$pmhandler = new \PMDataHandler();
	}

	// Build our final PM array
	$pm = array(
		'subject'		=> $pm['subject'],
		'message'		=> $pm['message'],
		'icon'			=> -1,
		'fromid'		=> ($fromid == 0 ? (int)$mybb->user['uid'] : ($fromid < 0 ? 0 : $fromid)),
		'toid'			=> array($pm['touid']),
		'bccid'			=> array(),
		'do'			=> '',
		'pmid'			=> '',
		'saveasdraft'	=> 0,
		'options'	=> array(
			'signature'			=> 0,
			'disablesmilies'	=> 0,
			'savecopy'			=> 0,
			'readreceipt'		=> 0
		)
	);

	if(isset($mybb->session))
	{
		$pm['ipaddress'] = $mybb->session->packedip;
	}

	// Admin override
	$pmhandler->admin_override = (int)$admin_override;

	$pmhandler->set_data($pm);

	if($pmhandler->validate_pm())
	{
		$pmhandler->insert_pm();

		return true;
	}

	return false;
}

function send_alert($tid, $uid, $author=0)
{
	global $lang, $mybb, $alertType, $db;

	\MySupport\Core\load_language();

	if(!($mybb->settings['mysupport_notifications'] && class_exists('MybbStuff_MyAlerts_AlertTypeManager')))
	{
		return false;
	}

	$alertType = \MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('mysupport_thread');

	if(!$alertType)
	{
		return false;
	}

	$query = $db->simple_select('alerts', 'id', "object_id='{$tid}' AND uid='{$uid}' AND unread=1 AND alert_type_id='{$alertType->getId()}'");

	if($db->fetch_field($query, 'id'))
	{
		return false;
	}

	if($alertType->getEnabled())
	{
		$alert = new \MybbStuff_MyAlerts_Entity_Alert();

		$alert->setType($alertType)->setUserId($uid)->setExtraDetails([
			'type'       => $alertType->getId()
		]);

		if($tid)
		{
			$alert->setObjectId($tid);
		}

		if($author)
		{
			$alert->setFromUserId($author);
		}

		\MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
	}
}

function _cache($what = "")
{
	global $db, $cache;

	$old_cache = $cache->read("mysupport");
	$new_cache = array();

	if($what == "version" || !$what)
	{
		$new_cache['version'] = MYSUPPORT_VERSION;
	}
	else
	{
		$new_cache['version'] = $old_cache['version'];
	}

	if($what == "priorities" || !$what)
	{
		$query = $db->simple_select("mysupport", "mid, name, description, extra", "type = 'priority'");
		$new_cache['priorities'] = array();
		while($priority = $db->fetch_array($query))
		{
			$new_cache['priorities'][$priority['mid']] = $priority;
		}
	}
	else
	{
		$new_cache['priorities'] = $old_cache['priorities'];
	}

	if($what == "deniedreasons" || !$what)
	{
		$query = $db->simple_select("mysupport", "mid, name, description", "type = 'deniedreason'");
		$new_cache['deniedreasons'] = array();
		while($deniedreason = $db->fetch_array($query))
		{
			$new_cache['deniedreasons'][$deniedreason['mid']] = $deniedreason;
		}
	}
	else
	{
		$new_cache['deniedreasons'] = $old_cache['deniedreasons'];
	}

	$cache->update("mysupport", $new_cache);
}