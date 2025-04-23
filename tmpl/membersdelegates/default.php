<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

$app  = Factory::getApplication();
$wa   = $this->document->getWebAssetManager();

$wa->useStyle('com_bie_members.admin')
   ->useScript('com_bie_members.admin')
   ->useScript('joomla.dialog-autocreate'); 
$user      = $app->getIdentity();
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_bie_members');

$saveOrder = $listOrder === 'a.ordering';
if ($saveOrder)
{
    $saveOrderingUrl = 'index.php?option=com_bie_members&task=membersdelegates.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}

$sortFields     = $this->getSortFields();
$this->sidebar  = LayoutHelper::render('joomla.searchtools.default', ['view' => $this]);
$this->hideSearchBox = false;
$this->filters  = ['view' => $this];
?>


      <form action="<?php echo Route::_('index.php?option=com_bie_members&view=membersdelegates'); ?>" method="post" name="adminForm" id="adminForm">

      <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>


    <div id="j-main-container">

	


        <div class="clearfix"></div>
        <div class="table-responsive">
            <table class="table table-striped" id="delegateList">
                <thead>
                <tr>
					<?php if (isset($this->items[0]->ordering)): ?>
                        <th width="1%" class="nowrap center hidden-phone">
							<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.`ordering`', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
                        </th>
					<?php endif; ?>
                    <th width="1%" class="hidden-phone">
                        <input type="checkbox" name="checkall-toggle" value=""
                               title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)"/>
                    </th>
					<?php if (isset($this->items[0]->state)): ?>

					<?php endif; ?>
                    <th class='center hidden-phone' width="20px">
                        &nbsp;
                    </th>

                    <th class='left nowrap' scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BIE_MEMBERS_MEMBERSDELEGATES_COUNTRY', 'a.`country`', $listDirn, $listOrder); ?>
                    </th>
                    <th class='left hidden-phone'>
						<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_USERNAME'); ?>
                    </th>
                    <th class='left' scope="col">
						<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_FULLNAME'); ?>
                    </th>
                    <th class='left' style="width:50px;" scope="col">
						<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_ROLE'); ?>
                    </th>
                    <th class='left' style="width:250px;" scope="col">
						<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_MAILS'); ?>
                    </th>
                    <th class='left' style="width:140px;" scope="col">
						<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_PHONES'); ?>
                    </th>
                    <th class='left nowrap' scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BIE_MEMBERS_MEMBERSDELEGATES_TYPE', 'a.`type`', $listDirn, $listOrder); ?>
                    </th>
                    <th class='left nowrap hidden-phone'>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BIE_MEMBERS_MEMBERSDELEGATES_ORDER', 'a.`order`', $listDirn, $listOrder); ?>
                    </th>

                    <th class='center hidden-phone' width="90px">
						<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_ACTIVE_USER'); ?>
                    </th>
                    <th class='center hidden-phone' style="width:60px">
                        <label class="hasTooltip"
                               data-original-title="<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_NOTES'); ?>">
                            <i class="fa fa-commenting" aria-hidden="true"></i>
                        </label>

                    </th>
                    <th class='center hidden-phone' style="width:30px">
                        <label class="hasTooltip"
                               data-original-title="<?php echo Text::_('COM_BIE_BULLETIN_BULLETININDIVIDUALS_LANGUAGE_TITLE'); ?>">
                            <i class="fa fa-language" aria-hidden="true"></i>
                        </label>

                    </th>
                    <th class='center hidden-phone' style="width:30px">
                        <label class="hasTooltip"
                               data-original-title="<?php echo Text::_('COM_BIE_BULLETIN_BULLETININDIVIDUALS_NEWSLETTER_TITLE'); ?>">
                            <i class="fa fa-newspaper-o" aria-hidden="true"></i>
                        </label>
                    </th>


                </tr>
                </thead>
                <tfoot>
                <tr>
                    <td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
                    </td>
                </tr>
                </tfoot>
                <tbody>
				<?php foreach ($this->items as $i => $item) :
					$ordering = ($listOrder == 'a.ordering');
					$canCreate = $user->authorise('core.create', 'com_bie_members');
					$canEdit = $user->authorise('core.edit', 'com_bie_members');
					$canCheckin = $user->authorise('core.manage', 'com_bie_members');
					$canChange = $user->authorise('core.edit.state', 'com_bie_members');
					?>
                    <tr class="row<?php echo $i % 2; ?>">

						<?php if (isset($this->items[0]->ordering)) : ?>
                            <td class="order nowrap center hidden-phone">
								<?php if ($canChange) :
									$disableClassName = '';
									$disabledLabel = '';

									if (!$saveOrder) :
										$disabledLabel    = Text::_('JORDERINGDISABLED');
										$disableClassName = 'inactive tip-top';
									endif; ?>
                                    <span class="sortable-handler hasTooltip <?php echo $disableClassName ?>"
                                          title="<?php echo $disabledLabel ?>"><i class="icon-menu"></i>
                                    </span>
                                    <input type="text" style="display:none" name="order[]" size="5"
                                           value="<?php echo $item->ordering; ?>" class="width-20 text-area-order "/>
								<?php else : ?>
                                    <span class="sortable-handler inactive">
                                        <i class="icon-menu"></i>
                                    </span>
								<?php endif; ?>
                            </td>
						<?php endif; ?>
                        <td class="hidden-phone">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                        </td>
						<?php if (isset($this->items[0]->state)): ?>

						<?php endif; ?>

                        <td class="hidden-phone">
                        <?php


$popupOptions = [
    'popupType'  => 'iframe',
    'textHeader' => Text::_('COM_BIE_MEMBERS_DELEGATE_DETAILS') . ': ' . $item->id,
    'width'      => '70vw',
    'height'     => '70vh',
    'src'        => Route::_('index.php?option=com_bie_members&view=membersdelegate&id=' . (int) $item->id . '&tmpl=component'),
                 

];

?>
<a href="#" class="btn btn-sm btn-outline-info hasTooltip"
   title="<?php echo Text::_('COM_BIE_MEMBERS_DELEGATES_VIEW_DETAILS'); ?>"
   data-joomla-dialog='<?php echo htmlspecialchars(json_encode($popupOptions), ENT_QUOTES, 'UTF-8'); ?>'>
   <span class="icon-info-circle" aria-hidden="true"></span>
</a>

                            </td>
                        <td scope="row" data-label="<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_COUNTRY'); ?>">
							<?php if (isset($item->checked_out) && $item->checked_out && ($canEdit || $canChange)) : ?>
								<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'delegates.', $canCheckin); ?>
							<?php endif; ?>
                            <?php echo isset($item->country) ? $this->escape($item->country) : 'not found'; ?>

                        </td>
                        <td class="hidden-phone">

							<span style="display:block;"><?php echo $item->username; ?></span>
                            <span style="display:block;font-size: 0.7rem;"><?php echo $item->mail; ?></span>
                        </td>
                        <td scope="row" data-label="<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_FULLNAME'); ?>">

							<?php echo $item->fullname; ?>
                        </td>
                        <td scope="row" data-label="<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_ROLE'); ?>">

							<?php echo $item->job_title; ?>
                        </td>
                        <td scope="row" data-label="<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_MAILS'); ?>">

							<?php echo $item->mails; ?>
                        </td>
                        <td scope="row" data-label="<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_PHONES'); ?>"
                            class="phones">

							<?php echo $item->phones; ?>
                        </td>
                        <td scope="row" data-label="<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_TYPE'); ?>">

							<?php echo $item->type; ?>
                        </td>
                        <td class="hidden-phone center">

							<?php echo $item->order; ?>
                        </td>

						<td class="hidden-phone center">
    <?php echo $item->active
        ? '<span class="icon-publish" aria-label="' . Text::_('JYES') . '"></span>'
        : '<span class="icon-unpublish" aria-label="' . Text::_('JNO') . '"></span>'; ?>
</td>


                        <td class="hidden-phone center">

							<?php if (intval($item->notes) > 0) : ?>
                                <a class="btn-edit-module" href="javascript:void(0);"
                                   data-toggle="modal"
                                   data-target="#jabktmp-module" data-url="<?php echo $item->notes_url; ?>"
                                   data-module="2" data-title="">
                                    <span class="" title="View Notes"><?php echo $item->notes; ?></span>
                                </a>
							<?php endif; ?>
                            <a class="btn-edit-module" href="javascript:void(0);"
                               data-toggle="modal"
                               data-target="#jabktmp-module" data-url="<?php echo $item->comment_url; ?>"
                               data-module="2" data-title="">
                                <span class="fa fa-pencil-square-o" title="Add Notes"></span>
                            </a>

                        </td>
                        <td class="center hidden-phone">
                            <label class="hasTooltip"
                                   data-original-title="<?php echo Text::_('COM_BIE_BULLETIN_BULLETININDIVIDUALS_DELEGATE_PREFERRED_LANGUAGE_' . strtoupper($item->preferred_language)); ?>">
								<?php echo HTMLHelper::_('image', 'mod_languages/' . strtolower($item->preferred_language) . '.gif', Text::_('COM_BIE_BULLETIN_BULLETININDIVIDUALS_DELEGATE_PREFERRED_LANGUAGE_' . strtoupper($item->preferred_language)), array('title' => ""), true); ?>
                            </label>
                        </td>
                        <td class="center hidden-phone">
                            <label class="hasTooltip"
                                   data-original-title="<?php echo Text::_('COM_BIE_BULLETIN_BULLETININDIVIDUALS_NEWSLETTER_STATUS_' . strtoupper($item->isSubscribed)); ?>">
								<?php echo HTMLHelper::_('image', 'admin/' . Text::_('COM_BIE_BULLETIN_BULLETININDIVIDUALS_NEWSLETTER_IMG_' . strtoupper($item->isSubscribed)), Text::_('COM_BIE_BULLETIN_BULLETININDIVIDUALS_NEWSLETTER_STATUS_' . strtoupper($item->isSubscribed)), array('title' => ""), true); ?>
                            </label>

                        </td>

                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="list[ordering]" value="<?php echo $listOrder; ?>">
<input type="hidden" name="list[direction]" value="<?php echo $listDirn; ?>">
		<?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
<div class="modal fade" id="jabktmp-module" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 90vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">...</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
            </div>
            <div class="modal-body" style="min-height: 70vh;">
                <iframe id="ja-md-edit" name="modalModule" src="" style="width:100%; height:100%;" frameborder="0"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCLOSE'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
function refreshTable() {
    location.reload();
}
    function actionsNewsletter(val) {
        console.log(val);

		var result = confirm("<?php echo Text::_("COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_CONFIRM"); ?>");

        if (result) {
            if (val == 1) {
                Joomla.submitbutton('delegates.subscribeNewsletter');
            }
            if (val == 2) {
                Joomla.submitbutton('delegates.unsubscribenewsletter');
            }
        }
    }

    jQuery(document).ready(function () {
        jQuery('#clear-search-button').on('click', function () {
            jQuery('#filter_search').val('');
            jQuery('#adminForm').submit();
        });

        jQuery('.btn-edit-module').on('click', function () {
            loadurl = jQuery(this).data('url');
            moduleTitle = jQuery(this).data('title');
            jQuery('#jabktmp-module').find('.modal-body').html('<iframe height="100%" scrolling="no" id="ja-md-edit" name="modalModule" src="' + loadurl + '"></iframe>');
            jQuery('#jabktmp-module').find('.modal-title').html(moduleTitle);
            jQuery('#jabktmp-module').find('.modal-footer').html('<button type="button" class="btn btn-default btn-cancel" data-dismiss="modal" onClick="this.hide();"><?php echo Text::_('Close'); ?></button>');
        });


    });

    window.toggleField = function (id, task, field) {

        var f = document.adminForm,
            i = 0, cbx,
            cb = f[id];

        if (!cb) return false;

        while (true) {
            cbx = f['cb' + i];

            if (!cbx) break;

            cbx.checked = false;
            i++;
        }

        var inputField = document.createElement('input');
        inputField.type = 'hidden';
        inputField.name = 'field';
        inputField.value = field;
        f.appendChild(inputField);

        cb.checked = true;
        f.boxchecked.value = 1;
        window.submitform(task);

        return false;
    };

</script>