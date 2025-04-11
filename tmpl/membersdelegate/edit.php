<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');
	HTMLHelper::_('jquery.framework');
	HTMLHelper::_('bootstrap.tooltip');
	

$document = Factory::getDocument();
$document->addStyleSheet(Uri::root() . 'media/com_bie_members/css/form.css');
?>
<script type="text/javascript">
	js = jQuery.noConflict();
	js(document).ready(function () {
		
	});

	Joomla.submitbutton = function (task) {
		if (task == 'delegate.cancel') {
			Joomla.submitform(task, document.getElementById('delegate-form'));
		}
		else {
			
			if (task != 'delegate.cancel' && document.formvalidator.isValid(document.id('delegate-form'))) {
				
				Joomla.submitform(task, document.getElementById('delegate-form'));
			}
			else {
				alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}
</script>



<form
	action="<?php echo Route::_('index.php?option=com_bie_members&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="membersdelegate-form" class="form-validate form-horizontal">

	<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'basic')); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'basic', Text::_('Basic Info')); ?>
		<div class="row-fluid">
			<div class="col-md-12 form-horizontal">
				<fieldset class="adminform">
					<legend><?php echo Text::_('COM_BIE_MEMBERS_FIELDSET_BASICINFO'); ?></legend>
					<?php echo $this->form->renderField('prefix'); ?>
					<?php echo $this->form->renderField('first_name'); ?>
					<?php echo $this->form->renderField('last_name'); ?>
					<?php echo $this->form->renderField('gender'); ?>
					<?php echo $this->form->renderField('organisation'); ?>
					<?php echo $this->form->renderField('group'); ?>
					<?php echo $this->form->renderField('preferred_language'); ?>
					<?php echo $this->form->renderField('date_of_announce'); ?>
					<?php echo $this->form->renderField('job_title'); ?>
					<?php echo $this->form->renderField('primary_email'); ?>
					<?php echo $this->form->renderField('secondary_email'); ?>
				</fieldset>
			</div>
		</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'contactinfo', Text::_('Contact Info')); ?>
		<div class="row-fluid">
			<div class="col-md-12 form-horizontal">
				<fieldset class="adminform">
					<legend><?php echo Text::_('COM_BIE_MEMBERS_FIELDSET_CONTACTINFO'); ?></legend>
					<?php echo $this->form->renderField('city'); ?>
					<?php echo $this->form->renderField('street_address'); ?>
					<?php echo $this->form->renderField('supplemental_address_1'); ?>
					<?php echo $this->form->renderField('supplemental_address_2'); ?>
					<?php echo $this->form->renderField('postal_code'); ?>
					<?php echo $this->form->renderField('country'); ?>
					<?php echo $this->form->renderField('phone'); ?>
					<?php echo $this->form->renderField('mobile_phone'); ?>
					<?php echo $this->form->renderField('website'); ?>
					<?php echo $this->form->renderField('facebook'); ?>
					<?php echo $this->form->renderField('twitter'); ?>
				</fieldset>
			</div>
		</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'misc', Text::_('Misc')); ?>
		<div class="row-fluid">
			<div class="col-md-12 form-horizontal">
				<fieldset class="adminform">
					<legend><?php echo Text::_('COM_BIE_MEMBERS_FIELDSET_MISC'); ?></legend>
					<?php echo $this->form->renderField('notes'); ?>
				</fieldset>
			</div>
		</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

	<input type="hidden" name="jform[id]" value="<?php echo isset($this->item->id) ? $this->item->id : ''; ?>" />
	<input type="hidden" name="jform[state]" value="<?php echo isset($this->item->state) ? $this->item->state : ''; ?>" />

	<?php echo $this->form->renderField('created_by'); ?>
	<?php echo $this->form->renderField('modified_by'); ?>

	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
