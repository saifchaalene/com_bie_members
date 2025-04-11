<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Anand
 * @author     Super User <dev@component-creator.com>
 * @copyright  2023 Super User
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

HTMLHelper::_('jquery.framework');
HTMLHelper::_('bootstrap.tooltip');

?>

<div class="item_fields">

	<table class="table">
		

	</table>

</div>


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
	action="<?php echo Route::_('index.php?option=com_bie_members&view=delegates'); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="delegate-form" class="form-validate">

	<div class="form-horizontal">
            
            <h4 class="alert alert-error">Please select a Delegate first</h4>        

		<input type="hidden" name="task" value=""/>
		<?php echo Html::_('form.token'); ?>

	</div>
</form>
