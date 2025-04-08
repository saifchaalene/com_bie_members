<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Saif
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

// Tom Select for dropdowns
HTMLHelper::_('script', 'media/vendor/tom-select/js/tom-select.complete.min.js', ['relative' => false]);
HTMLHelper::_('stylesheet', 'media/vendor/tom-select/css/tom-select.bootstrap5.min.css', ['relative' => false]);

$document = Factory::getDocument();
$document->addStyleSheet(Uri::root() . 'media/com_bie_members/css/form.css');
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
	const js = jQuery.noConflict();

	// Capitalize
	js("#jform_first_name, #jform_last_name").on("keyup", function () {
		const val = js(this).val();
		if (val.length > 0) {
			js(this).val(val.charAt(0).toUpperCase() + val.slice(1));
		}
	});

	// Tom Select init
	['jform_contact_id', 'jform_prefix_id', 'jform_employer_id'].forEach(id => {
		const el = document.getElementById(id);
		if (el) {
			new TomSelect(el, { create: false, plugins: ['remove_button'] });
		}
	});

	// Validation handlers
	const validatePattern = (value, regex, message) => {
		const isValid = regex.test(value);
		if (!isValid) displayError(value, message);
		return isValid;
	};

	const displayError = (value, message) => {
		const id = jQuery(':input').filter(function () {
			return this.value == value
		}).attr('id');
		const lbl = jQuery('#membership-form #' + id + '-lbl');
		const error = { error: [`Field: <b>${lbl.text()}</b> - value "${value}" ${message}`] };
		Joomla.renderMessages(error);
	};

	document.formvalidator.setHandler("url", v => validatePattern(v, /https?:\/\/[\w\-]+(\.[\w\-]+)+([\w.,@?^=%&:/~+#\-]*[\w@?^=%&/~+#\-])?/, "is not a valid URL"));
	document.formvalidator.setHandler("facebook", v => validatePattern(v, /https?:\/\/(\d+|[a-z\-]+)?\.facebook\.com\/(\d+|[A-Za-z0-9\.]+)\/?/, "is not a valid Facebook URL"));
	document.formvalidator.setHandler("twitter", v => validatePattern(v, /https?:\/\/(?:www\.)?twitter\.com\/(\d+|[A-Za-z0-9\.]+)\/?/, "is not a valid Twitter URL"));
	document.formvalidator.setHandler("number", v => validatePattern(v, /^\d*$/, "is not a valid number"));
	document.formvalidator.setHandler("phone", v => validatePattern(v, /^\+?[0-9]{3}-?[0-9]{6,12}$/, "is not a valid phone number"));

	Joomla.submitbutton = function (task) {
		if (task === 'membership.cancel' || document.formvalidator.isValid(document.getElementById('membership-form'))) {
			Joomla.submitform(task, document.getElementById('membership-form'));
		} else {
			alert('<?php echo Text::_('JGLOBAL_VALIDATION_FORM_FAILED'); ?>');
		}
	};
});
</script>

<form
	action="<?php echo Route::_('index.php?option=com_bie_members&view=membership&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="membership-form" class="form-validate">

	<div class="container-fluid">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', ['active' => 'general']); ?>
		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_CRM_CONTACTS_CREATE_ORGANISATION_TAB_BASIC')); ?>

		<fieldset class="options-form">
			<?php echo $this->form->renderField('prefix_id'); ?>
			<?php foreach ((array) $this->item->prefix_id as $value) :
				if (!is_array($value)) : ?>
					<input type="hidden" class="prefix_id" name="jform[prefix_idhidden][<?php echo $value; ?>]" value="<?php echo $value; ?>" />
				<?php endif;
			endforeach; ?>

			<?php echo $this->form->renderField('contact_id'); ?>
			<?php echo $this->form->renderField('start_date'); ?>
		</fieldset>

		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
		<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
		<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
		<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
		<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
		<?php echo $this->form->renderField('created_by'); ?>
		<?php echo $this->form->renderField('modified_by'); ?>

		<?php if ($this->state->params->get('save_history', 1)) : ?>
			<div class="mb-3">
				<?php echo $this->form->renderField('version_note'); ?>
			</div>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
