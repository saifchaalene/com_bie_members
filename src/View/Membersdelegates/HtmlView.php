<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\View\Membersdelegates;
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\MVC\View\HtmlView as JViewLegacy;
use Joomla\CMS\HTML\HTMLHelper;
use \Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersHelper;
use Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersUtils;


/**
 * View class for a list of Membersdelegates.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView


{
	protected $items;
	protected $pagination;
	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');

		if (!$this->state->get('filter.type')) {
			$this->state->set('filter.type', 1);
		}

		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		$model = $this->getModel();
		$this->totalItems = $model->countTotalItems();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		Bie_membersHelper::addSubmenu('membersdelegates');
		$this->addToolbar();
		$this->sidebar = HTMLHelper::_('sidebar.render');
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 */
	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = Bie_membersHelper::getActions();

		ToolbarHelper::title(Text::_('COM_BIE_MEMBERS_TITLE_DELEGATES'), 'users');

		if (Bie_membersUtils::allowEditDelegates()) {
			ToolbarHelper::custom('membersdelegate.add', 'new', '', 'New Delegate / New Person', false);
			ToolbarHelper::addNew('membership.edit', 'New Delegate/ Existing Person');
			ToolbarHelper::custom('membersdelegates.denounce', 'exit', '', 'Denounce Delegate', false);
			ToolbarHelper::custom('membersdelegates.reannounce', 'enter', '', 'Re-Announce Former Delegate', false);
			ToolbarHelper::divider();
		}

		ToolbarHelper::custom('membersdelegates.exportxls', 'download', '', 'Export Data (xlsx)', false);
		ToolbarHelper::custom('membersdelegates.outlook', 'download', '', 'Outlook (csv)', false);
		ToolbarHelper::custom('membersdelegates.labels', 'address', '', 'Labels', false);
		ToolbarHelper::custom('membersdelegates.labelscountry', 'address', '', 'Labels w/ Country', false);
		ToolbarHelper::divider();
		ToolbarHelper::custom('membersdelegates.deletebowsuser', 'user', '', 'Delete BOWS User', true);

		if (Bie_membersUtils::isAdmin()) {
			ToolbarHelper::custom('membersdelegates.syncBows', 'user', '', 'Sync From BOWS', false);
		}

		if ($canDo->get('core.admin')) {
			ToolbarHelper::preferences('com_bie_members');
		}

		HTMLHelper::_('sidebar.setAction', 'index.php?option=com_bie_members&view=membersdelegates');
	}

	/**
	 * Method to order fields
	 *
	 * @return array
	 */
	protected function getSortFields()
	{
		return array(
			'a.country' => Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_COUNTRY'),
			'a.first_name' => Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_FIRST_NAME'),
			'a.last_name' => Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_LAST_NAME'),
			'a.type' => Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_TYPE'),
			'a.ordering' => Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_ORDERING'),
		);
	}

	/**
	 * Check if a state is set
	 *
	 * @param   string  $state
	 *
	 * @return mixed
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
