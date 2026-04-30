<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * @var \Cybersalt\Component\Cstemplateintegrity\Administrator\View\Dashboard\HtmlView $this
 */

declare(strict_types=1);

defined('_JEXEC') or die;

// First-run disclaimer modal (rendered only if the current admin
// user has not yet ticked "do not show again").
echo \Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\DisclaimerHelper::renderModalIfNeeded();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$rescanAction       = Route::_('index.php?option=com_cstemplateintegrity', false);
$runScanAction      = Route::_('index.php?option=com_cstemplateintegrity', false);
$siteTemplatesUrl   = Route::_('index.php?option=com_templates&view=templates&client_id=0', false);
$sessionsUrl        = Route::_('index.php?option=com_cstemplateintegrity&view=sessions', false);
$actionsUrl         = Route::_('index.php?option=com_cstemplateintegrity&view=actions', false);
$backupsUrl         = Route::_('index.php?option=com_cstemplateintegrity&view=backups', false);
?>

<div class="container-fluid cstemplateintegrity-dashboard">

    <!-- Navigation row — "where do I go" buttons. Alphabetical so the
         user can hunt by name; ms-auto removed because flex-wrap was
         occasionally hiding the button to its right on narrow widths
         (Action log was vanishing). All items now flow left-to-right
         in one line; on narrow screens they wrap predictably. -->
    <div class="d-flex flex-wrap gap-2 mb-2 align-items-center" role="navigation" aria-label="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_QUICKNAV_LABEL')); ?>">
        <a href="<?php echo $this->escape($actionsUrl); ?>" class="btn btn-secondary">
            <span class="icon-clock" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_SUBMENU_ACTIONS'); ?>
        </a>
        <a href="<?php echo $this->escape($backupsUrl); ?>" class="btn btn-secondary">
            <span class="icon-archive" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_SUBMENU_BACKUPS'); ?>
        </a>
        <a href="<?php echo $this->escape($siteTemplatesUrl); ?>"
           class="btn btn-secondary"
           target="_blank"
           rel="noopener noreferrer">
            <span class="icon-arrow-right" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_OPTION_A_BUTTON'); ?>
        </a>
        <a href="<?php echo $this->escape($sessionsUrl); ?>" class="btn btn-secondary">
            <span class="icon-list" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_SUBMENU_SESSIONS'); ?>
        </a>
        <form action="<?php echo $this->escape($rescanAction); ?>"
              method="post"
              class="d-inline m-0"
              onsubmit="return confirm('<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_RESCAN_CONFIRM'), 'JavaScript'); ?>');">
            <?php echo HTMLHelper::_('form.token'); ?>
            <input type="hidden" name="task" value="display.rescan">
            <button type="submit" class="btn csti-rescan-btn"
                    title="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_RESCAN_TOOLTIP')); ?>">
                <span class="icon-refresh" aria-hidden="true"></span>
                <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_RESCAN_BUTTON'); ?>
            </button>
        </form>
        <button type="button" class="btn btn-info" data-csti-open-diag>
            <span class="icon-info" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_BUTTON'); ?>
        </button>
    </div>

    <!--
        Action shortcuts row — the two "what do you want to do today"
        method buttons. Method 1 (claude.ai / Code copy-paste) is left,
        works without an API key. Method 2 (server-side automated scan)
        is right; it renders as an outlined-secondary button when no
        Anthropic API key is saved (anchor-link still works and drops
        the user on the Method 2 card, which explains the missing key).
    -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="#csti-manual-card" class="btn csti-method-1-btn btn-lg"
           title="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ACTION_MANUAL_TOOLTIP')); ?>">
            <span class="icon-copy" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ACTION_MANUAL'); ?>
        </a>
        <?php if ($this->hasApiKey) : ?>
            <a href="#csti-autoscan-card" class="btn btn-primary btn-lg"
               title="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ACTION_AUTOSCAN_TOOLTIP_OK')); ?>">
                <span class="icon-rocket" aria-hidden="true"></span>
                <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ACTION_AUTOSCAN'); ?>
            </a>
        <?php else : ?>
            <a href="#csti-autoscan-card" class="btn btn-outline-secondary btn-lg"
               title="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ACTION_AUTOSCAN_TOOLTIP_NOKEY')); ?>">
                <span class="icon-rocket" aria-hidden="true"></span>
                <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ACTION_AUTOSCAN'); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Method 1: copy-paste prompt into Claude.ai or Claude Code. -->
    <div id="csti-manual-card" class="card mb-3 border-info cstemplateintegrity-prompt-card" style="scroll-margin-top: 80px;">
        <div class="card-body">
            <h3 class="card-title">
                <span class="icon-flash" aria-hidden="true"></span>
                <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_TITLE'); ?>
            </h3>
            <p class="card-text"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_INTRO'); ?></p>

            <ol class="mb-3">
                <li class="mb-2">
                    <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_STEP1_TITLE'); ?></strong>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_STEP1_BODY'); ?>
                </li>
                <li class="mb-2">
                    <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_STEP2_TITLE'); ?></strong>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_STEP2_BODY'); ?>
                </li>
                <li class="mb-2">
                    <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_STEP3_TITLE'); ?></strong>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_STEP3_BODY'); ?>
                </li>
                <li class="mb-2">
                    <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_STEP4_TITLE'); ?></strong>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_STEP4_BODY'); ?>
                </li>
            </ol>

            <p class="card-text mb-2">
                <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_PROMPT_LABEL'); ?></strong>
            </p>
            <pre class="cstemplateintegrity-codeblock mb-2"><code id="cstemplateintegrity-prompt"><?php echo $this->escape($this->claudePrompt); ?></code></pre>
            <button type="button"
                    class="btn btn-primary"
                    id="cstemplateintegrity-copy-btn"
                    data-default-label="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_COPY_BUTTON')); ?>"
                    data-copied-label="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_COPIED')); ?>">
                <span class="icon-copy" aria-hidden="true"></span>
                <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_COPY_BUTTON'); ?>
            </button>
        </div>
    </div>

    <!-- Continue-previous-review prompt — still part of the Method 1
         (copy-paste) family, so it sits directly under the Method 1
         card and *above* Method 2. -->
    <div class="card mb-3 border-warning cstemplateintegrity-prompt-card">
        <div class="card-body">
            <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                <span class="icon-warning me-2 fs-4" aria-hidden="true"></span>
                <div>
                    <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_FIX_AUDIENCE_TITLE'); ?></strong>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_FIX_AUDIENCE_BODY'); ?>
                </div>
            </div>

            <h3 class="card-title">
                <span class="icon-wrench" aria-hidden="true"></span>
                <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_FIX_TITLE'); ?>
            </h3>
            <p class="card-text"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_FIX_INTRO'); ?></p>
            <pre class="cstemplateintegrity-codeblock mb-2"><code id="cstemplateintegrity-fix-prompt"><?php echo $this->escape($this->fixPrompt); ?></code></pre>
            <button type="button"
                    class="btn btn-primary"
                    id="cstemplateintegrity-fix-copy-btn"
                    data-default-label="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_FIX_COPY_BUTTON')); ?>"
                    data-copied-label="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_COPIED')); ?>">
                <span class="icon-copy" aria-hidden="true"></span>
                <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_FIX_COPY_BUTTON'); ?>
            </button>
        </div>
    </div>

    <!-- Method 2: server-side automated scan via saved Anthropic API key.
         Lives at the bottom so the Method 2 anchor link from the top
         scrolls all the way past the copy-paste section to land here. -->
    <?php if ($this->hasApiKey) : ?>
        <div id="csti-autoscan-card" class="card mb-3 border-primary" style="scroll-margin-top: 80px;">
            <div class="card-body">
                <h3 class="card-title">
                    <span class="icon-rocket" aria-hidden="true"></span>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_TITLE'); ?>
                </h3>
                <div class="alert alert-primary d-flex align-items-center mb-3" role="alert">
                    <span class="icon-flash me-2 fs-4" aria-hidden="true"></span>
                    <div>
                        <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_AVAILABLE_TITLE'); ?></strong>
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_AVAILABLE_BODY'); ?>
                    </div>
                </div>
                <p class="card-text"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_INTRO'); ?></p>
                <p class="card-text">
                    <small class="text-body-secondary">
                        <?php echo Text::sprintf('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_NOTE', (int) $this->autoScanMaxOverrides, (int) $this->autoScanMaxOverrides); ?>
                    </small>
                </p>
                <form action="<?php echo $this->escape($runScanAction); ?>"
                      method="post"
                      data-csti-runscan
                      data-confirm-text="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_CONFIRM')); ?>"
                      data-loading-title="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_LOADING_TITLE')); ?>"
                      data-loading-body="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_LOADING_BODY')); ?>">
                    <?php echo HTMLHelper::_('form.token'); ?>
                    <input type="hidden" name="task" value="display.runScan">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <span class="icon-rocket" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_BUTTON'); ?>
                    </button>
                </form>
            </div>
        </div>
    <?php else : ?>
        <div id="csti-autoscan-card" class="card mb-3 border-warning" style="scroll-margin-top: 80px;">
            <div class="card-body">
                <h3 class="card-title">
                    <span class="icon-rocket" aria-hidden="true"></span>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_TITLE'); ?>
                </h3>
                <div class="alert alert-warning d-flex align-items-center mb-0" role="alert">
                    <span class="icon-warning me-2 fs-4" aria-hidden="true"></span>
                    <div>
                        <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_NOKEY_TITLE'); ?></strong>
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_NOKEY_BODY'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- About / endpoint / version footer. Was a right-column sidebar
         until v2.1; moved here so the action methods get full width. -->
    <div class="card mb-0 border-secondary cstemplateintegrity-about-footer">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-1"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ABOUT_TITLE'); ?></h5>
                    <p class="card-text mb-0">
                        <small class="text-body-secondary"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ABOUT_DESCRIPTION'); ?></small>
                    </p>
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    <p class="card-text mb-1" style="word-break: break-all;">
                        <small>
                            <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ENDPOINT_LABEL'); ?>:</strong>
                            <code><?php echo $this->escape($this->overridesEndpoint); ?></code>
                        </small>
                    </p>
                    <p class="card-text mb-0">
                        <small class="text-body-secondary">
                            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_VERSION_LABEL'); ?>:
                            <?php echo $this->escape($this->componentVersion ?: '?'); ?>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

<div id="csti-diag-overlay" class="csti-diag-overlay" role="dialog" aria-modal="true" aria-labelledby="csti-diag-title">
    <div class="csti-diag-card">
        <h3 id="csti-diag-title">
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_TITLE'); ?>
            <button type="button" class="btn btn-sm btn-secondary" data-csti-diag-close>
                <?php echo Text::_('JCANCEL'); ?>
            </button>
        </h3>

        <h4><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_API_KEY'); ?></h4>
        <div class="csti-diag-row">
            <span class="label"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_API_KEY_STATUS'); ?></span>
            <span class="value">
                <?php if ($this->hasApiKey) : ?>
                    <span class="csti-diag-result is-pass"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_SAVED'); ?></span>
                <?php else : ?>
                    <span class="csti-diag-result is-fail"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_NOT_SAVED'); ?></span>
                <?php endif; ?>
            </span>
        </div>
        <?php if ($this->hasApiKey) : ?>
            <div class="csti-diag-row">
                <span class="label"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_FINGERPRINT'); ?></span>
                <span class="value"><?php echo $this->escape($this->apiKeyFingerprint); ?></span>
            </div>
        <?php endif; ?>

        <h4><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_TEST'); ?></h4>
        <p class="text-body-secondary mb-2">
            <small><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_TEST_INTRO'); ?></small>
        </p>
        <button type="button"
                class="btn btn-primary"
                data-csti-test-conn
                data-test-url="<?php echo $this->escape($this->testConnectionUrl); ?>"
                <?php if (!$this->hasApiKey) : ?>disabled<?php endif; ?>>
            <span class="icon-flash" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_TEST_BUTTON'); ?>
        </button>
        <div id="csti-diag-test-result" class="mt-2"></div>

        <h4><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_SYSTEM'); ?></h4>
        <div class="csti-diag-row">
            <span class="label"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_VERSION_LABEL'); ?></span>
            <span class="value"><?php echo $this->escape($this->componentVersion ?: '?'); ?></span>
        </div>
        <div class="csti-diag-row">
            <span class="label">Joomla</span>
            <span class="value"><?php echo $this->escape(JVERSION); ?></span>
        </div>
        <div class="csti-diag-row">
            <span class="label">PHP</span>
            <span class="value"><?php echo $this->escape(PHP_VERSION); ?></span>
        </div>
        <div class="csti-diag-row">
            <span class="label"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_API_BASE'); ?></span>
            <span class="value"><?php echo $this->escape($this->apiBase); ?></span>
        </div>
        <div class="csti-diag-row">
            <span class="label"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_AUTOSCAN_CAP'); ?></span>
            <span class="value"><?php echo $this->escape($this->autoScanMaxOverrides); ?> overrides per call</span>
        </div>
    </div>
</div>