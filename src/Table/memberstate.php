<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis
 * @copyright  2025
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersUtils;

/**
 * Memberstate Table class
 *
 * @since  1.0.0
 */
class MemberstateTable extends Table implements VersionableTableInterface, TaggableTableInterface
{
	use TaggableTableTrait;

	protected $_supportNullValue = true;

	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = 'com_bie_members.memberstate';
		parent::__construct('#__civicrm_member_states', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}

	public function getTypeAlias(): string
	{
		return $this->typeAlias;
	}

	public function bind($array, $ignore = '')
	{
		$input = Factory::getApplication()->input;
		$user  = Factory::getApplication()->getIdentity();

		if (($array['id'] ?? 0) == 0 && empty($array['created_by']))
		{
			$array['created_by'] = $user->id;
		}

		if (($array['id'] ?? 0) == 0 && empty($array['modified_by']))
		{
			$array['modified_by'] = $user->id;
		}

		if (in_array($input->getCmd('task', ''), ['apply', 'save']))
		{
			$array['modified_by'] = $user->id;
		}

		if (($array['start_date'] ?? '') === '0000-00-00')
		{
			$array['start_date'] = null;
		}

		if (($array['end_date'] ?? '') === '0000-00-00')
		{
			$array['end_date'] = null;
		}

		if (isset($array['params']) && is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		if (isset($array['metadata']) && is_array($array['metadata']))
		{
			$registry = new Registry;
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

		if (!$user->authorise('core.admin', 'com_bie_members.memberstate.' . ($array['id'] ?? 0)))
		{
			$actions         = Access::getActionsFromFile(
				JPATH_ADMINISTRATOR . '/components/com_bie_members/access.xml',
				"/access/section[@name='memberstate']/"
			);
			$default_actions = Access::getAssetRules('com_bie_members.memberstate.' . ($array['id'] ?? 0))->getData();
			$array_jaccess   = [];

			foreach ($actions as $action)
			{
				if (isset($default_actions[$action->name]))
				{
					$array_jaccess[$action->name] = $default_actions[$action->name];
				}
			}

			$array['rules'] = $this->JAccessRulestoArray($array_jaccess);
		}

		if (isset($array['rules']) && is_array($array['rules']))
		{
			$this->setRules($array['rules']);
		}

		return parent::bind($array, $ignore);
	}

	private function JAccessRulestoArray(array $jaccessrules): array
	{
		$rules = [];

		foreach ($jaccessrules as $action => $jaccess)
		{
			$actions = [];

			if ($jaccess)
			{
				foreach ($jaccess->getData() as $group => $allow)
				{
					$actions[$group] = (bool) $allow;
				}
			}

			$rules[$action] = $actions;
		}

		return $rules;
	}

	public function check(): bool
	{
		if (property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}

		return parent::check();
	}

	/**
	 * Custom publish method to sync with external CiviCRM custom table
	 */
	public function publish($pks = null, $state = 1, $userId = 0): bool
	{
		ArrayHelper::toInteger($pks);

		if (empty($pks))
		{
			throw new \RuntimeException(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'), 500);
		}

		$db    = $this->getDbo();
		$state = (int) $state;
		$k     = 'entity_id';

		$where = $k . '=' . implode(' OR ' . $k . '=', $pks);

		// Update related CiviCRM custom field
		$db->setQuery(
			'UPDATE `civicrm_value_organisaztion_name_en_1`' .
			' SET `right_to_vote_17` = ' . $state .
			' WHERE (' . $where . ')'
		)->execute();

		// Log changes if utility is available
		Bie_membersUtils::logVotingRightChanges($pks, $state);

		return true;
	}

	protected function _getAssetName(): string
	{
		$k = $this->_tbl_key;

		return $this->typeAlias . '.' . (int) $this->$k;
	}

	protected function _getAssetParentId($table = null, $id = null)
	{
		$assetParent = Table::getInstance('Asset');
		$assetParentId = $assetParent->getRootId();

		if ($assetParent->loadByName('com_bie_members') && $assetParent->id)
		{
			$assetParentId = $assetParent->id;
		}

		return $assetParentId;
	}

	public function delete($pk = null): bool
	{
		$this->load($pk);
		return parent::delete($pk);
	}
}
