<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\View\Membersdelegate;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Document\HtmlDocument;
use Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersHelper;

/**
 * View class for a single Membersdelegate.
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
	 * @param   string  $tpl  Template name
	 * @return  void
	 * @throws  \Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		$input  = Factory::getApplication()->input;
		$layout = (string) $input->get('layout');

		// Redirect with error message if conditions are not met
		if ($this->item->id && $layout === 'reannounce' && $this->item->type != 2)
		{
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_BIE_MEMBERS_EDIT_DELEGATE_IS_NOT_FORMER_DELEGATE', $this->item->first_name . ' ' . $this->item->last_name),
				'error'
			);
			Factory::getApplication()->redirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
			return;
		}

		if ($this->item->id && $layout === 'denounce' && $this->item->type != 1)
		{
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_BIE_MEMBERS_EDIT_DELEGATE_IS_NOT_DELEGATE', $this->item->first_name . ' ' . $this->item->last_name),
				'error'
			);
			Factory::getApplication()->redirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
			return;
		}

		// Set field attributes if needed
		if ($this->item->id && $layout === 'denounce')
		{
			$this->form->setFieldAttribute('end_date', 'readonly', 'false');
		}

		if ($this->item->id && $layout === 'reannounce')
		{
			$this->form->setFieldAttribute('start_date', 'readonly', 'false');
		}

		// Debugging: log delegate data in JS console
		$document = Factory::getDocument();
		$itemJson = json_encode($this->item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($document instanceof HtmlDocument)
		{
			$document->addScriptDeclaration("console.log('Delegate Item:', $itemJson);");
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);
		$layout = Factory::getApplication()->input->get('layout');

		$user    = Factory::getUser();
		$isNew   = ($this->item->id == 0);
		$canDo   = Bie_membersHelper::getActions();

		$checkedOut = isset($this->item->checked_out) &&
			$this->item->checked_out != 0 &&
			$this->item->checked_out != $user->get('id');

		ToolbarHelper::title(Text::_('COM_BIE_MEMBERS_TITLE_DELEGATE'), 'delegate.png');

		if ($isNew)
		{
			ToolbarHelper::cancel('delegate.cancel', 'JTOOLBAR_CLOSE');
		}
		else
		{
			if ($layout === 'denounce')
			{
				ToolbarHelper::save('delegate.denounce', 'JTOOLBAR_SAVE');
			}
			elseif ($layout === 'reannounce')
			{
				ToolbarHelper::save('delegate.reannounce', 'JTOOLBAR_SAVE');
			}

			ToolbarHelper::cancel('delegate.cancel', 'JTOOLBAR_CANCEL');
		}
	}
}
