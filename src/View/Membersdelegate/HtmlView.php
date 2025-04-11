<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\View\Membersdelegate;
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use \Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersHelper;
use \Joomla\CMS\Language\Text;
use Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersUtils;
use Joomla\CMS\Router\Route;

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
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}
                
                $input = Factory::getApplication()->input;
				$layout = (string)$input->get('layout', '');

                if ($this->item->id && $layout == 'reannounce' && $this->item->type != 2) {

                    Factory::getApplication()->enqueueMessage(Text::sprintf('COM_BIE_MEMBERS_EDIT_DELEGATE_IS_NOT_FORMER_DELEGATE',$this->item->first_name.' '.$this->item->last_name), 'error');
                    Factory::getApplication()->redirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
                    return false;

                }

                if ($this->item->id && $layout == 'denounce' && $this->item->type != 1) {

                    Factory::getApplication()->enqueueMessage(Text::sprintf('COM_BIE_MEMBERS_EDIT_DELEGATE_IS_NOT_DELEGATE',$this->item->first_name.' '.$this->item->last_name), 'error');
                    Factory::getApplication()->redirect(Route::_('index.php?option=com_bie_members&view=membersdelegates', false));
                    return false;

                }
                
                
                if ($this->item->id && $layout == 'denounce') {
                    $this->form->setFieldAttribute('end_date','readonly','false'); 
                }
                if ($this->item->id && $layout == 'reannounce') {
                    $this->form->setFieldAttribute('start_date','readonly','false'); 
                }
                
		$this->addToolbar();
		parent::display($tpl);
	}


	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);
                 $layout = Factory::getApplication()->input->get('layout');

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if (isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = Bie_membersHelper::getActions();

		ToolBarHelper::title(Text::_('COM_BIE_MEMBERS_TITLE_DELEGATE'), 'delegate.png');

		// If not checked out, can save the item.
		if (empty($this->item->id))
		{
			ToolBarHelper::cancel('delegate.cancel', 'JTOOLBAR_CLOSE');
		}
		else
		{
                    if ($layout == 'denounce') {    
                        ToolBarHelper::save('membersdelegate.denounce', 'JTOOLBAR_SAVE');
                    }
                    if ($layout == 'reannounce') {    
                        ToolBarHelper::save('membersdelegate.reannounce', 'JTOOLBAR_SAVE');
                    }
                    
			ToolBarHelper::cancel('membersdelegate.cancel', 'JTOOLBAR_CANCEL');
		}
	}
}
