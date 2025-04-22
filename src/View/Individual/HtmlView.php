<?php

namespace Combiemembers\Component\Bie_members\Administrator\View\Individual;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\User\User;
use Joomla\CMS\Application\CMSApplication;

/**
 * View to edit Individual
 *
 * @since  1.6
 */
class HtmlView extends BaseHtmlView
{
	protected $state;
	protected $item;
	protected $form;

	/**
	 * Display the view
	 *
	 * @param string|null $tpl Template name
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function display($tpl = null)
{
	$this->state = $this->get('State');
	$this->item  = $this->get('Item');
	$this->form  = $this->get('Form');

	$document = Factory::getDocument();

	if ($document instanceof HtmlDocument) {
		$stateJson = json_encode($this->state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$itemJson = json_encode($this->item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$formJson = json_encode($this->form, JSON_PARTIAL_OUTPUT_ON_ERROR); // form can't always be fully stringified

		$document->addScriptDeclaration("
			console.groupCollapsed('👀 Joomla Individual View Debug');
			console.log('✅ View Loaded: com_bie_members > individual');
			console.log('🔹 State:', $stateJson);
			console.log('🔸 Item:', $itemJson);
			console.log('📋 Form:', '$formJson');
			console.groupEnd();
		");
	}

	// Handle errors
	if (count($errors = $this->get('Errors'))) {
		$errMsg = implode('\\n', $errors);
		$document->addScriptDeclaration("console.error('❌ View Errors: $errMsg');");
		throw new \Exception(implode("\n", $errors));
	}

	$this->addToolbar();
	parent::display($tpl);
}


	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 */
	protected function addToolbar(): void
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user    = Factory::getUser();
		$isNew   = ($this->item->id ?? 0) === 0;
		$checkedOut = isset($this->item->checked_out) && $this->item->checked_out != 0 && $this->item->checked_out != $user->get('id');

		$canDo = \Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersHelper::getActions();

		ToolbarHelper::title(Text::_('COM_PAVILIONS_EXPOINDIVIDUAL_NEW_DELEGATE'), 'user');

		if (!$checkedOut && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
			ToolbarHelper::apply('individual.apply');
			ToolbarHelper::save('individual.save');
		}

		if (!$checkedOut && $canDo->get('core.create')) {
			ToolbarHelper::save2new('individual.save2new');
		}

		if ($isNew) {
			ToolbarHelper::cancel('individual.cancel');
		} else {
			ToolbarHelper::cancel('individual.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
