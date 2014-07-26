<?php
namespace Craft;

/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */

/**
 * Plugin template variable.
 *
 * @package craft.app.validators
 */
class PluginVariable extends BaseComponentTypeVariable
{
	/**
	 * Returns the plugin’s display name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->component->getName();
	}

	/**
	 * Returns the plugin's version.
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return $this->component->getVersion();
	}

	/**
	 * Returns the plugin developer's name.
	 *
	 * @return string
	 */
	public function getDeveloper()
	{
		return $this->component->getDeveloper();
	}

	/**
	 * Returns the plugin developer's URL.
	 *
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return $this->component->getDeveloperUrl();
	}

	/**
	 * Returns the URL to the plugin's settings in the CP.
	 *
	 * @return string|null
	 */
	public function getSettingsUrl()
	{
		// Is this plugin managing its own settings?
		$url = $this->component->getSettingsUrl();

		if (!$url)
		{
			// Check to see if they're using getSettingsHtml(), etc.
			if ($this->component->getSettingsHtml())
			{
				$url = 'settings/plugins/'.mb_strtolower($this->component->getClassHandle());
			}
		}

		if ($url)
		{
			return UrlHelper::getCpUrl($url);
		}
	}

	/**
	 * Returns whether the plugin is installed.
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		return $this->component->isInstalled;
	}

	/**
	 * Returns if the plugin is currently enabled or not.
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->component->isEnabled;
	}
}
