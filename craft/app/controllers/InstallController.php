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
 * Class InstallController
 *
 * @package craft.app.controllers
 */
class InstallController extends BaseController
{
	protected $allowAnonymous = true;

	/**
	 * Init
	 *
	 * @throws HttpException
	 */
	public function init()
	{
		// Return a 404 if Craft is already installed
		if (!craft()->config->get('devMode') && craft()->isInstalled())
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Index action
	 */
	public function actionIndex()
	{
		craft()->runController('templates/requirementscheck');

		// Guess the site name based on the server name
		$server = craft()->request->getServerName();
		$words = preg_split('/[\-_\.]+/', $server);
		array_pop($words);
		$vars['defaultSiteName'] = implode(' ', array_map('ucfirst', $words));
		$vars['defaultSiteUrl'] = 'http://'.$server;

		$this->renderTemplate('_special/install', $vars);
	}

	/**
	 * Validates the user account credentials
	 */
	public function actionValidateAccount()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$accountSettings = new AccountSettingsModel();
		$username = craft()->request->getPost('username');
		if (!$username)
		{
			$username = craft()->request->getPost('email');
		}

		$accountSettings->username = $username;
		$accountSettings->email = craft()->request->getPost('email');
		$accountSettings->password = craft()->request->getPost('password');

		if ($accountSettings->validate())
		{
			$return['validates'] = true;
		}
		else
		{
			$return['errors'] = $accountSettings->getErrors();
		}

		$this->returnJson($return);
	}

	/**
	 * Validates the site settings
	 */
	public function actionValidateSite()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$siteSettings = new SiteSettingsModel();
		$siteSettings->siteName = craft()->request->getPost('siteName');
		$siteSettings->siteUrl = craft()->request->getPost('siteUrl');

		if ($siteSettings->validate())
		{
			$return['validates'] = true;
		}
		else
		{
			$return['errors'] = $siteSettings->getErrors();
		}

		$this->returnJson($return);
	}

	/**
	 * Install action
	 */
	public function actionInstall()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		// Run the installer
		$username = craft()->request->getPost('username');

		if (!$username)
		{
			$username = craft()->request->getPost('email');
		}

		$inputs['username']   = $username;
		$inputs['email']      = craft()->request->getPost('email');
		$inputs['password']   = craft()->request->getPost('password');
		$inputs['siteName']   = craft()->request->getPost('siteName');
		$inputs['siteUrl']    = craft()->request->getPost('siteUrl');
		$inputs['locale'  ]   = craft()->request->getPost('locale');

		craft()->install->run($inputs);

		$return = array('success' => true);
		$this->returnJson($return);
	}
}
