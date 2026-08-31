<?php

/**
 * @package     Csoverridechecker
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * @var \Cybersalt\Component\Csoverridechecker\Administrator\View\Support\HtmlView $this
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>

<div class="container-fluid csoverridechecker-support">

    <!-- Free-forever statement. This extension has no Pro tier, so there is
         deliberately no licence banner and no paid-priority upsell here. -->
    <div class="card mb-3 border-success">
        <div class="card-body">
            <h3 class="card-title">
                <span class="icon-heart" aria-hidden="true"></span>
                <?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_FREE_HEADING'); ?>
            </h3>
            <p class="card-text mb-0"><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_FREE_BODY'); ?></p>
        </div>
    </div>

    <!-- Set expectations honestly rather than implying guaranteed support. -->
    <div class="alert alert-info" role="alert">
        <span class="icon-info-circle me-2" aria-hidden="true"></span>
        <strong><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_EXPECT_TITLE'); ?></strong>
        <?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_EXPECT_BODY'); ?>
    </div>

    <div class="row">
        <div class="col-lg-7">

            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title"><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_WHERE_HEADING'); ?></h4>
                    <p class="card-text"><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_WHERE_INTRO'); ?></p>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="<?php echo $this->escape($this->issuesUrl); ?>"
                           class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                            <span class="icon-bug" aria-hidden="true"></span>
                            <?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_BTN_ISSUES'); ?>
                        </a>
                        <a href="<?php echo $this->escape($this->docsUrl); ?>"
                           class="btn btn-secondary" target="_blank" rel="noopener noreferrer">
                            <span class="icon-book" aria-hidden="true"></span>
                            <?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_BTN_DOCS'); ?>
                        </a>
                        <a href="<?php echo $this->escape($this->websiteUrl); ?>"
                           class="btn btn-secondary" target="_blank" rel="noopener noreferrer">
                            <span class="icon-out-2" aria-hidden="true"></span>
                            <?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_BTN_WEBSITE'); ?>
                        </a>
                    </div>

                    <p class="card-text mb-0">
                        <small class="text-body-secondary">
                            <?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_WHERE_PUBLIC_NOTE'); ?>
                        </small>
                    </p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title"><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_INCLUDE_HEADING'); ?></h4>
                    <p class="card-text"><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_INCLUDE_INTRO'); ?></p>
                    <ul class="mb-0">
                        <li><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_INCLUDE_ITEM1'); ?></li>
                        <li><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_INCLUDE_ITEM2'); ?></li>
                        <li><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_INCLUDE_ITEM3'); ?></li>
                        <li><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_INCLUDE_ITEM4'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="card mb-3 border-info">
                <div class="card-body">
                    <h4 class="card-title"><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_FEEDBACK_HEADING'); ?></h4>
                    <p class="card-text mb-0"><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_FEEDBACK_BODY'); ?></p>
                </div>
            </div>

        </div>

        <div class="col-lg-5">
            <div class="card mb-3 border-secondary">
                <div class="card-body">
                    <h4 class="card-title"><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_DIAG_HEADING'); ?></h4>
                    <p class="card-text">
                        <small><?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_DIAG_INTRO'); ?></small>
                    </p>

                    <pre class="csoverridechecker-codeblock mb-2"><code id="csti-support-diag"><?php
                        echo $this->escape($this->diagnosticsBlock);
                    ?></code></pre>

                    <button type="button"
                            class="btn btn-primary"
                            id="csti-support-copy"
                            data-default-label="<?php echo $this->escape(Text::_('COM_CSOVERRIDECHECKER_SUPPORT_DIAG_COPY')); ?>"
                            data-copied-label="<?php echo $this->escape(Text::_('COM_CSOVERRIDECHECKER_DASHBOARD_USAGE_COPIED')); ?>">
                        <span class="icon-copy" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_DIAG_COPY'); ?>
                    </button>

                    <p class="card-text mt-3 mb-0">
                        <small class="text-body-secondary">
                            <?php echo Text::_('COM_CSOVERRIDECHECKER_SUPPORT_DIAG_NOSECRETS'); ?>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
