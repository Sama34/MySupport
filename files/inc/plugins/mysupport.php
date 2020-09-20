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
 
// Die if IN_MYBB is not defined, for security reasons.
if(!defined('IN_MYBB'))
{
	die('This file cannot be accessed directly.');
}

define('MYSUPPORT_ROOT', MYBB_ROOT . 'inc/plugins/mysupport');

require_once MYSUPPORT_ROOT.'/core.php';

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Add our hooks
if(defined('IN_ADMINCP'))
{
	require_once MYSUPPORT_ROOT.'/admin.php';

	require_once MYSUPPORT_ROOT.'/admin_hooks.php';

	\MySupport\Core\addHooks('MySupport\AdminHooks');
}
else
{
	require_once MYSUPPORT_ROOT.'/forum_hooks.php';

	\MySupport\Core\addHooks('MySupport\ForumHooks');
}
/*
require MYSUPPORT_ROOT.'/myalerts.php';

if(\MySupport\MyAlerts\MyAlertsIsIntegrable())
{
	\MySupport\MyAlerts\initMyalerts();

	\MySupport\MyAlerts\initLocations();
}*/

define('MYSUPPORT_VERSION', '1.8.0');

define('MYSUPPORT_VERSION_CODE', 1800);

// Plugin API
function mysupport_info()
{
	return \MySupport\Admin\_info();
}

// Activate the plugin.
function mysupport_activate()
{
	\MySupport\Admin\_activate();
}

// Deactivate the plugin.
function mysupport_deactivate()
{
	\MySupport\Admin\_deactivate();
}

// Install the plugin.
function mysupport_install()
{
	\MySupport\Admin\_install();
}

// Check if installed.
function mysupport_is_installed()
{
	return \MySupport\Admin\_is_installed();
}

// Unnstall the plugin.
function mysupport_uninstall()
{
	\MySupport\Admin\_uninstall();
}

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com ), 1.62
if(!function_exists('control_object'))
{
	function control_object(&$obj, $code)
	{
		static $cnt = 0;
		$newname = '_objcont_'.(++$cnt);
		$objserial = serialize($obj);
		$classname = get_class($obj);
		$checkstr = 'O:'.strlen($classname).':"'.$classname.'":';
		$checkstr_len = strlen($checkstr);
		if(substr($objserial, 0, $checkstr_len) == $checkstr)
		{
			$vars = array();
			// grab resources/object etc, stripping scope info from keys
			foreach((array)$obj as $k => $v)
			{
				if($p = strrpos($k, "\0"))
				{
					$k = substr($k, $p+1);
				}
				$vars[$k] = $v;
			}
			if(!empty($vars))
			{
				$code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
			}
			eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
			$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
			if(!empty($vars))
			{
				$obj->___setvars($vars);
			}
		}
		// else not a valid object or PHP serialize has changed
	}
}