<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

 namespace Combiemembers\Component\Bie_members\Administrator\View\Membership;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Uri\Uri;

/**
 * View class for editing a Membership.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
	protected $state;
	protected $item;
	protected $form;

	/**
	 * Display the view
	 *
	 * @param   string|null  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function display($tpl = null): void
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	protected function addToolbar(): void
	{
		$app   = Factory::getApplication();
		$user  = $app->getIdentity();
		$input = $app->getInput();

		$input->set('hidemainmenu', true);

		$isNew = ($this->item->id == 0);

		$checkedOut = !empty($this->item->checked_out)
			&& $this->item->checked_out != 0
			&& $this->item->checked_out != $user->id;

		ToolbarHelper::title(Text::_('COM_BIE_MEMBERS_EDIT_MEMBERSHIP'), 'user');

		if (!$checkedOut && ($user->authorise('core.edit', 'com_bie_members') || $user->authorise('core.create', 'com_bie_members')))
		{
			ToolbarHelper::apply('membership.save');
		}

		ToolbarHelper::cancel('membership.cancel', 'JTOOLBAR_CLOSE');
	}
}
