<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

$app = Factory::getApplication();
$wa  = $this->document->getWebAssetManager();
$wa->useStyle('com_bie_members.admin')->useScript('com_bie_members.admin');

$user      = $app->getIdentity();
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_bie_members');

$saveOrder = $listOrder == 'a.ordering';
if (!empty($saveOrder)) {
    $saveOrderingUrl = 'index.php?option=com_bie_members&task=membersdelegates.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}

$sortFields     = $this->getSortFields();
$this->hideSearchBox = false;
$this->sidebar  = LayoutHelper::render('joomla.searchtools.default', ['view' => $this]);
$this->filters  = ['view' => $this];
?>

<form action="<?php echo Route::_('index.php?option=com_bie_members&view=membersdelegates'); ?>" method="post" name="adminForm" id="adminForm">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <div id="j-main-container" class="clearfix">
        <div class="table-responsive">
            <table class="table table-striped" id="delegateList">
                <thead>
                    <tr>
                        <?php if (isset($this->items[0]->ordering)) : ?>
                            <th width="1%" class="nowrap center hidden-phone">
                                <?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder); ?>
                            </th>
                        <?php endif; ?>
                        <th width="1%" class="hidden-phone">
                            <?php echo HTMLHelper::_('grid.checkall'); ?>
                        </th>
                        <th class="center hidden-phone" width="20px">&nbsp;</th>
                        <th class="left nowrap"><?php echo HTMLHelper::_('searchtools.sort', 'COM_BIE_MEMBERS_MEMBERSDELEGATES_COUNTRY', 'a.country', $listDirn, $listOrder); ?></th>
                        <th class="left hidden-phone"><?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_USERNAME'); ?></th>
                        <th class="left"><?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_FULLNAME'); ?></th>
                        <th class="left" style="width:50px;"><?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_ROLE'); ?></th>
                        <th class="left" style="width:250px;"><?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_MAILS'); ?></th>
                        <th class="left" style="width:140px;"><?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_PHONES'); ?></th>
                        <th class="left nowrap"><?php echo HTMLHelper::_('searchtools.sort', 'COM_BIE_MEMBERS_MEMBERSDELEGATES_TYPE', 'a.type', $listDirn, $listOrder); ?></th>
                        <th class="left nowrap hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'COM_BIE_MEMBERS_MEMBERSDELEGATES_ORDER', 'a.order', $listDirn, $listOrder); ?></th>
                        <th class="center hidden-phone" width="90px"><?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_ACTIVE_USER'); ?></th>
                        <th class="center hidden-phone" style="width:60px"><i class="fa fa-commenting" title="<?php echo Text::_('COM_BIE_MEMBERS_MEMBERSDELEGATES_NOTES'); ?>"></i></th>
                        <th class="center hidden-phone" style="width:30px"><i class="fa fa-language" title="<?php echo Text::_('COM_BIE_BULLETIN_BULLETININDIVIDUALS_LANGUAGE_TITLE'); ?>"></i></th>
                        <th class="center hidden-phone" style="width:30px"><i class="fa fa-newspaper-o" title="<?php echo Text::_('COM_BIE_BULLETIN_BULLETININDIVIDUALS_NEWSLETTER_TITLE'); ?>"></i></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan="14"><?php echo $this->pagination->getListFooter(); ?></td>
                    </tr>
                </tfoot>
                <tbody>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <?php if (isset($this->items[0]->ordering)) : ?>
                                <td class="order nowrap center hidden-phone">
                                    <span class="sortable-handler<?php echo !$saveOrder ? ' inactive tip-top' : ''; ?>" title="<?php echo !$saveOrder ? Text::_('JORDERINGDISABLED') : ''; ?>">
                                        <i class="icon-menu"></i>
                                    </span>
                                    <input type="text" name="order[]" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order" style="display:none" />
                                </td>
                            <?php endif; ?>

                            <td class="hidden-phone"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>

                            <td class="hidden-phone">
                                <a class="btn-edit-module" href="javascript:void(0);" data-toggle="modal" data-target="#jabktmp-module" data-url="<?php echo $item->view_url; ?>" data-title="<?php echo $this->escape($item->fullname); ?>">
                                    <span class="icon-info"></span>
                                </a>
                            </td>

                            <td><?php echo $this->escape($item->country); ?></td>
                            <td>
                                <?php echo $this->escape($item->username); ?>
                                <br><small><?php echo $this->escape($item->mail); ?></small>
                            </td>
                            <td><?php echo $this->escape($item->fullname); ?></td>
                            <td><?php echo $this->escape($item->job_title); ?></td>
                            <td><?php echo nl2br($this->escape(strip_tags($item->mails))); ?></td>
                            <td><?php echo nl2br($this->escape(strip_tags($item->phones))); ?></td>
                            <td><?php echo $this->escape($item->type); ?></td>
                            <td class="center"><?php echo $item->order; ?></td>
                            <td class="center">
                                <?php echo $item->active
                                    ? '<span class="icon-publish" aria-label="' . Text::_('JYES') . '"></span>'
                                    : '<span class="icon-unpublish" aria-label="' . Text::_('JNO') . '"></span>'; ?>
                            </td>
                            <td class="center">
                                <?php if ((int) $item->notes > 0) : ?>
                                    <a class="btn-edit-module" href="javascript:void(0);" data-toggle="modal" data-target="#jabktmp-module" data-url="<?php echo $item->notes_url; ?>"><span><?php echo $item->notes; ?></span></a>
                                <?php endif; ?>
                                <a class="btn-edit-module" href="javascript:void(0);" data-toggle="modal" data-target="#jabktmp-module" data-url="<?php echo $item->comment_url; ?>"><span class="fa fa-pencil-square-o" title="Add Notes"></span></a>
                            </td>
                            <td class="center"><?php echo HTMLHelper::_('image', 'mod_languages/' . strtolower($item->preferred_language) . '.gif', '', [], true); ?></td>
                            <td class="center"><?php echo HTMLHelper::_('image', 'admin/' . Text::_('COM_BIE_BULLETIN_BULLETININDIVIDUALS_NEWSLETTER_IMG_' . strtoupper($item->isSubscribed)), '', [], true); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="list[ordering]" value="<?php echo $listOrder; ?>" />
        <input type="hidden" name="list[direction]" value="<?php echo $listDirn; ?>" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
