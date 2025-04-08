<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Model;
// No direct access.
defined('_JEXEC') or die;



use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\CMS\Event\Model;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\Database\DatabaseDriver;
/**
 * Membersdelegate model.
 *
 * @since  1.0.0
 */
class MembersdelegateModel extends AdminModel
{
	/**
	 * @var    string  The prefix to use with controller messages.
	 *
	 * @since  1.0.0
	 */
	protected $text_prefix = 'COM_BIE_MEMBERS';

	/**
	 * @var    string  Alias to manage history control
	 *
	 * @since  1.0.0
	 */
	public $typeAlias = 'com_bie_members.membersdelegate';

	/**
	 * @var    null  Item data
	 *
	 * @since  1.0.0
	 */
	protected $item = null;

	
	

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since   1.0.0
	 */
	public function getTable($type = 'Membersdelegate', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		$form = $this->loadForm(
								'com_bie_members.membersdelegate', 
								'membersdelegate',
								array(
									'control' => 'jform',
									'load_data' => $loadData 
								)
							);

		

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.0.0
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_bie_members.edit.membersdelegate.data', array());

		if (empty($data))
		{
			if ($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;
			

			$array = array();

			foreach ((array) $data->prefix as $value)
			{
				if (!is_array($value))
				{
					$array[] = $value;
				}
			}
			if(!empty($array)){

			$data->prefix = $array;
			}
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	
public function getItem($pk = null)
{
	$app = Factory::getApplication();
	$input = $app->getInput();
	$layout = $input->get('layout');
	$pk = !empty($pk) ? $pk : (int) $this->getState('delegate.id');
	$item = null;

	if ($layout === 'denounce') {
		$item = $this->getCurrentDelegateItem($pk);
	} else {
		$item = parent::getItem($pk);

		if ($item && $layout === 'reannounce') {
			$item->start_date = null;
		}
	}

	return $item;
}
public function getCurrentDelegateItem($pk = null)
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get('DatabaseDriver');

	$query = $db->getQuery(true)
		->select('*')
		->from($db->quoteName('civicrm_delegates_current'))
		->where($db->quoteName('id') . ' = :id')
		->bind(':id', (int) $pk, \PDO::PARAM_INT);

	$db->setQuery($query);

	return $db->loadObject();
}

	/**
	 * Method to duplicate an Membersdelegate
	 *
	 * @param   array  &$pks  An array of primary key IDs.
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws  Exception
	 */
	public function duplicate(&$pks)
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();
        $dispatcher = $this->getDispatcher();

		if (!$user->authorise('core.create', 'com_bie_members'))
		{
			throw new \Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		$context    = $this->option . '.' . $this->name;

		PluginHelper::importPlugin($this->events_map['save']);

		$table = $this->getTable();

		foreach ($pks as $pk)
		{
			
				if ($table->load($pk, true))
				{
					$table->id = 0;

					if (!$table->check())
					{
						throw new \Exception($table->getError());
					}
					
				if (!empty($table->gender))
				{
					if (is_array($table->gender))
					{
						$table->gender = implode(',', $table->gender);
					}
				}
				else
				{
					$table->gender = '';
				}

				if (!empty($table->organisation))
				{
					if (is_array($table->organisation))
					{
						$table->organisation = implode(',', $table->organisation);
					}
				}
				else
				{
					$table->organisation = '';
				}

				if (!empty($table->group))
				{
					if (is_array($table->group))
					{
						$table->group = implode(',', $table->group);
					}
				}
				else
				{
					$table->group = '';
				}

				if (!empty($table->preferred_language))
				{
					if (is_array($table->preferred_language))
					{
						$table->preferred_language = implode(',', $table->preferred_language);
					}
				}
				else
				{
					$table->preferred_language = '';
				}

				if (!empty($table->country))
				{
					if (is_array($table->country))
					{
						$table->country = implode(',', $table->country);
					}
				}
				else
				{
					$table->country = '';
				}


					// Create the before save event.
					$beforeSaveEvent = AbstractEvent::create(
						$this->event_before_save,
						[
							'context' => $context,
							'subject' => $table,
							'isNew'   => true,
							'data'    => $table,
						]
					);

					// Trigger the before save event.
					$dispatchResult = Factory::getApplication()->getDispatcher()->dispatch($this->event_before_save, $beforeSaveEvent);

					// Check if dispatch result is an array and handle accordingly
					$result = isset($dispatchResult['result']) ? $dispatchResult['result'] : [];

					// Proceed with your logic
					if (in_array(false, $result, true) || !$table->store()) {
						throw new \Exception($table->getError());
					}

					// Trigger the after save event.
					Factory::getApplication()->getDispatcher()->dispatch(
						$this->event_after_save,
						AbstractEvent::create(
							$this->event_after_save,
							[
								'context'    => $context,
								'subject'    => $table,
								'isNew'      => true,
								'data'       => $table,
							]
						)
					);			
				}
				else
				{
					throw new \Exception($table->getError());
				}
			
		}

		// Clean cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   Table  $table  Table Object
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function prepareTable($table): void
	{
		if (empty($table->id))
		{
			if ($table->ordering === '' || $table->ordering === null)
			{
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true)
					->select('MAX(ordering)')
					->from($db->quoteName('civicrm_delegates'));
	
				$db->setQuery($query);
				$max = (int) $db->loadResult();
	
				$table->ordering = $max + 1;
			}
		}
	}


	function denounce(array $data): bool
{
    $app = Factory::getApplication();
    $db = Factory::getContainer()->get('DatabaseDriver');

    // Initialize CiviCRM API with path to civicrm.settings.php
    \Civi::initialize();
    \Civi::apiKernel()->setApiParams([
        'conf_path' => JPATH_BASE . '/components/com_civicrm'
    ]);

    // Get current user
    $civicrm_user = Bie_membersUtils::getCiviCRMUser();

    $params = [
        'id' => $data['mid'],
        'contact_id' => $data['id'],
        'membership_type_id' => 2,
        'status_id' => 6,
        'is_override' => 1,
        'end_date' => Bie_membersUtils::isodateformat($data['end_date']),
        'userId' => $civicrm_user->contact_id,
    ];

    try {
        $result = civicrm_api3('Membership', 'create', $params);

        if (!empty($result['id']) || !empty($result['values'])) {
            Bie_membersUtils::setUserEnabledField(1, $data['id']);
            $app->enqueueMessage(Text::sprintf(
                'COM_BIE_MEMBERS_EDIT_DELEGATE_DENOUNCE_SUCCESS',
                $data['first_name'] . ' ' . $data['last_name']
            ), 'success');
        } else {
            $app->enqueueMessage('Membership: Unknown error', 'error');
        }
    } catch (Exception $e) {
        $app->enqueueMessage('Membership: ' . $e->getMessage(), 'error');
    }

    return true;
}


function reannounce(array $data): bool
{
    $app = Factory::getApplication();
    $db = Factory::getContainer()->get('DatabaseDriver');

    // Initialize CiviCRM if not already initialized
    \Civi::initialize();
    \Civi::apiKernel()->setApiParams([
        'conf_path' => JPATH_BASE . '/components/com_civicrm'
    ]);

    // Get the current CiviCRM user
    $civicrm_user = Bie_membersUtils::getCiviCRMUser();

    $params = [
        'contact_id' => $data['id'],
        'membership_type_id' => 2,
        'status_id' => 2,
        'is_override' => 1,
        'start_date' => Bie_membersUtils::isodateformat($data['start_date']),
        'join_date' => Bie_membersUtils::isodateformat($data['start_date']),
        'userId' => $civicrm_user->contact_id,
    ];

    try {
        $result = civicrm_api3('Membership', 'create', $params);

        if (!empty($result['id']) || !empty($result['values'])) {
            Bie_membersUtils::setUserEnabledField(0, $data['id']);
            $app->enqueueMessage(Text::sprintf(
                'COM_BIE_MEMBERS_EDIT_DELEGATE_REANNOUNCE_SUCCESS',
                $data['first_name'] . ' ' . $data['last_name']
            ), 'success');
        } else {
            $app->enqueueMessage('Membership: Unknown error', 'error');
        }
    } catch (Exception $e) {
        $app->enqueueMessage('Membership: ' . $e->getMessage(), 'error');
    }

    return true;
}

/*
public function denounce(array $data): bool
{
    $application = Factory::getApplication();
    $db = Factory::getContainer()->get('DatabaseDriver');

    // Initialize CiviCRM API
    $api = new \civicrm_api3([
        'conf_path' => JPATH_BASE . '/components/com_civicrm',
    ]);

    $civicrm_user = Bie_membersUtils::getCiviCRMUser();

    $params = [
        'id'                  => $data['mid'],
        'contact_id'          => $data['id'],
        'membership_type_id'  => 2,
        'status_id'           => 6,
        'is_override'         => 1,
        'end_date'            => Bie_membersUtils::isodateformat($data['end_date']),
        'userId'              => $civicrm_user->contact_id,
    ];

    try {
        $result = $api->Membership->Create($params);

        if ($result) {
            Bie_membersUtils::setUserEnabledField(1, $data['id']);
            $application->enqueueMessage(
                Text::sprintf(
                    'COM_BIE_MEMBERS_EDIT_DELEGATE_DENOUNCE_SUCCESS',
                    $data['first_name'] . ' ' . $data['last_name']
                ),
                'success'
            );
        }
    } catch (\Exception $e) {
        $application->enqueueMessage('Membership Error: ' . $e->getMessage(), 'error');
    }

    return true;
}
	*/
}
