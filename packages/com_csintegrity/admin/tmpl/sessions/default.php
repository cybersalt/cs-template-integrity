<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * @var \Cybersalt\Component\Csintegrity\Administrator\View\Sessions\HtmlView $this
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$listAction = Route::_('index.php?option=com_csintegrity&view=sessions', false);
?>

<form action="<?php echo $this->escape(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
    <div class="container-fluid">
        <p class="text-body-secondary mb-3">
            <?php echo Text::_('COM_CSINTEGRITY_SESSIONS_INTRO'); ?>
        </p>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <?php echo Text::_('COM_CSINTEGRITY_SESSIONS_EMPTY'); ?>
            </div>
        <?php else : ?>
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-1"><input type="checkbox" name="checkall-toggle" value=""
                                onclick="Joomla.checkAll(this)"></th>
                        <th><?php echo Text::_('COM_CSINTEGRITY_SESSIONS_COL_NAME'); ?></th>
                        <th><?php echo Text::_('COM_CSINTEGRITY_SESSIONS_COL_SOURCE'); ?></th>
                        <th><?php echo Text::_('COM_CSINTEGRITY_SESSIONS_COL_SUMMARY'); ?></th>
                        <th><?php echo Text::_('COM_CSINTEGRITY_SESSIONS_COL_CREATED'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->items as $i => $row) : ?>
                        <?php $url = Route::_('index.php?option=com_csintegrity&view=session&id=' . (int) $row->id, false); ?>
                        <tr>
                            <td><input type="checkbox" id="cb<?php echo $i; ?>" name="cid[]" value="<?php echo (int) $row->id; ?>"
                                    onclick="Joomla.isChecked(this.checked);"></td>
                            <td>
                                <a href="<?php echo $this->escape($url); ?>">
                                    <?php echo $this->escape($row->name); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $this->escape($row->source); ?></span>
                            </td>
                            <td>
                                <small class="text-body-secondary">
                                    <?php echo $this->escape($row->summary !== '' ? $row->summary : Text::_('COM_CSINTEGRITY_SESSIONS_NO_SUMMARY')); ?>
                                </small>
                            </td>
                            <td>
                                <small><?php echo HTMLHelper::_('date', $row->created_at, Text::_('DATE_FORMAT_LC4')); ?></small>
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
