<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

 namespace Combiemembers\Component\Bie_members\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseDriver;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\PluginHelper;

class MembershipModel extends AdminModel
{
	protected $text_prefix = 'COM_BIE_MEMBERS';
	public $typeAlias = 'com_bie_members.Membership';
	protected $item = null;

	public function getTable($type = 'Individual', $prefix = 'Bie_membersTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm(
			'com_bie_members.membership',
			'membership',
			array('control' => 'jform', 'load_data' => $loadData)
		);

		return $form ?: false;
	}

	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_bie_members.edit.membership.data', array());

		if (empty($data)) {
			$this->item = $this->item ?: $this->getItem();
			$data = $this->item;
			$array = [];

			foreach ((array) $data->prefix_id as $value) {
				if (!is_array($value)) {
					$array[] = $value;
				}
			}

			$data->prefix_id = implode(',', $array);
		}

		return $data;
	}

	public function duplicate(&$pks)
	{
		$user = Factory::getApplication()->getIdentity();

		if (!$user->authorise('core.create', 'com_bie_members')) {
			throw new \Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		PluginHelper::importPlugin($this->events_map['save']);
		$table = $this->getTable();

		foreach ($pks as $pk) {
			if ($table->load($pk, true)) {
				$table->id = 0;

				if (!$table->check()) {
					throw new \Exception($table->getError());
				}

				$table->employer_id = is_array($table->employer_id) ? implode(',', $table->employer_id) : ($table->employer_id ?? '');

				if (!$table->store()) {
					throw new \Exception($table->getError());
				}
			} else {
				throw new \Exception($table->getError());
			}
		}

		$this->cleanCache();
		return true;
	}

	public function cancel($data)
	{
		Factory::getApplication()->setUserState('com_bie_members.edit.membership.data', []);
		return true;
	}

	public function save($data)
	{
		$app = Factory::getApplication();
		$contact_id = ArrayHelper::getValue($data, 'contact_id', 0);
		$start_date = ArrayHelper::getValue($data, 'start_date', '');

		if (empty($contact_id)) {
			$this->setError('You have to select an Individual');
			return false;
		}

		if (empty($start_date)) {
			$this->setError('You have to select a Start Date');
			return false;
		}

		\Civi::initialize();
		\Civi::apiKernel()->setApiParams(['conf_path' => JPATH_BASE . '/components/com_civicrm']);
		$civicrm_user = Bie_membersUtils::getCiviCRMUser();

		$params = [
			'contact_id' => $contact_id,
			'membership_type_id' => 2,
			'status_id' => 2,
			'is_override' => 1,
			'start_date' => Bie_membersUtils::isodateformat($start_date),
			'join_date' => Bie_membersUtils::isodateformat($start_date),
			'userId' => $civicrm_user->contact_id,
		];

		$result = civicrm_api3('Membership', 'create', $params);
		if (empty($result['id']) && empty($result['values'])) {
			$this->setError('Membership creation failed');
			return false;
		}

		$groupParams = [
			'contact_id' => $contact_id,
			'group_id' => 2,
			'status' => 'added',
		];

		$result = civicrm_api3('GroupContact', 'create', $groupParams);
		if (empty($result['id']) && empty($result['values'])) {
			$this->setError('GroupContact creation failed');
			return false;
		}

		if (Bie_membersUtils::createJoomlaUserByID($contact_id)) {
			$app->enqueueMessage(Text::_('COM_BIE_DELEGATE_LBL_DELEGATE_BOWS_SUCCESS'), 'success');
		} else {
			$app->enqueueMessage(Text::_('COM_BIE_DELEGATE_LBL_DELEGATE_BOWS_ERROR'), 'error');
		}

		$url = 'index.php?option=com_civicrm&task=civicrm/contact/view&reset=1&tmpl=popup&cid=' . (int) $contact_id;
		$view_url = '<a href="javascript:displayPopup(\'' . $url . '\');"> here </a>';
		$msg = 'Successfully created contact. Click <b>' . $view_url . '</b> to view the contact';
		$app->enqueueMessage($msg, 'notice');

		return true;
	}

	public function getContact($contact_id)
	{
		\Civi::initialize();
		\Civi::apiKernel()->setApiParams(['conf_path' => JPATH_BASE . '/components/com_civicrm']);
		$params = ['contact_id' => $contact_id];
		$result = civicrm_api3('Contact', 'get', $params);
		return $result['values'][0] ?? false;
	}

	public function removeOldGroups($contact_id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->update($db->quoteName('civicrm_group_contact'))
			->set($db->quoteName('status') . ' = ' . $db->quote('Removed'))
			->where($db->quoteName('contact_id') . ' = ' . $db->quote($contact_id));

		$db->setQuery($query);
		$db->execute();
	}
}
