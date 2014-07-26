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
 * Class EmailerType
 *
 * @abstract
 * @package craft.app.enums
 */
abstract class EmailerType extends BaseEnum
{
	const Php      = 'php';
	const Sendmail = 'sendmail';
	const Smtp     = 'smtp';
	const Pop      = 'pop';
	const Gmail    = 'gmail';
}
