<?php
/**
 * @package    Com_Bie_members
 * @version    CVS: 1.0.0
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Access\Access;
use Joomla\CMS\User\User;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\CMS\Versioning\VersionableTableTrait;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\Database\DatabaseDriver;

class IndividualTable extends Table
{
	public function __construct(DatabaseDriver $db)
{
    $this->typeAlias = 'com_bie_members.individual';
    parent::__construct('civicrm_contact', 'id', $db);
}


	public function bind($array, $ignore = '')
	{
		// Normalize prefix_id
		if (isset($array['prefix_id'])) {
			if (is_array($array['prefix_id'])) {
				$array['prefix_id'] = implode(',', $array['prefix_id']);
			} elseif (str_contains($array['prefix_id'], ',')) {
				$array['prefix_id'] = explode(',', $array['prefix_id']);
			} elseif (empty($array['prefix_id'])) {
				$array['prefix_id'] = '';
			}
		}

		// Normalize employer_id
		if (!empty($array['employer_id'])) {
			if (is_array($array['employer_id'])) {
				$array['employer_id'] = implode(',', $array['employer_id']);
			} elseif (str_contains($array['employer_id'], ',')) {
				$array['employer_id'] = explode(',', $array['employer_id']);
			}
		} else {
			$array['employer_id'] = '';
		}

		$user = Factory::getApplication()->getIdentity();

		if (empty($array['id'])) {
			$array['created_by'] = $user->id;
			$array['modified_by'] = $user->id;
		}

		// Convert params/metadata arrays to strings
		foreach (['params', 'metadata'] as $field) {
			if (isset($array[$field]) && is_array($array[$field])) {
				$registry = new Registry($array[$field]);
				$array[$field] = (string) $registry;
			}
		}

		if (!$user->authorise('core.admin', 'com_bie_members.individual.' . ($array['id'] ?? 0))) {
			$actions = Access::getActionsFromFile(JPATH_ADMINISTRATOR . '/components/com_bie_members/access.xml', "/access/section[@name='individual']/");
			$default = Access::getAssetRules('com_bie_members.individual.' . ($array['id'] ?? 0))->getData();
			$rules = [];

			foreach ($actions as $action) {
				$rules[$action->name] = $default[$action->name] ?? [];
			}

			$array['rules'] = $this->convertRulesToArray($rules);
		}

		if (isset($array['rules']) && is_array($array['rules'])) {
			$this->setRules($array['rules']);
		}

		return parent::bind($array, $ignore);
	}

	protected function convertRulesToArray(array $rules): array
	{
		$converted = [];
		foreach ($rules as $action => $data) {
			$converted[$action] = array_map(fn($v) => (bool) $v, (array) $data);
		}
		return $converted;
	}

	public function check(): bool
	{
		if (property_exists($this, 'ordering') && (int) $this->id === 0) {
			$this->ordering = self::getNextOrder();
		}
		return parent::check();
	}

	public function publish($pks = null, $state = 1, $userId = 0): bool
	{
		$k = $this->_tbl_key;
		ArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state  = (int) $state;

		if (empty($pks)) {
			$pks = [$this->$k];
			if (!$this->$k) {
				throw new \RuntimeException(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'), 500);
			}
		}

		$where = $k . '=' . implode(' OR ' . $k . '=', $pks);
		$checkin = '';

		if (property_exists($this, 'checked_out') && property_exists($this, 'checked_out_time')) {
			$checkin = ' AND (checked_out = 0 OR checked_out = ' . $userId . ')';
		}

		$this->_db->setQuery(
			"UPDATE `$this->_tbl` SET `state` = $state WHERE ($where) $checkin"
		)->execute();

		if ($checkin && count($pks) === $this->_db->getAffectedRows()) {
			foreach ($pks as $pk) {
				$this->checkin($pk);
			}
		}

		if (in_array($this->$k, $pks, true)) {
			$this->state = $state;
		}

		return true;
	}

	protected function _getAssetName(): string
	{
		return 'com_bie_members.individual.' . (int) $this->{$this->_tbl_key};
	}

	protected function _getAssetParentId(Table $table = null, $id = null)
	{
		$asset = Table::getInstance('Asset');
		$asset->loadByName('com_bie_members');
		return $asset->id ?: $asset->getRootId();
	}

	public function delete($pk = null): bool
	{
		$this->load($pk);
		return parent::delete($pk);
	}
}