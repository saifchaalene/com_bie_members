<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersUtils;

/**
 * Membersdelegates list controller class.
 *
 * @since  1.0.0
 */
class MembersdelegatesController extends AdminController
{
	/**
	 * Method to clone existing Membersdelegates
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	public function duplicate()
	{
		// Check for request forgeries
		$this->checkToken();

		// Get id(s)
		$pks = $this->input->post->get('cid', array(), 'array');

		try
		{
			if (empty($pks))
			{
				throw new \Exception(Text::_('COM_BIE_MEMBERS_NO_ELEMENT_SELECTED'));
			}

			ArrayHelper::toInteger($pks);
			$model = $this->getModel();
			$model->duplicate($pks);
			$this->setMessage(Text::_('COM_BIE_MEMBERS_ITEMS_SUCCESS_DUPLICATED'));
		}
		catch (\Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		$this->setRedirect('index.php?option=com_bie_members&view=membersdelegates');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Optional. Model name
	 * @param   string  $prefix  Optional. Class prefix
	 * @param   array   $config  Optional. Configuration array for model
	 *
	 * @return  object	The Model
	 *
	 * @since   1.0.0
	 */
	public function getModel($name = 'Membersdelegates', $prefix = 'Administrator', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$pks   = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		// Sanitize the input
		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		Factory::getApplication()->close();
	}



	public function exportxls()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		ArrayHelper::toInteger($pks);
		$this->getModel()->exportxls($pks);
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function outlook()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		ArrayHelper::toInteger($pks);
		$this->getModel()->exportToOutlook($pks);
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function labels()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		ArrayHelper::toInteger($pks);
		$this->getModel()->labels($pks);
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function labelscountry()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		ArrayHelper::toInteger($pks);
		$this->getModel()->labels($pks, true);
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function loginmail()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		foreach ($pks as $userid) {
			$user = Bie_membersUtils::getBOWSUser($userid);
			$contact = Bie_membersUtils::getCiviCrmContactByID($userid);

			$delegate = [
				'username'    => $user->username ?? '',
				'bows_id'     => $user->user_id ?? '',
				'email'       => $contact->email ?? '',
				'contact_id'  => $contact->contact_id ?? 0,
				'name'        => trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')),
			];

			$result = Bie_membersUtils::updateBOWSDelegate($delegate, true);

			if (!$result) {
				Factory::getApplication()->enqueueMessage(Text::_('COM_BIE_MEMBERS_DELEGATE_SEND_MAIL_ERROR'), 'warning');
			} else {
				Factory::getApplication()->enqueueMessage(Text::sprintf('COM_BIE_MEMBERS_EDIT_DELEGATE_RESEND_MAIL', $delegate['name'], $delegate['email']), 'message');
			}
		}
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function updatebows()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		foreach ($pks as $userid) {
			$user = Bie_membersUtils::getBOWSUser($userid);
			$contact = Bie_membersUtils::getCiviCrmContactByID($userid);

			$delegate = [
				'username'    => $user->username ?? '',
				'bows_id'     => $user->user_id ?? '',
				'email'       => $contact->email ?? '',
				'contact_id'  => $contact->contact_id ?? 0,
				'name'        => trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')),
			];

			$result = Bie_membersUtils::updateBOWSDelegate($delegate, false);

			$message = $result
				? Text::sprintf('COM_BIE_MEMBERS_EDIT_DELEGATE_UPDATE', $delegate['name'])
				: Text::_('COM_BIE_MEMBERS_DELEGATE_UPDATE_ERROR');

			Factory::getApplication()->enqueueMessage($message, $result ? 'message' : 'warning');
		}
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function initmail()
	{
		Bie_membersUtils::initDelegatesMailingListBOWS();
		Factory::getApplication()->enqueueMessage(Text::_('COM_BIE_MEMBERS_MAILING_LIST_UPDATED'), 'message');
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function syncBows()
	{
		Bie_membersUtils::syncLocalCopyDelegatesBOWSUsers();
		Factory::getApplication()->enqueueMessage(Text::_('COM_BIE_MEMBERS_SYNC_SUCCESS'), 'message');
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function deletebowsuser()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		ArrayHelper::toInteger($pks);
		try {
			if (count($pks) !== 1) {
				throw new \RuntimeException(Text::_('COM_BIE_MEMBERS_EDIT_DELEGATE_CHOOSE_ONE'));
			}
			Bie_membersUtils::deleteBOWSUser($pks[0]);
			Factory::getApplication()->enqueueMessage(Text::_('COM_BIE_MEMBERS_BOWS_USER_DELETED'), 'message');
		} catch (\Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function createbowsuser()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		ArrayHelper::toInteger($pks);

		if (count($pks) !== 1) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_BIE_MEMBERS_EDIT_DELEGATE_CHOOSE_ONE'), 'error');
		} else {
			$userid = $pks[0];
			$user = Bie_membersUtils::getBOWSUser($userid);
			if (isset($user->id)) {
				Factory::getApplication()->enqueueMessage(Text::_('COM_BIE_MEMBERS_ALREADY_USER'), 'message');
			} else {
				if (Bie_membersUtils::createJoomlaUserByID($userid)) {
					Factory::getApplication()->enqueueMessage(Text::_('COM_BIE_MEMBERS_USER_CREATED'), 'message');
				}
			}
		}
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function denounce()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		ArrayHelper::toInteger($pks);
		if (count($pks) !== 1) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_BIE_MEMBERS_EDIT_DELEGATE_CHOOSE_ONE'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_bie_members&view=delegates', false));
		} else {
			$this->setRedirect(Route::_('index.php?option=com_bie_members&view=delegate&layout=denounce&id=' . (int) $pks[0], false));
		}
	}

	public function reannounce()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		ArrayHelper::toInteger($pks);
		if (count($pks) !== 1) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_BIE_MEMBERS_EDIT_FORMER_DELEGATE_CHOOSE_ONE'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_bie_members&view=delegates', false));
		} else {
			$this->setRedirect(Route::_('index.php?option=com_bie_members&view=delegate&layout=reannounce&id=' . (int) $pks[0], false));
		}
	}

	public function subscribeNewsletter()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		$actions = $this->input->post->get('actions', [], 'array');
		$action = $actions['newsletter'] ?? 0;
		ArrayHelper::toInteger($pks);
		if ((int) $action === 1) {
			$this->getModel()->actionOnNewsletter($pks, $action);
		}
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function unsubscribenewsletter()
	{
		$pks = $this->input->post->get('cid', [], 'array');
		$actions = $this->input->post->get('actions', [], 'array');
		$action = $actions['newsletter'] ?? 0;
		ArrayHelper::toInteger($pks);
		if ((int) $action === 2) {
			$this->getModel()->actionOnNewsletter($pks, $action);
		}
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
	}

	public function add()
{
    $this->setRedirect('index.php?option=com_bie_members&view=membersdelegate&layout=edit');
}

}
