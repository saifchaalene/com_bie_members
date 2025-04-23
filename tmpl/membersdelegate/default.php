<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

$delegate = $this->item;
?>

<div class="container py-3">
    <h3><?php echo Text::_('COM_BIE_MEMBERS_DELEGATE_DETAILS'); ?></h3>

    <div class="card p-3 mt-3">
        <p><strong><?php echo Text::_('COM_BIE_MEMBERS_DELEGATES_FULLNAME'); ?>:</strong>
            <?php echo htmlspecialchars($delegate->first_name . ' ' . $delegate->last_name); ?></p>

        <p><strong><?php echo Text::_('COM_BIE_MEMBERS_DELEGATES_COUNTRY'); ?>:</strong>
            <?php echo htmlspecialchars($delegate->country); ?></p>

        <p><strong><?php echo Text::_('COM_BIE_MEMBERS_DELEGATES_ROLE'); ?>:</strong>
            <?php echo htmlspecialchars($delegate->job_title); ?></p>

        <p><strong><?php echo Text::_('COM_BIE_MEMBERS_DELEGATES_TYPE'); ?>:</strong>
            <?php echo htmlspecialchars($delegate->type); ?></p>

        <p><strong><?php echo Text::_('COM_BIE_MEMBERS_DELEGATES_START_DATE'); ?>:</strong>
            <?php echo htmlspecialchars($delegate->start_date); ?></p>

        <p><strong><?php echo Text::_('COM_BIE_MEMBERS_DELEGATES_END_DATE'); ?>:</strong>
            <?php echo htmlspecialchars($delegate->end_date); ?></p>

        <p><strong><?php echo Text::_('COM_BIE_MEMBERS_DELEGATES_EMAIL'); ?>:</strong>
            <?php echo htmlspecialchars($delegate->primary_email); ?></p>

        <p><strong><?php echo Text::_('COM_BIE_MEMBERS_DELEGATES_PHONE'); ?>:</strong>
            <?php echo htmlspecialchars($delegate->phone); ?></p>
    </div>
</div>
