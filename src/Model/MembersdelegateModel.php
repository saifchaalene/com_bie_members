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

use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Document\HtmlDocument;
class MembersdelegateModel extends AdminModel
{
	protected $text_prefix = 'COM_BIE_MEMBERS';
	public $typeAlias = 'com_bie_members.Membersdelegate';
	protected $item = null;

	public function getTable($type = 'Delegate', $prefix = 'Bie_membersTable', $config = array())
	{
		$table = parent::getTable($type, $prefix, $config);


		return $table;
	}


	
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm(
			'com_bie_members.membersdelegate',
			'membersdelegate',
			['control' => 'jform', 'load_data' => $loadData]
		);
	
		if (empty($form)) {
			Factory::getApplication()->enqueueMessage('❌ Failed to load form.', 'error');
			return false;
		}
	
		$jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	
		$doc = Factory::getDocument();
	
		if ($doc instanceof HtmlDocument) {
			$doc->addScriptDeclaration("console.log(' getForm data:', $jsonData);");
		}
	
		Factory::getApplication()->enqueueMessage(' Form loaded successfully.', 'message');
		return $form;
	}
	
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_bie_members.edit.membersdelegate.data', array());

		if (empty($data)) {
			if ($this->item === null) {
				$this->item = $this->getItem();
			}
			$data = $this->item;
			
			$array = array();
			foreach ((array) $data->prefix as $value) {
				if (!is_array($value)) {
					$array[] = $value;
				}
			}
			if (!empty($array)) {
				$data->prefix = $array;
			}
		}

		return $data;
	}

	public function getItem($pk = null)
	{
		$app = Factory::getApplication();
		$input = $app->getInput();
		$layout = $input->get('layout');
		$pk = !empty($pk) ? $pk : (int) $this->getState('delegate.id');
		$item = null;

		if ($layout === 'denounce') {
			$item = $this->getCurrentDelegateItem($pk);
			$app->enqueueMessage("\u2139 Loaded current delegate for denounce.", 'message');
		} else {
			$item = parent::getItem($pk);
			if ($item && $layout === 'reannounce') {
				$item->start_date = null;
				$app->enqueueMessage("\u2139 Reannounce layout detected: start_date reset.", 'message');
			}
		}
		return $item;
	}

	public function getCurrentDelegateItem($pk = null)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('civicrm_delegates_current'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', (int) $pk, \PDO::PARAM_INT);
		$db->setQuery($query);
		return $db->loadObject();
	}

	public function duplicate(&$pks)
{
    $app  = Factory::getApplication();
    $user = $app->getIdentity();

    if (!$user->authorise('core.create', 'com_bie_members')) {
        $app->enqueueMessage('❌ Not authorized to duplicate.', 'error');
        throw new \Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
    }

    $table = $this->getTable();
    $doc = Factory::getDocument();

    foreach ($pks as $pk) {
        if ($table->load($pk, true)) {
            // Log the original record to the console
            $originalData = (array) $table;
            $json = json_encode($originalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if ($doc instanceof HtmlDocument) {
                $doc->addScriptDeclaration("console.log('🧾 Original record for duplication (ID $pk):', $json);");
            }

            $table->id = 0;

            if (!$table->check()) {
                $app->enqueueMessage('⚠️ Table check failed: ' . $table->getError(), 'warning');
                throw new \Exception($table->getError());
            }

            if (!$table->store()) {
                $app->enqueueMessage('❌ Store failed: ' . $table->getError(), 'error');
                throw new \Exception($table->getError());
            }

            $app->enqueueMessage('✅ Record duplicated successfully.', 'message');

            // Log the new ID after duplication
            $newId = (int) $table->id;
            if ($doc instanceof HtmlDocument) {
                $doc->addScriptDeclaration("console.log('🆕 New duplicated record ID:', $newId);");
            }
        } else {
            $app->enqueueMessage("❌ Could not load item with ID $pk.", 'error');
            throw new \Exception($table->getError());
        }
    }

    $this->cleanCache();
    $app->enqueueMessage('🧹 Cache cleaned.', 'info');

    // Final message in console
    if ($doc instanceof HtmlDocument) {
        $doc->addScriptDeclaration("console.log('🎉 Duplication process complete.');");
    }

    return true;
}

	protected function prepareTable($table): void
	{
		if (empty($table->id) && ($table->ordering === '' || $table->ordering === null)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true)
				->select('MAX(ordering)')
				->from($db->quoteName('civicrm_delegates'));
			$db->setQuery($query);
			$max = (int) $db->loadResult();
			$table->ordering = $max + 1;
			Factory::getApplication()->enqueueMessage("\u2139 Table ordering prepared.", 'message');
		}
	}

	function denounce(array $data): bool
	{
		$app = Factory::getApplication();
		\Civi::initialize();
		\Civi::apiKernel()->setApiParams(['conf_path' => JPATH_BASE . '/components/com_civicrm']);
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
				$app->enqueueMessage(Text::sprintf('COM_BIE_MEMBERS_EDIT_DELEGATE_DENOUNCE_SUCCESS', $data['first_name'] . ' ' . $data['last_name']), 'success');
			} else {
				$app->enqueueMessage('Membership: Unknown error', 'error');
			}
		} catch (\Exception $e) {
			$app->enqueueMessage('Membership: ' . $e->getMessage(), 'error');
		}

		return true;
	}

	function reannounce(array $data): bool
	{
		$app = Factory::getApplication();
		\Civi::initialize();
		\Civi::apiKernel()->setApiParams(['conf_path' => JPATH_BASE . '/components/com_civicrm']);
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
				$app->enqueueMessage(Text::sprintf('COM_BIE_MEMBERS_EDIT_DELEGATE_REANNOUNCE_SUCCESS', $data['first_name'] . ' ' . $data['last_name']), 'success');
			} else {
				$app->enqueueMessage('Membership: Unknown error', 'error');
			}
		} catch (\Exception $e) {
			$app->enqueueMessage('Membership: ' . $e->getMessage(), 'error');
		}

		return true;
	}
}