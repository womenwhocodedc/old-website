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
 * Handles structure management tasks.
 *
 * @package craft.app.controllers
 */
class StructuresController extends BaseController
{
	/**
	 * Moves an element within a structure.
	 */
	public function actionMoveElement(array $variables = array())
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$structureId     = craft()->request->getRequiredPost('structureId');
		$elementId       = craft()->request->getRequiredPost('elementId');
		$localeId        = craft()->request->getRequiredPost('locale');
		$parentElementId = craft()->request->getPost('parentId');
		$prevElementId   = craft()->request->getPost('prevId');

		$structure = craft()->structures->getStructureById($structureId);

		// Make sure they have permission to be doing this
		if ($structure->movePermission)
		{
			craft()->userSession->requirePermission($structure->movePermission);
		}

		$element = craft()->elements->getElementById($elementId, null, $localeId);

		if ($prevElementId)
		{
			$prevElement = craft()->elements->getElementById($prevElementId, null, $localeId);
			$success = craft()->structures->moveAfter($structure->id, $element, $prevElement, 'auto', true);
		}
		else if ($parentElementId)
		{
			$parentElement = craft()->elements->getElementById($parentElementId, null, $localeId);
			$success = craft()->structures->prepend($structure->id, $element, $parentElement, 'auto', true);
		}
		else
		{
			$success = craft()->structures->prependToRoot($structure->id, $element, 'auto', true);
		}

		$this->returnJson(array(
			'success' => $success
		));
	}
}
