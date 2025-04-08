<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Table;
// No direct access
defined('_JEXEC') or die;

use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Table\Table as Table;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use \Joomla\Database\DatabaseDriver;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\CMS\Filesystem\File;
use \Joomla\Registry\Registry;
use \Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersHelper;
use \Joomla\CMS\Helper\ContentHelper;


/**
 * Membersdelegate table
 *
 * @since 1.0.0
 */
class MembersdelegateTable extends Table implements VersionableTableInterface, TaggableTableInterface
{
	use TaggableTableTrait;

	/**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     * @since  4.0.0
     */
    protected $_supportNullValue = true;

	
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = 'com_bie_members.membersdelegate';
		parent::__construct('#__bie_members_delegates', 'id', $db);
		$this->setColumnAlias('published', 'state');
		
	}

	/**
	 * Get the type alias for the history table
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   1.0.0
	 */
	public function getTypeAlias()
	{
		return $this->typeAlias;
	}

	/**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param   array  $array   Named array
	 * @param   mixed  $ignore  Optional array or list of parameters to ignore
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     Table:bind
	 * @since   1.0.0
	 * @throws  \InvalidArgumentException
	 */
	public function bind($array, $ignore = '')
	{
		$date = Factory::getDate();
		$task = Factory::getApplication()->input->get('task');
		$user = Factory::getApplication()->getIdentity();
		
		$input = Factory::getApplication()->input;
		$task = $input->getString('task', '');

		if ($array['id'] == 0 && empty($array['created_by']))
		{
			$array['created_by'] = Factory::getUser()->id;
		}

		if ($array['id'] == 0 && empty($array['modified_by']))
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		if ($task == 'apply' || $task == 'save')
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		// Support for multiple field: prefix
		if (isset($array['prefix']))
		{
			if (is_array($array['prefix']))
			{
				$array['prefix'] = implode(',',$array['prefix']);
			}
			elseif (strpos($array['prefix'], ',') != false)
			{
				$array['prefix'] = explode(',',$array['prefix']);
			}
			elseif (strlen($array['prefix']) == 0)
			{
				$array['prefix'] = '';
			}
		}
		else
		{
			$array['prefix'] = '';
		}

		// Support for multiple field: gender
		if (isset($array['gender']))
		{
			if (is_array($array['gender']))
			{
				$array['gender'] = implode(',',$array['gender']);
			}
			elseif (strpos($array['gender'], ',') != false)
			{
				$array['gender'] = explode(',',$array['gender']);
			}
			elseif (strlen($array['gender']) == 0)
			{
				$array['gender'] = '';
			}
		}
		else
		{
			$array['gender'] = '';
		}

		// Support for multiple field: organisation
		if (isset($array['organisation']))
		{
			if (is_array($array['organisation']))
			{
				$array['organisation'] = implode(',',$array['organisation']);
			}
			elseif (strpos($array['organisation'], ',') != false)
			{
				$array['organisation'] = explode(',',$array['organisation']);
			}
			elseif (strlen($array['organisation']) == 0)
			{
				$array['organisation'] = '';
			}
		}
		else
		{
			$array['organisation'] = '';
		}

		// Support for multiple field: group
		if (isset($array['group']))
		{
			if (is_array($array['group']))
			{
				$array['group'] = implode(',',$array['group']);
			}
			elseif (strpos($array['group'], ',') != false)
			{
				$array['group'] = explode(',',$array['group']);
			}
			elseif (strlen($array['group']) == 0)
			{
				$array['group'] = '';
			}
		}
		else
		{
			$array['group'] = '';
		}

		// Support for multiple field: preferred_language
		if (isset($array['preferred_language']))
		{
			if (is_array($array['preferred_language']))
			{
				$array['preferred_language'] = implode(',',$array['preferred_language']);
			}
			elseif (strpos($array['preferred_language'], ',') != false)
			{
				$array['preferred_language'] = explode(',',$array['preferred_language']);
			}
			elseif (strlen($array['preferred_language']) == 0)
			{
				$array['preferred_language'] = '';
			}
		}
		else
		{
			$array['preferred_language'] = '';
		}

		// Support for multiple field: country
		if (isset($array['country']))
		{
			if (is_array($array['country']))
			{
				$array['country'] = implode(',',$array['country']);
			}
			elseif (strpos($array['country'], ',') != false)
			{
				$array['country'] = explode(',',$array['country']);
			}
			elseif (strlen($array['country']) == 0)
			{
				$array['country'] = '';
			}
		}
		else
		{
			$array['country'] = '';
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

		if (!$user->authorise('core.admin', 'com_bie_members.membersdelegate.' . $array['id']))
		{
			$actions         = Access::getActionsFromFile(
				JPATH_ADMINISTRATOR . '/components/com_bie_members/access.xml',
				"/access/section[@name='membersdelegate']/"
			);
			$default_actions = Access::getAssetRules('com_bie_members.membersdelegate.' . $array['id'])->getData();
			$array_jaccess   = array();

			foreach ($actions as $action)
			{
				if (key_exists($action->name, $default_actions))
				{
					$array_jaccess[$action->name] = $default_actions[$action->name];
				}
			}

			$array['rules'] = $this->JAccessRulestoArray($array_jaccess);
		}

		// Bind the rules for ACL where supported.
		if (isset($array['rules']) && is_array($array['rules']))
		{
			$this->setRules($array['rules']);
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Method to store a row in the database from the Table instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be updated with the instance property values.
	 * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0.0
	 */
	public function store($updateNulls = true)
	{
		
		if(!($this->id))
		{
			if (empty($this->date_of_announce))
			{
			$this->date_of_announce = NULL;
			}
		}
		else{
			if (empty($this->date_of_announce))
			{
			$this->date_of_announce = NULL;
			}
		}
		
		return parent::store($updateNulls);
	}

	/**
	 * This function convert an array of Access objects into an rules array.
	 *
	 * @param   array  $jaccessrules  An array of Access objects.
	 *
	 * @return  array
	 */
	private function JAccessRulestoArray($jaccessrules)
	{
		$rules = array();

		foreach ($jaccessrules as $action => $jaccess)
		{
			$actions = array();

			if ($jaccess)
			{
				foreach ($jaccess->getData() as $group => $allow)
				{
					$actions[$group] = ((bool)$allow);
				}
			}

			$rules[$action] = $actions;
		}

		return $rules;
	}

	/**
	 * Overloaded check function
	 *
	 * @return bool
	 */
	public function check()
	{
		// If there is an ordering column and this is a new row then get the next ordering value
		if (property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}
		
		

		return parent::check();
	}

	/**
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @return string The asset name
	 *
	 * @see Table::_getAssetName
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return $this->typeAlias . '.' . (int) $this->$k;
	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Table   $table  Table name
	 * @param   integer  $id     Id
	 *
	 * @see Table::_getAssetParentId
	 *
	 * @return mixed The id on success, false on failure.
	 */
	protected function _getAssetParentId($table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetParent = Table::getInstance('Asset');

		// Default: if no asset-parent can be found we take the global asset
		$assetParentId = $assetParent->getRootId();

		// The item has the component as asset-parent
		$assetParent->loadByName('com_bie_members');

		// Return the found asset-parent-id
		if ($assetParent->id)
		{
			$assetParentId = $assetParent->id;
		}

		return $assetParentId;
	}

	//XXX_CUSTOM_TABLE_FUNCTION

	
    /**
     * Delete a record by id
     *
     * @param   mixed  $pk  Primary key value to delete. Optional
     *
     * @return bool
     */
    public function delete($pk = null)
    {
        $this->load($pk);
        $result = parent::delete($pk);
        
        return $result;
    }
}
