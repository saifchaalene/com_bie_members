<?php

namespace Combiemembers\Component\Bie_members\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\EventDispatcherInterface;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersUtils;

class IndividualModel extends AdminModel
{
	protected $text_prefix = 'COM_BIE_MEMBERS';
	public $typeAlias = 'com_bie_members.individual';
	protected $item = null;
	public function getTable($type = 'Individual', $prefix = 'Bie_membersTable', $config = [])
{
	try {
		$table = parent::getTable($type, $prefix, $config);

		if (!$table) {
			throw new \RuntimeException("Table class not found: {$prefix}{$type}");
		}

		return $table;
	} catch (\Throwable $e) {
		$msg = 'getTable() ERROR: ' . $e->getMessage();
		\Joomla\CMS\Factory::getApplication()->enqueueMessage($msg, 'error');
		\Joomla\CMS\Factory::getDocument()->addScriptDeclaration("console.error(" . json_encode($msg) . ");");
		return false;
	}
}

	

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_bie_members.individual',
			'individual',
			['control' => 'jform', 'load_data' => $loadData]
		);
		return $form ?: false;
	}

	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_bie_members.edit.individual.data', []);
		if (empty($data)) {
			$this->item = $this->item ?: $this->getItem();
			$data = $this->item;

			if (isset($data->prefix_id)) {
				$data->prefix_id = is_array($data->prefix_id) ? implode(',', $data->prefix_id) : $data->prefix_id;
			}
		}
		return $data;
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);
	
		if (!$item) {
			\Joomla\CMS\Factory::getApplication()->enqueueMessage('getItem() returned null or false', 'error');
		}
	
		return $item;
	}
	

	public function cancel($data)
	{
		Factory::getApplication()->setUserState('com_bie_members.edit.individual.data', []);
		return true;
	}

	public function save($data)
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$session = $app->getSession();

		// Boot CiviCRM
		define('CIVICRM_SETTINGS_PATH', JPATH_ADMINISTRATOR . '/components/com_civicrm/civicrm.settings.php');
		require_once CIVICRM_SETTINGS_PATH;
		require_once JPATH_ADMINISTRATOR . '/components/com_civicrm/civicrm/CRM/Core/Config.php';
		\CRM_Core_Config::singleton();

		// Validation: must select org
		$employerId = (int) ArrayHelper::getValue($data, 'employer_id');
		if (!$employerId) {
			$this->setError(Text::_('COM_BIE_MEMBERS_INDIVIDUAL_NO_ORGANIZATION_SELECTED'));
			return false;
		}

		$org = Bie_membersUtils::getExtraFields($employerId);

		if (!empty($data['street_address']) && empty($data['country_id'])) {
			$this->setError(Text::_('COM_BIE_MEMBERS_ADDRESS_NEEDS_COUNTRY'));
			return false;
		}

		$similar = $this->checkforSimilarities($data);
		if ($similar && empty($data['dups_informed'])) {
			$data['dups_informed'] = 1;
			$app->setUserState('com_bie_members.edit.individual.data', $data);
			$app->enqueueMessage($similar, 'warning');
			$app->redirect(Route::_('index.php?option=com_bie_members&view=individual&layout=edit', false));
			return false;
		}

		try {
			$params = [
				'contact_type' => 'Individual',
				'first_name' => $data['first_name'],
				'last_name' => $data['last_name'],
				'prefix_id' => $data['prefix_id'],
				'job_title' => $data['job_title'],
				'employer_id' => $employerId,
				'gender_id' => $data['gender_id'],
				'preferred_language' => $data['prefered_language'] ?? 'en_US',
				'note' => $data['note'] ?? '',
				'group' => [$data['group_id'] => 1],
			];
			$contact = civicrm_api3('Contact', 'create', $params);
			$contactId = $contact['id'];

			$this->createSubEntities($contactId, $data);

			$app->enqueueMessage(Text::_('COM_BIE_MEMBERS_CONTACT_CREATED'), 'message');
			return true;
		} catch (\Exception $e) {
			$this->setError('CiviCRM Error: ' . $e->getMessage());
			return false;
		}
	}

	protected function createSubEntities($contactId, $data)
{/*
	$fields = [
		'Email' => ['email', 'email2'],
		'Phone' => ['phone', 'mobile_phone'],
		'Website' => ['web', 'facebook', 'twitter'],
	];

	foreach ($fields as $entity => $keys) {
		foreach ($keys as $key) {
			if (!empty($data[$key])) {
				$params = ['contact_id' => $contactId];
				switch ($entity) {
					case 'Email':
						$params['email'] = $data[$key];
						$params['location_type_id'] = ($key === 'email') ? 2 : 4;
						$params['is_primary'] = ($key === 'email') ? 1 : 0;
						break;
					case 'Phone':
						$params['phone'] = $data[$key];
						$params['phone_type_id'] = ($key === 'mobile_phone') ? 2 : 1;
						$params['location_type_id'] = 2;
						$params['is_primary'] = ($key === 'phone') ? 1 : 0;
						break;
					case 'Website':
						$params['url'] = $data[$key];
						switch ($key) {
							case 'web':
								$params['website_type_id'] = 1;
								break;
							case 'facebook':
								$params['website_type_id'] = 3;
								break;
							case 'twitter':
								$params['website_type_id'] = 11;
								break;
						}
						break;
				}
				civicrm_api3($entity, 'create', $params);
			}
		}
	}

	// Address
	if (!empty($data['street_address'])) {
		civicrm_api3('Address', 'create', [
			'contact_id' => $contactId,
			'location_type_id' => 2,
			'city' => $data['city'],
			'postal_code' => $data['postal_code'],
			'country_id' => $data['country_id'],
			'address' => $data['street_address'],
			'supplemental_address_1' => $data['supplemental_address_1'],
			'supplemental_address_2' => $data['supplemental_address_2'],
			'is_primary' => 1,
		]);
	}

	// Membership
	$civiUser = Bie_membersUtils::getCiviCRMUser();
	civicrm_api3('Membership', 'create', [
		'contact_id' => $contactId,
		'membership_type_id' => 2,
		'status_id' => 2,
		'is_override' => 1,
		'start_date' => Bie_membersUtils::isodateformat($data['start_date']),
		'join_date' => Bie_membersUtils::isodateformat($data['start_date']),
		'userId' => $civiUser->contact_id,
	]);
}


		// Address
		if (!empty($data['street_address'])) {
			civicrm_api3('Address', 'create', [
				'contact_id' => $contactId,
				'location_type_id' => 2,
				'city' => $data['city'],
				'postal_code' => $data['postal_code'],
				'country_id' => $data['country_id'],
				'address' => $data['street_address'],
				'supplemental_address_1' => $data['supplemental_address_1'],
				'supplemental_address_2' => $data['supplemental_address_2'],
				'is_primary' => 1,
			]);
		}

		// Membership
		$civiUser = Bie_membersUtils::getCiviCRMUser();
		civicrm_api3('Membership', 'create', [
			'contact_id' => $contactId,
			'membership_type_id' => 2,
			'status_id' => 2,
			'is_override' => 1,
			'start_date' => Bie_membersUtils::isodateformat($data['start_date']),
			'join_date' => Bie_membersUtils::isodateformat($data['start_date']),
			'userId' => $civiUser->contact_id,
		]);*/
	}

	protected function checkforSimilarities($data): string
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['id', 'first_name', 'last_name'])
			->from($db->quoteName('civicrm_contact'))
			->where($db->quoteName('contact_type') . ' = ' . $db->quote('Individual'))
			->where('SOUNDEX(last_name) = SOUNDEX(' . $db->quote($data['last_name']) . ')');

		$results = $db->setQuery($query)->loadObjectList();
		if (count($results)) {
			$links = [];
			foreach ($results as $item) {
				$url = Route::_('index.php?option=com_civicrm&task=civicrm/contact/view&reset=1&tmpl=component&cid=' . $item->id);
				$links[] = '<a href="javascript:displayPopup(\'' . $url . '\')">' . $item->first_name . ' ' . $item->last_name . '</a>';
			}
			return Text::sprintf('COM_BIE_MEMBERS_SIMILAR_CONTACTS_FOUND', implode(', ', $links));
		}
		return '';
	}
}
