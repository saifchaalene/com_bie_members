<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */


defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.framework');

$document = Factory::getDocument();
$document->addStyleSheet(Uri::root() . 'media/com_bie_members/css/form.css');
$ajaxUri = 'index.php?option=com_bie_members&task=membership.execute&format=json&' . Session::getFormToken() . '=1';
?>
<script type="text/javascript">
const js = jQuery.noConflict();

js(document).ready(function () {

	// Capitalize first letter of first and last name
	js("#jform_first_name, #jform_last_name").on("keyup", function () {
		if (js(this).val().length > 0) {
			js(this).val(js(this).val().charAt(0).toUpperCase() + js(this).val().slice(1));
		}
	});

	document.formvalidator.setHandler("url", value => validatePattern(value, /https?:\/\/[\w\-]+(\.[\w\-]+)+([\w.,@?^=%&:/~+#\-]*[\w@?^=%&/~+#\-])?/, "is not a valid URL"));
	document.formvalidator.setHandler("facebook", value => validatePattern(value, /https?:\/\/(\d+|[a-z\-]+)?\.facebook\.com\/(\d+|[A-Za-z0-9\.]+)\/?/, "is not a valid Facebook URL"));
	document.formvalidator.setHandler("twitter", value => validatePattern(value, /https?:\/\/(www\.)?twitter\.com\/(\d+|[A-Za-z0-9\.]+)\/?/, "is not a valid Twitter URL"));
	document.formvalidator.setHandler("number", value => validatePattern(value, /^\d*$/, "is not a valid number"));
	document.formvalidator.setHandler("phone", value => validatePattern(value, /^\+?[0-9]{3}-?[0-9]{6,12}$/, "is not a valid phone number"));

	function validatePattern(value, regex, message) {
		const isValid = regex.test(value);
		if (!isValid) displayError(value, message);
		return isValid;
	}

	function displayError(value, message) {
		const id = js(":input").filter(function () { return this.value === value; }).attr("id");
		const label = js("#membership-form #" + id + "-lbl").text();
		const error = { error: [`Field: <b>${label}</b> - value "${value}" ${message}`] };
		Joomla.renderMessages(error);
	}

	Joomla.submitbutton = function (task) {
		if (task === 'membership.cancel' || document.formvalidator.isValid(document.getElementById('membership-form'))) {
			Joomla.submitform(task, document.getElementById('membership-form'));
		} else {
			alert('<?php echo Text::_('JGLOBAL_VALIDATION_FORM_FAILED'); ?>');
		}
	};
});
</script>

<form action="<?php echo Route::_('index.php?option=com_bie_members&view=membership&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="membership-form" class="form-validate">

	<div class="form-horizontal">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', ['active' => 'general']); ?>
		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_CRM_CONTACTS_CREATE_ORGANISATION_TAB_BASIC', true)); ?>

		<fieldset class="adminform">
			<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
			<?php echo $this->form->renderField('prefix_id'); ?>
			<?php foreach ((array) $this->item->prefix_id as $value) :
				if (!is_array($value)) : ?>
					<input type="hidden" class="prefix_id" name="jform[prefix_idhidden][<?php echo $value; ?>]" value="<?php echo $value; ?>" />
				<?php endif;
			endforeach; ?>
			<?php echo $this->form->renderField('contact_id'); ?>
			<?php echo $this->form->renderField('start_date'); ?>

			<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
			<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
			<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
			<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
			<?php echo $this->form->renderField('created_by'); ?>
			<?php echo $this->form->renderField('modified_by'); ?>

			<?php if ($this->state->params->get('save_history', 1)) : ?>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('version_note'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('version_note'); ?></div>
				</div>
			<?php endif; ?>
		</fieldset>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
