<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * @var \Cybersalt\Component\Csintegrity\Administrator\View\Sessionform\HtmlView $this
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$action = Route::_('index.php?option=com_csintegrity&task=sessions.save', false);
?>

<div class="container-fluid csintegrity-dashboard">
    <p class="mb-3">
        <a href="<?php echo $this->escape($this->backUrl); ?>" class="btn btn-secondary">
            <span class="icon-arrow-left" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSINTEGRITY_SESSION_BACK_TO_LIST'); ?>
        </a>
    </p>

    <form action="<?php echo $this->escape($action); ?>" method="post" id="csintegrity-session-form">
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="card-title"><?php echo Text::_('COM_CSINTEGRITY_SESSIONFORM_HEADING'); ?></h3>
                <p class="card-text"><?php echo Text::_('COM_CSINTEGRITY_SESSIONFORM_INTRO'); ?></p>

                <div class="mb-3">
                    <label for="jform_name" class="form-label">
                        <?php echo Text::_('COM_CSINTEGRITY_SESSIONFORM_NAME'); ?>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="jform_name"
                           name="jform[name]"
                           maxlength="64"
                           value="<?php echo $this->escape($this->defaultName); ?>">
                    <small class="form-text text-body-secondary">
                        <?php echo Text::_('COM_CSINTEGRITY_SESSIONFORM_NAME_HELP'); ?>
                    </small>
                </div>

                <div class="mb-3">
                    <label for="jform_summary" class="form-label">
                        <?php echo Text::_('COM_CSINTEGRITY_SESSIONFORM_SUMMARY'); ?>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="jform_summary"
                           name="jform[summary]"
                           maxlength="500"
                           placeholder="<?php echo $this->escape(Text::_('COM_CSINTEGRITY_SESSIONFORM_SUMMARY_PLACEHOLDER')); ?>">
                </div>

                <div class="mb-3">
                    <label for="jform_report_markdown" class="form-label">
                        <?php echo Text::_('COM_CSINTEGRITY_SESSIONFORM_REPORT'); ?>
                    </label>
                    <textarea class="form-control"
                              id="jform_report_markdown"
                              name="jform[report_markdown]"
                              rows="20"
                              placeholder="<?php echo $this->escape(Text::_('COM_CSINTEGRITY_SESSIONFORM_REPORT_PLACEHOLDER')); ?>"></textarea>
                </div>

                <button type="submit" class="btn btn-info">
                    <span class="icon-save" aria-hidden="true"></span>
                    <?php echo Text::_('COM_CSINTEGRITY_SESSIONFORM_SAVE'); ?>
                </button>
            </div>
        </div>

        <input type="hidden" name="task" value="sessions.save">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
