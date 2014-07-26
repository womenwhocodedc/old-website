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

craft()->requireEdition(Craft::Client);

/**
 * Class EntryRevisionsController
 *
 * @package craft.app.controllers
 */
class EntryRevisionsController extends BaseEntriesController
{
	/**
	 * Saves a draft, or creates a new one.
	 */
	public function actionSaveDraft()
	{
		$this->requirePostRequest();

		$draftId = craft()->request->getPost('draftId');

		if ($draftId)
		{
			$draft = craft()->entryRevisions->getDraftById($draftId);

			if (!$draft)
			{
				throw new Exception(Craft::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
			}
		}
		else
		{
			$draft = new EntryDraftModel();
			$draft->id        = craft()->request->getPost('entryId');
			$draft->sectionId = craft()->request->getRequiredPost('sectionId');
			$draft->creatorId = craft()->userSession->getUser()->id;
			$draft->locale    = craft()->request->getPost('locale', craft()->i18n->getPrimarySiteLocaleId());
		}

		// Make sure they have permission to be editing this
		$this->enforceEditEntryPermissions($draft);

		$this->_setDraftAttributesFromPost($draft);

		if (!$draft->id)
		{
			// Attempt to create a new entry

			// Manually validate 'title' since ElementsService will just give it a title automatically
			$fields = array('title');
			$content = $draft->getContent();
			$content->setRequiredFields($fields);

			if ($content->validate($fields))
			{
				$draftEnabled = $draft->enabled;
				$draft->enabled = false;

				craft()->entries->saveEntry($draft);

				$draft->enabled = $draftEnabled;
			}
			else
			{
				$draft->addErrors($content->getErrors());
			}
		}

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
		$draft->setContentFromPost($fieldsLocation);

		if ($draft->id && craft()->entryRevisions->saveDraft($draft))
		{
			craft()->userSession->setNotice(Craft::t('Draft saved.'));

			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{entryId}') !== false)
			{
				craft()->deprecator->log('EntryRevisionsController::saveDraft():entryId_redirect', 'The {entryId} token within the ‘redirect’ param on entryRevisions/saveDraft requests has been deprecated. Use {id} instead.');
				$_POST['redirect'] = str_replace('{entryId}', '{id}', $_POST['redirect']);
			}

			$this->redirectToPostedUrl($draft);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save draft.'));

			// Send the draft back to the template
			craft()->urlManager->setRouteVariables(array(
				'entry' => $draft
			));
		}
	}

	/**
	 * Renames a draft.
	 */
	public function actionUpdateDraftMeta()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$draftId = craft()->request->getRequiredPost('draftId');
		$name = craft()->request->getRequiredPost('name');

		$draft = craft()->entryRevisions->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
		}

		if ($draft->creatorId != craft()->userSession->getUser()->id)
		{
			// Make sure they have permission to be doing this
			craft()->userSession->requirePermission('editPeerEntryDrafts:'.$draft->sectionId);
		}

		$draft->name = $name;
		$draft->revisionNotes = craft()->request->getPost('notes');

		if (craft()->entryRevisions->saveDraft($draft, false))
		{
			$this->returnJson(array('success' => true));
		}
		else
		{
			$this->returnErrorJson($draft->getError('name'));
		}
	}

	/**
	 * Deletes a draft.
	 */
	public function actionDeleteDraft()
	{
		$this->requirePostRequest();

		$draftId = craft()->request->getPost('draftId');
		$draft = craft()->entryRevisions->getDraftById($draftId);

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
		}

		if ($draft->creatorId != craft()->userSession->getUser()->id)
		{
			craft()->userSession->requirePermission('deletePeerEntryDrafts:'.$draft->sectionId);
		}

		craft()->entryRevisions->deleteDraft($draft);

		$this->redirectToPostedUrl();
	}

	/**
	 * Publish a draft.
	 */
	public function actionPublishDraft()
	{
		$this->requirePostRequest();

		$draftId = craft()->request->getPost('draftId');
		$draft = craft()->entryRevisions->getDraftById($draftId);
		$userId = craft()->userSession->getUser()->id;

		if (!$draft)
		{
			throw new Exception(Craft::t('No draft exists with the ID “{id}”', array('id' => $draftId)));
		}

		// Permission enforcement
		$entry = craft()->entries->getEntryById($draft->id, $draft->locale);

		if (!$entry)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $draft->id)));
		}

		$this->enforceEditEntryPermissions($entry);
		$userSessionService = craft()->userSession;

		// Is this another user's entry (and it's not a Single)?
		if (
			$entry->authorId != $userSessionService->getUser()->id &&
			$entry->getSection()->type != SectionType::Single
		)
		{
			if ($entry->enabled)
			{
				// Make sure they have permission to make live changes to those
				$userSessionService->requirePermission('publishPeerEntries:'.$entry->sectionId);
			}
		}

		// Is this another user's draft?
		if ($draft->creatorId != $userId)
		{
			craft()->userSession->requirePermission('publishPeerEntryDrafts:'.$entry->sectionId);
		}

		// Populate the main draft attributes
		$this->_setDraftAttributesFromPost($draft);

		// Even more permission enforcement
		if ($draft->enabled)
		{
			$userSessionService->requirePermission('publishEntries:'.$entry->sectionId);
		}

		// Populate the field content
		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
		$draft->setContentFromPost($fieldsLocation);

		// Publish the draft (finally!)
		if (craft()->entryRevisions->publishDraft($draft))
		{
			craft()->userSession->setNotice(Craft::t('Draft published.'));

			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{entryId}') !== false)
			{
				craft()->deprecator->log('EntryRevisionsController::publishDraft():entryId_redirect', 'The {entryId} token within the ‘redirect’ param on entryRevisions/publishDraft requests has been deprecated. Use {id} instead.');
				$_POST['redirect'] = str_replace('{entryId}', '{id}', $_POST['redirect']);
			}

			$this->redirectToPostedUrl($draft);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t publish draft.'));

			// Send the draft back to the template
			craft()->urlManager->setRouteVariables(array(
				'entry' => $draft
			));
		}
	}

	/**
	 * Reverts an entry to a version.
	 */
	public function actionRevertEntryToVersion()
	{
		$this->requirePostRequest();

		$versionId = craft()->request->getPost('versionId');
		$version = craft()->entryRevisions->getVersionById($versionId);
		$userId = craft()->userSession->getUser()->id;

		if (!$version)
		{
			throw new Exception(Craft::t('No version exists with the ID “{id}”', array('id' => $versionId)));
		}

		// Permission enforcement
		$entry = craft()->entries->getEntryById($version->id, $version->locale);

		if (!$entry)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $version->id)));
		}

		$this->enforceEditEntryPermissions($entry);
		$userSessionService = craft()->userSession;

		// Is this another user's entry (and it's not a Single)?
		if (
			$entry->authorId != $userSessionService->getUser()->id &&
			$entry->getSection()->type != SectionType::Single
		)
		{
			if ($entry->enabled)
			{
				// Make sure they have permission to make live changes to those
				$userSessionService->requirePermission('publishPeerEntries:'.$entry->sectionId);
			}
		}

		if ($entry->enabled)
		{
			$userSessionService->requirePermission('publishEntries:'.$entry->sectionId);
		}

		// Revent to the version
		if (craft()->entryRevisions->revertEntryToVersion($version))
		{
			craft()->userSession->setNotice(Craft::t('Entry reverted to past version.'));
			$this->redirectToPostedUrl($version);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t revert entry to past version.'));

			// Send the version back to the template
			craft()->urlManager->setRouteVariables(array(
				'entry' => $version
			));
		}
	}

	/**
	 * Sets a draft's attributes from the post data.
	 *
	 * @access private
	 * @param EntryDraftModel $draft
	 */
	private function _setDraftAttributesFromPost(EntryDraftModel $draft)
	{
		$draft->typeId     = craft()->request->getPost('typeId');
		$draft->slug       = craft()->request->getPost('slug');
		$draft->postDate   = craft()->request->getPost('postDate');
		$draft->expiryDate = craft()->request->getPost('expiryDate');
		$draft->enabled    = (bool) craft()->request->getPost('enabled');
		$draft->authorId   = craft()->request->getPost('author');

		$draft->getContent()->title = craft()->request->getPost('title');
	}
}
