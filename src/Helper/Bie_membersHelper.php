<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Helper;
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Object\CMSObject;
use Joomla\CMS\HTML\Helpers\Sidebar;


/**
 * Bie_members helper.
 *
 * @since  1.0.0
 */
class Bie_membersHelper
{
/**
	 * Configure the sidebar menu.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return void
	 */
	public static function addSubmenu(string $vName = ''): void
	{
		Sidebar::addEntry(
			Text::_('COM_BIE_MEMBERS_TITLE_DELEGATES'),
			'index.php?option=com_bie_members&view=delegates',
			$vName === 'delegates'
		);

		Sidebar::addEntry(
			Text::_('COM_BIE_MEMBERS_TITLE_MEMBERSTATES'),
			'index.php?option=com_bie_members&view=memberstates',
			$vName === 'memberstates'
		);

		Sidebar::addEntry(
			Text::_('COM_BIE_MEMBERS_TITLE_COMMITTEES'),
			'index.php?option=com_bie_members&view=committees',
			$vName === 'committees'
		);
	}

	/**
	 * Gets the files attached to an item.
	 *
	 * @param   int     $pk     The item's id.
	 * @param   string  $table  The table's name.
	 * @param   string  $field  The field's name.
	 *
	 * @return  array
	 */
	public static function getFiles(int $pk, string $table, string $field): array
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query
			->select($field)
			->from($db->quoteName($table))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $pk, \PDO::PARAM_INT);

		$db->setQuery($query);

		$result = $db->loadResult();

		return !empty($result) ? explode(',', $result) : [];
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return CMSObject
	 */
	public static function getActions(): CMSObject
	{
		$user = Factory::getApplication()->getIdentity();
		$result = new CMSObject;

		$assetName = 'com_bie_members';

		$actions = [
			'core.admin', 'core.manage', 'core.create',
			'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		];

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}
}

