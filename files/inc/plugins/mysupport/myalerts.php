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

namespace MySupport\MyAlerts;

function getAvailableLocations()
{
	$directory = MYSUPPORT_ROOT.'/myalerts/';

	return array_map(
		'basename',
		glob($directory.'*', \GLOB_ONLYDIR)
	);
}

function getInstalledLocations()
{
	global $cache;

	return $cache->read('mysupport')['MyAlertLocationsInstalled'] ?? [];
}

function isLocationAlertTypePresent($locationName)
{
	if(\MySupport\MyAlerts\MyAlertsIsIntegrable())
	{
		$alertTypeManager = \MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		return $alertTypeManager->getByCode('mysupport_' . $locationName) !== null;
	}

	return false;
}

function installLocation($name)
{
	global $db, $cache;

	$cacheEntry = $cache->read('mysupport');

	if(!in_array($name, $cacheEntry['MyAlertLocationsInstalled']))
	{
		$cacheEntry['MyAlertLocationsInstalled'][] = $name;

		$cache->update('mysupport', $cacheEntry);
	}

	if(!\MySupport\MyAlerts\isLocationAlertTypePresent($name))
	{
		$alertTypeManager = \MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		$alertType = new \MybbStuff_MyAlerts_Entity_AlertType();

		$alertType->setCode('mysupport_' . $name);

		$alertTypeManager->add($alertType);
	}
}

function uninstallLocation(string $name)
{
	global $db, $cache;

	// remove MyAlerts type
	$alertTypeManager = \MybbStuff_MyAlerts_AlertTypeManager::getInstance();

	$alertTypeManager->deleteByCode('dvz_mentions_' . $name);

	// remove datacache value
	$cacheEntry = $cache->read('dvz_mentions');
	$key = array_search($name, $cacheEntry['MyAlertLocationsInstalled']);

	if ($key !== false) {
		unset($cacheEntry['MyAlertLocationsInstalled'][$key]);
		$cache->update('dvz_mentions', $cacheEntry);
	}
}

function initMyalerts()
{
	defined('MYBBSTUFF_CORE_PATH') or define('MYBBSTUFF_CORE_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/Core/');

	defined('MYALERTS_PLUGIN_PATH') or define('MYALERTS_PLUGIN_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/MyAlerts');

	require_once MYBBSTUFF_CORE_PATH . 'ClassLoader.php';

	$classLoader = new \MybbStuff_Core_ClassLoader();

	$classLoader->registerNamespace('MybbStuff_MyAlerts', [MYALERTS_PLUGIN_PATH . '/src']);

	$classLoader->register();
}

function initLocations()
{
	foreach(\MySupport\MyAlerts\getInstalledLocations() as $locationName)
	{
		require_once MYSUPPORT_ROOT.'/myalerts/' . $locationName . '/init.php';
	}
}

function registerMyalertsFormatters()
{
	global $mybb, $lang, $formatterManager;

	$formatterManager = \MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

	//$formatterManager or $formatterManager = \MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);

	foreach(\MySupport\MyAlerts\getInstalledLocations() as $locationName)
	{
		$class = 'MybbStuff_MyAlerts_Formatter_OUGCPrivateThreads_' . ucfirst($locationName) . 'Formatter';

		$formatter = new $class($mybb, $lang, 'mysupport_' . $locationName);

		$formatterManager->registerFormatter($formatter);
	}
}

function MyAlertsIsIntegrable()
{
	global $cache;

	static $status;

	if(!$status)
	{
		$status = false;

		$plugins = $cache->read('plugins');

		if(!empty($plugins['active']) && in_array('myalerts', $plugins['active']))
		{
			if($euantor_plugins = $cache->read('euantor_plugins'))
			{
				if(isset($euantor_plugins['myalerts']['version']))
				{
					$version = explode('.', $euantor_plugins['myalerts']['version']);

					if($version[0] == '2' && $version[1] == '0')
					{
						$status = true;
					}
				}
			}
		}
	}

	return $status;
}