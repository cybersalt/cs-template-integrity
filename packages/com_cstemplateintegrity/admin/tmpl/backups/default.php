<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * @var \Cybersalt\Component\Cstemplateintegrity\Administrator\View\Backups\HtmlView $this
 */

declare(strict_types=1);

defined('_JEXEC') or die;

// First-run disclaimer modal (rendered only if the current admin
// user has not yet ticked "do not show again").
echo \Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\DisclaimerHelper::renderModalIfNeeded();

use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\BackupDescriber;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
?>

<form action="<?php echo $this->escape(\Joomla\CMS\Uri\Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
<div class="container-fluid">
    <p class="text-body-secondary mb-3"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_DESCRIPTION'); ?></p>
    <p class="text-body-secondary mb-3"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_STORAGE_NOTE'); ?></p>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_EMPTY'); ?></div>
    <?php else : ?>
        <table class="table">
            <thead>
                <tr>
                    <th class="w-1"><input type="checkbox" name="checkall-toggle" value="" onclick="Joomla.checkAll(this)"></th>
                    <th><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_COL_TIME'); ?></th>
                    <th><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_COL_FILE'); ?></th>
                    <th><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_COL_SIZE'); ?></th>
                    <th><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_COL_HASH'); ?></th>
                    <th><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_COL_SESSION'); ?></th>
                    <th><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_COL_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->items as $i => $row) : ?>
                    <?php
                    $viewUrl     = Route::_('index.php?option=com_cstemplateintegrity&view=backup&id=' . (int) $row->id, false);
                    $downloadUrl = Route::_('index.php?option=com_cstemplateintegrity&task=backups.download&id=' . (int) $row->id . '&' . Session::getFormToken() . '=1', false);
                    ?>
                    <tr>
                        <td><input type="checkbox" id="cb<?php echo $i; ?>" name="cid[]" value="<?php echo (int) $row->id; ?>" onclick="Joomla.isChecked(this.checked);"></td>
                        <td>
                            <small><?php echo HTMLHelper::_('date', $row->created_at, Text::_('DATE_FORMAT_LC4')); ?></small>
                        </td>
                        <td>
                            <a href="<?php echo $this->escape($viewUrl); ?>"><small><code><?php echo $this->escape($row->file_path); ?></code></small></a>
                            <br><small class="text-body-secondary"><?php echo BackupDescriber::describe((string) $row->file_path); ?></small>
                        </td>
                        <td><small><?php echo number_format((int) $row->file_size); ?> B</small></td>
                        <td><small class="text-body-secondary"><?php echo $this->escape(substr($row->file_hash, 0, 12)); ?>&hellip;</small></td>
                        <td>
                            <?php if ($row->session_id) : ?>
                                <a href="<?php echo $this->escape(Route::_('index.php?option=com_cstemplateintegrity&view=session&id=' . (int) $row->session_id . '&from=backups', false)); ?>">
                                    #<?php echo (int) $row->session_id; ?>
                                </a>
                            <?php else : ?>
                                <span class="text-body-secondary">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo $this->escape($viewUrl); ?>" class="btn btn-sm btn-info" title="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_VIEW')); ?>">
                                <span class="icon-eye" aria-hidden="true"></span>
                            </a>
                            <a href="<?php echo $this->escape($downloadUrl); ?>" class="btn btn-sm btn-info" title="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_BACKUPS_COL_DOWNLOAD')); ?>">
                                <span class="icon-download" aria-hidden="true"></span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
