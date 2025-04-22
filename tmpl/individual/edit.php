<?php
/**
 * @package     Com_Bie_members
 * @subpackage  Administrator
 * @author       Tasos
 * @license      GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

// Load core Joomla assets only
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('jquery'); // ✅ Required for jQuery usage
$wa->useScript('keepalive')
   ->useScript('form.validate');
$ajaxUri = Route::_('index.php?option=com_expos_participants&task=expoparticipants.execute&format=json&' . Session::getFormToken() . '=1');
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const capitalize = str => str.charAt(0).toUpperCase() + str.slice(1);
    const $ = jQuery;

    $('#jform_first_name').on('keyup', function () {
        $(this).val(capitalize($(this).val()));
    });

    $('#jform_last_name').on('keyup', function () {
        $(this).val(capitalize($(this).val()));
    });

    Joomla.submitbutton = function (task) {
	const form = document.getElementById('individual-form');

	if (task === 'individual.cancel') {
		console.log('❌ Cancel clicked');
		Joomla.submitform(task, form);
		return;
	}

	if (document.formvalidator.isValid(form)) {
		console.groupCollapsed('🟢 Submit button clicked');
		console.log('🔘 Task:', task);
		console.log('📤 Form data to submit:');

		[...form.elements].forEach(el => {
			if (el.name && el.type !== 'button' && el.type !== 'submit') {
				console.log(`${el.name}:`, el.value);
			}
		});
		console.groupEnd();

		Joomla.submitform(task, form);
	} else {
		alert('<?php echo Text::_('JGLOBAL_VALIDATION_FORM_FAILED'); ?>');

		console.warn('Form validation failed:');
		[...form.elements].forEach(el => {
			if (el.classList.contains('invalid')) {
				console.warn(`❗ Invalid field: ${el.name}`, el);
			}
		});

		const firstInvalid = form.querySelector('.invalid');
		if (firstInvalid) {
			firstInvalid.focus();
			firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
	}
};

});
</script>

<form
    action="<?php echo Route::_('index.php?option=com_bie_members&view=individual&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post"
    enctype="multipart/form-data"
    name="adminForm"
    id="individual-form"
    class="form-validate">

    <input type="hidden" id="expoparticipant-type-url" value="<?php echo $ajaxUri; ?>" />

    <div class="form-horizontal">
        <?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', ['active' => 'general']); ?>

        <?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_CRM_CONTACTS_CREATE_ORGANISATION_TAB_BASIC')); ?>
        <fieldset class="adminform">
            <?php echo $this->form->renderField('prefix_id'); ?>
            <?php echo $this->form->renderField('first_name'); ?>
            <?php echo $this->form->renderField('last_name'); ?>
            <?php echo $this->form->renderField('gender_id'); ?>
            <?php echo $this->form->renderField('employer_id'); ?>
            <?php echo $this->form->renderField('group_id'); ?>
            <?php echo $this->form->renderField('prefered_language'); ?>
            <?php echo $this->form->renderField('start_date'); ?>
            <?php echo $this->form->renderField('job_title'); ?>
            <?php echo $this->form->renderField('email'); ?>
            <?php echo $this->form->renderField('email2'); ?>
        </fieldset>
        <?php echo HTMLHelper::_('bootstrap.endTab'); ?>

        <?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'address', Text::_('COM_CRM_CONTACTS_CREATE_ORGANISATION_TAB_ADDRESS')); ?>
        <fieldset class="adminform">
            <?php echo $this->form->renderField('city'); ?>
            <?php echo $this->form->renderField('street_address'); ?>
            <?php echo $this->form->renderField('supplemental_address_1'); ?>
            <?php echo $this->form->renderField('supplemental_address_2'); ?>
            <?php echo $this->form->renderField('postal_code'); ?>
            <?php echo $this->form->renderField('country_id'); ?>
            <?php echo $this->form->renderField('phone'); ?>
            <?php echo $this->form->renderField('mobile_phone'); ?>
            <?php echo $this->form->renderField('web'); ?>
            <?php echo $this->form->renderField('facebook'); ?>
            <?php echo $this->form->renderField('twitter'); ?>
        </fieldset>
        <?php echo HTMLHelper::_('bootstrap.endTab'); ?>

        <?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'misc', Text::_('COM_CRM_CONTACTS_CREATE_ORGANISATION_TAB_MISC')); ?>
        <fieldset class="adminform">
            <?php echo $this->form->renderField('exposure'); ?>
            <?php echo $this->form->renderField('note'); ?>
        </fieldset>
        <?php echo HTMLHelper::_('bootstrap.endTab'); ?>

        <?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>

        <!-- Hidden fields -->
        <?php echo $this->form->renderField('created_by'); ?>
        <?php echo $this->form->renderField('modified_by'); ?>
        <?php echo $this->form->renderField('dups_informed'); ?>

        <?php if ($this->state->params->get('save_history', 1)) : ?>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('version_note'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('version_note'); ?></div>
            </div>
        <?php endif; ?>

        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
