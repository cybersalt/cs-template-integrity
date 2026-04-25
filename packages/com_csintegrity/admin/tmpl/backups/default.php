<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * @var \Cybersalt\Component\Csintegrity\Administrator\View\Backups\HtmlView $this
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>

<div class="container-fluid">
    <p class="text-body-secondary mb-3"><?php echo Text::_('COM_CSINTEGRITY_BACKUPS_DESCRIPTION'); ?></p>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_CSINTEGRITY_BACKUPS_EMPTY'); ?></div>
    <?php else : ?>
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo Text::_('COM_CSINTEGRITY_BACKUPS_COL_TIME'); ?></th>
                    <th><?php echo Text::_('COM_CSINTEGRITY_BACKUPS_COL_FILE'); ?></th>
                    <th><?php echo Text::_('COM_CSINTEGRITY_BACKUPS_COL_SIZE'); ?></th>
                    <th><?php echo Text::_('COM_CSINTEGRITY_BACKUPS_COL_HASH'); ?></th>
                    <th><?php echo Text::_('COM_CSINTEGRITY_BACKUPS_COL_SESSION'); ?></th>
                    <th><?php echo Text::_('COM_CSINTEGRITY_BACKUPS_COL_DOWNLOAD'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->items as $row) : ?>
                    <?php $downloadUrl = Route::_('index.php?option=com_csintegrity&task=backups.download&id=' . (int) $row->id, false); ?>
                    <tr>
                        <td>
                            <small><?php echo HTMLHelper::_('date', $row->created_at, Text::_('DATE_FORMAT_LC4')); ?></small>
                        </td>
                        <td><small><code><?php echo $this->escape($row->file_path); ?></code></small></td>
                        <td><small><?php echo number_format((int) $row->file_size); ?> B</small></td>
                        <td><small class="text-body-secondary"><?php echo $this->escape(substr($row->file_hash, 0, 12)); ?>&hellip;</small></td>
                        <td>
                            <?php if ($row->session_id) : ?>
                                <a href="<?php echo $this->escape(Route::_('index.php?option=com_csintegrity&view=session&id=' . (int) $row->session_id . '&from=backups', false)); ?>">
                                    #<?php echo (int) $row->session_id; ?>
                                </a>
                            <?php else : ?>
                                <span class="text-body-secondary">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo $this->escape($downloadUrl); ?>" class="btn btn-sm btn-outline-secondary">
                                <span class="icon-download" aria-hidden="true"></span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
