<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * @var \Cybersalt\Component\Cstemplateintegrity\Administrator\View\Actions\HtmlView $this
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>

<div class="container-fluid">
    <p class="text-body-secondary mb-3"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_ACTIONS_DESCRIPTION'); ?></p>
    <p class="text-body-secondary mb-3"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_ACTIONS_VS_SESSIONS'); ?></p>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_ACTIONS_EMPTY'); ?></div>
    <?php else : ?>
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_ACTIONS_COL_TIME'); ?></th>
                    <th><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_ACTIONS_COL_ACTION'); ?></th>
                    <th><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_ACTIONS_COL_SESSION'); ?></th>
                    <th><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_ACTIONS_COL_DETAILS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->items as $row) : ?>
                    <tr>
                        <td>
                            <small><?php echo HTMLHelper::_('date', $row->created_at, Text::_('DATE_FORMAT_LC4')); ?></small>
                        </td>
                        <td><code><?php echo $this->escape($row->action); ?></code></td>
                        <td>
                            <?php if ($row->session_id) : ?>
                                <a href="<?php echo $this->escape(Route::_('index.php?option=com_cstemplateintegrity&view=session&id=' . (int) $row->session_id . '&from=actions', false)); ?>">
                                    #<?php echo (int) $row->session_id; ?>
                                </a>
                            <?php else : ?>
                                <span class="text-body-secondary">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row->details)) : ?>
                                <small><code class="cstemplateintegrity-detail-code"><?php echo $this->escape($row->details); ?></code></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
