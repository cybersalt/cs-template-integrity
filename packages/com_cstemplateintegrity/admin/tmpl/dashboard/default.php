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
$markReviewedAction = Route::_('index.php?option=com_cstemplateintegrity', false);
$runScanAction      = Route::_('index.php?option=com_cstemplateintegrity', false);
$siteTemplatesUrl   = Route::_('index.php?option=com_templates&view=templates&client_id=0', false);
$sessionsUrl        = Route::_('index.php?option=com_cstemplateintegrity&view=sessions', false);
$newSessionUrl      = Route::_('index.php?option=com_cstemplateintegrity&view=sessionform', false);
$actionsUrl         = Route::_('index.php?option=com_cstemplateintegrity&view=actions', false);
$backupsUrl         = Route::_('index.php?option=com_cstemplateintegrity&view=backups', false);
?>

<div class="container-fluid cstemplateintegrity-dashboard">

    <div class="alert alert-success d-flex align-items-center" role="alert">
        <span class="icon-publish me-2" aria-hidden="true"></span>
        <div>
            <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_STATUS_ACTIVE'); ?>.</strong>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_STATUS_DESCRIPTION'); ?>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3" role="navigation" aria-label="<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_QUICKNAV_LABEL')); ?>">
        <a href="<?php echo $this->escape($newSessionUrl); ?>" class="btn btn-info">
            <span class="icon-plus" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_SESSIONS_NEW'); ?>
        </a>
        <a href="<?php echo $this->escape($sessionsUrl); ?>" class="btn btn-secondary">
            <span class="icon-list" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_SUBMENU_SESSIONS'); ?>
        </a>
        <a href="<?php echo $this->escape($actionsUrl); ?>" class="btn btn-secondary">
            <span class="icon-clock" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_SUBMENU_ACTIONS'); ?>
        </a>
        <a href="<?php echo $this->escape($backupsUrl); ?>" class="btn btn-secondary">
            <span class="icon-archive" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_SUBMENU_BACKUPS'); ?>
        </a>
        <a href="<?php echo $this->escape($siteTemplatesUrl); ?>" class="btn btn-secondary">
            <span class="icon-arrow-right" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_OPTION_A_BUTTON'); ?>
        </a>
        <button type="button" class="btn btn-info ms-auto" data-csti-open-diag>
            <span class="icon-info" aria-hidden="true"></span>
            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_DIAGNOSTICS_BUTTON'); ?>
        </button>
    </div>

    <div class="card mb-3 border-warning">
        <div class="card-body">
            <h3 class="card-title">
                <span class="icon-refresh" aria-hidden="true"></span>
                <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_RESCAN_TITLE'); ?>
            </h3>
            <p class="card-text"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_RESCAN_DESCRIPTION'); ?></p>
            <p class="card-text">
                <small class="text-body-secondary">
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_RESCAN_NOTE'); ?>
                </small>
            </p>
            <form action="<?php echo $this->escape($rescanAction); ?>"
                  method="post"
                  onsubmit="return confirm('<?php echo $this->escape(Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_RESCAN_CONFIRM'), 'JavaScript'); ?>');">
                <?php echo HTMLHelper::_('form.token'); ?>
                <input type="hidden" name="task" value="display.rescan">
                <button type="submit" class="btn btn-warning">
                    <span class="icon-refresh" aria-hidden="true"></span>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_RESCAN_BUTTON'); ?>
                </button>
            </form>
        </div>
    </div>

    <?php if ($this->hasApiKey) : ?>
        <div class="card mb-3 border-success">
            <div class="card-body">
                <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                    <span class="icon-flash me-2 fs-4" aria-hidden="true"></span>
                    <div>
                        <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_AVAILABLE_TITLE'); ?></strong>
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_AVAILABLE_BODY'); ?>
                    </div>
                </div>
                <h3 class="card-title">
                    <span class="icon-rocket" aria-hidden="true"></span>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_TITLE'); ?>
                </h3>
                <p class="card-text"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_INTRO'); ?></p>
                <p class="card-text">
                    <small class="text-body-secondary">
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_NOTE'); ?>
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
                    <button type="submit" class="btn btn-success">
                        <span class="icon-rocket" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_BUTTON'); ?>
                    </button>
                </form>
            </div>
        </div>
    <?php else : ?>
        <div class="card mb-3 border-secondary">
            <div class="card-body">
                <h3 class="card-title">
                    <span class="icon-rocket" aria-hidden="true"></span>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_TITLE'); ?>
                </h3>
                <p class="card-text"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_AUTOSCAN_NOKEY_BODY'); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">

            <div class="card mb-3 border-info">
                <div class="card-body">
                    <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                        <span class="icon-checkmark-circle me-2 fs-4" aria-hidden="true"></span>
                        <div>
                            <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_AUDIENCE_TITLE'); ?></strong>
                            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_USAGE_AUDIENCE_BODY'); ?>
                        </div>
                    </div>

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

            <div class="card mb-3 border-warning">
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

            <div class="card mb-3 border-info">
                <div class="card-body">
                    <h3 class="card-title">
                        <span class="icon-list" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_SESSIONS_TITLE'); ?>
                    </h3>
                    <p class="card-text"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_SESSIONS_INTRO'); ?></p>

                    <?php if (empty($this->recentSessions)) : ?>
                        <p class="text-body-secondary"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_SESSIONS_EMPTY'); ?></p>
                    <?php else : ?>
                        <ul class="list-unstyled mb-3">
                            <?php foreach ($this->recentSessions as $row) : ?>
                                <li class="mb-2">
                                    <a href="<?php echo $this->escape(Route::_('index.php?option=com_cstemplateintegrity&view=session&id=' . (int) $row->id . '&from=dashboard', false)); ?>">
                                        <?php echo $this->escape($row->name); ?>
                                    </a>
                                    <span class="badge bg-secondary ms-2"><?php echo $this->escape($row->source); ?></span>
                                    <?php if (!empty($row->summary)) : ?>
                                        <br><small class="text-body-secondary"><?php echo $this->escape($row->summary); ?></small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <a href="<?php echo $this->escape($newSessionUrl); ?>" class="btn btn-info">
                        <span class="icon-plus" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_SESSIONS_NEW'); ?>
                    </a>
                    <a href="<?php echo $this->escape($sessionsUrl); ?>" class="btn btn-secondary">
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_SESSIONS_ALL'); ?>
                    </a>
                </div>
            </div>

            <div class="card mb-3 border-success">
                <div class="card-body">
                    <h3 class="card-title">
                        <span class="icon-checkmark" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_TITLE'); ?>
                    </h3>
                    <p class="card-text"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_INTRO'); ?></p>

                    <div class="mb-3">
                        <p class="card-text mb-2">
                            <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_OPTION_A_LABEL'); ?></strong>
                        </p>
                        <p class="card-text mb-2"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_OPTION_A_BODY'); ?></p>
                        <a href="<?php echo $this->escape($siteTemplatesUrl); ?>" class="btn btn-secondary">
                            <span class="icon-arrow-right" aria-hidden="true"></span>
                            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_OPTION_A_BUTTON'); ?>
                        </a>
                    </div>

                    <hr>

                    <div>
                        <p class="card-text mb-2">
                            <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_OPTION_B_LABEL'); ?></strong>
                        </p>
                        <p class="card-text mb-2"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_OPTION_B_BODY'); ?></p>
                        <button type="button" class="btn btn-success"
                                data-bs-toggle="modal"
                                data-bs-target="#cstemplateintegrity-mark-reviewed-modal">
                            <span class="icon-checkmark-circle" aria-hidden="true"></span>
                            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_OPTION_B_BUTTON'); ?>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ABOUT_TITLE'); ?></h3>
                    <p class="card-text"><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ABOUT_DESCRIPTION'); ?></p>
                    <hr>
                    <p class="card-text mb-1">
                        <small class="text-body-secondary">
                            <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_ENDPOINT_LABEL'); ?></strong>
                        </small>
                    </p>
                    <p class="card-text mb-2" style="word-break: break-all;">
                        <small><code><?php echo $this->escape($this->overridesEndpoint); ?></code></small>
                    </p>
                    <p class="card-text mb-0">
                        <small class="text-body-secondary">
                            <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_VERSION_LABEL'); ?>: <?php echo $this->escape($this->componentVersion ?: '?'); ?>
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

<div class="modal fade" id="cstemplateintegrity-mark-reviewed-modal" tabindex="-1" aria-labelledby="cstemplateintegrity-mark-reviewed-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cstemplateintegrity-mark-reviewed-modal-title">
                    <span class="icon-warning" aria-hidden="true"></span>
                    <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_MODAL_TITLE'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo $this->escape(Text::_('JCANCEL')); ?>"></button>
            </div>
            <div class="modal-body">
                <p><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_MODAL_BODY'); ?></p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="cstemplateintegrity-mark-reviewed-confirm-check">
                    <label class="form-check-label" for="cstemplateintegrity-mark-reviewed-confirm-check">
                        <strong><?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_MODAL_AGREE'); ?></strong>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo Text::_('JCANCEL'); ?>
                </button>
                <form action="<?php echo $this->escape($markReviewedAction); ?>" method="post" class="d-inline">
                    <?php echo HTMLHelper::_('form.token'); ?>
                    <input type="hidden" name="task" value="display.markReviewed">
                    <button type="submit"
                            class="btn btn-success"
                            id="cstemplateintegrity-mark-reviewed-confirm-btn"
                            disabled>
                        <span class="icon-checkmark-circle" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CSTEMPLATEINTEGRITY_DASHBOARD_REVIEWED_MODAL_CONFIRM'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
