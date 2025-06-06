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
use Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersUtils;

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
		$user = Factory::getUser();

		// Access checks.
		if (!$user->authorise('core.create', 'com_bie_members'))
		{
			throw new Exception(JText::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		$dispatcher = EventDispatcher::getInstance();
		$context    = $this->option . '.' . $this->name;

		// Include the plugins for the save events.
		JPluginHelper::importPlugin($this->events_map['save']);

		$table = $this->getTable();

		foreach ($pks as $pk)
		{
			if ($table->load($pk, true))
			{
				// Reset the id to create a new record.
				$table->id = 0;

				if (!$table->check())
				{
					throw new Exception($table->getError());
				}
				

				// Trigger the before save event.
				$result = $dispatcher->trigger($this->event_before_save, array($context, &$table, true));

				if (in_array(false, $result, true) || !$table->store())
				{
					throw new Exception($table->getError());
				}

				// Trigger the after save event.
				$dispatcher->trigger($this->event_after_save, array($context, &$table, true));
			}
			else
			{
				throw new Exception($table->getError());
			}
		}

		// Clean cache
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
	
		if (!defined('CIVICRM_SETTINGS_PATH')) {
			define('CIVICRM_SETTINGS_PATH', JPATH_ADMINISTRATOR . '/components/com_civicrm/civicrm.settings.php');
		}
		if (file_exists(CIVICRM_SETTINGS_PATH)) {
			require_once CIVICRM_SETTINGS_PATH;
		}
		if (class_exists('CRM_Core_Config')) {
			\CRM_Core_Config::singleton();
		}
		\CRM_Core_Config::singleton();
	
		$params = [
			'contact_id' => $contact_id,
			'membership_type_id' => 2,
			'status_id' => 2,
			'is_override' => 1,
			'start_date' => Bie_membersUtils::isodateformat($start_date),
			'join_date' => Bie_membersUtils::isodateformat($start_date),
		];
	
		try {
			$result = civicrm_api3('Membership', 'create', $params);
		} catch (\CiviCRM_API3_Exception $e) {
			$this->setError('Membership creation failed: ' . $e->getMessage());
			return false;
		}
	
		// Add to group
		try {
			$groupParams = [
				'contact_id' => $contact_id,
				'group_id' => 2,
				'status' => 'Added',
			];
			civicrm_api3('GroupContact', 'create', $groupParams);
		} catch (\CiviCRM_API3_Exception $e) {
			$this->setError('GroupContact creation failed: ' . $e->getMessage());
			return false;
		}
	
		$app->enqueueMessage(Text::_('COM_BIE_MEMBERSHIP_SAVE_SUCCESS'), 'success');
	
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
