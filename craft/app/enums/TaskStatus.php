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
 * Class TaskStatus
 *
 * @abstract
 * @package craft.app.enums
 */
class TaskStatus
{
	const Pending = 'pending';
	const Running = 'running';
	const Error   = 'error';
}
