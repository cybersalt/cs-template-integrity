<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * @var \Cybersalt\Component\Csintegrity\Administrator\View\Dashboard\HtmlView $this
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$rescanAction = Route::_('index.php?option=com_csintegrity', false);
?>

<div class="container-fluid csintegrity-dashboard">

    <div class="alert alert-success d-flex align-items-center" role="alert">
        <span class="icon-publish me-2" aria-hidden="true"></span>
        <div>
            <strong><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_STATUS_ACTIVE'); ?>.</strong>
            <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_STATUS_DESCRIPTION'); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">

            <div class="card mb-3 border-info">
                <div class="card-body">
                    <h3 class="card-title">
                        <span class="icon-flash" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_TITLE'); ?>
                    </h3>
                    <p class="card-text"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_INTRO'); ?></p>

                    <ol class="mb-3">
                        <li class="mb-2">
                            <strong><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_STEP1_TITLE'); ?></strong>
                            <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_STEP1_BODY'); ?>
                        </li>
                        <li class="mb-2">
                            <strong><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_STEP2_TITLE'); ?></strong>
                            <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_STEP2_BODY'); ?>
                        </li>
                        <li class="mb-2">
                            <strong><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_STEP3_TITLE'); ?></strong>
                            <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_STEP3_BODY'); ?>
                        </li>
                    </ol>

                    <p class="card-text mb-2">
                        <strong><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_PROMPT_LABEL'); ?></strong>
                    </p>
                    <pre class="csintegrity-codeblock mb-2"><code id="csintegrity-prompt"><?php echo $this->escape($this->claudePrompt); ?></code></pre>
                    <button type="button"
                            class="btn btn-outline-info btn-sm"
                            id="csintegrity-copy-btn"
                            data-default-label="<?php echo $this->escape(Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_COPY_BUTTON')); ?>"
                            data-copied-label="<?php echo $this->escape(Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_COPIED')); ?>">
                        <span class="icon-copy" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_USAGE_COPY_BUTTON'); ?>
                    </button>
                </div>
            </div>

            <div class="card mb-3 border-warning">
                <div class="card-body">
                    <h3 class="card-title">
                        <span class="icon-refresh" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_RESCAN_TITLE'); ?>
                    </h3>
                    <p class="card-text"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_RESCAN_DESCRIPTION'); ?></p>
                    <p class="card-text">
                        <small class="text-body-secondary">
                            <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_RESCAN_NOTE'); ?>
                        </small>
                    </p>
                    <form action="<?php echo $this->escape($rescanAction); ?>"
                          method="post"
                          onsubmit="return confirm('<?php echo $this->escape(Text::_('COM_CSINTEGRITY_DASHBOARD_RESCAN_CONFIRM'), 'JavaScript'); ?>');">
                        <?php echo HTMLHelper::_('form.token'); ?>
                        <input type="hidden" name="task" value="display.rescan">
                        <button type="submit" class="btn btn-warning">
                            <span class="icon-refresh" aria-hidden="true"></span>
                            <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_RESCAN_BUTTON'); ?>
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_ABOUT_TITLE'); ?></h3>
                    <p class="card-text"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_ABOUT_DESCRIPTION'); ?></p>
                    <hr>
                    <p class="card-text mb-1">
                        <small class="text-body-secondary">
                            <strong><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_ENDPOINT_LABEL'); ?></strong>
                        </small>
                    </p>
                    <p class="card-text mb-2" style="word-break: break-all;">
                        <small><code><?php echo $this->escape($this->overridesEndpoint); ?></code></small>
                    </p>
                    <p class="card-text mb-0">
                        <small class="text-body-secondary">
                            <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_VERSION_LABEL'); ?>: 0.4.1
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
