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

$curlExample = sprintf(
    "curl -H \"X-Joomla-Token: \$TOKEN\" \\\n     -H \"Accept: application/vnd.api+json\" \\\n     %s",
    $this->overridesEndpoint
);
$rescanAction = Route::_('index.php?option=com_csintegrity', false);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_STATUS_TITLE'); ?></h3>
                    <p class="card-text">
                        <span class="badge bg-success">
                            <span class="icon-publish" aria-hidden="true"></span>
                            <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_STATUS_ACTIVE'); ?>
                        </span>
                    </p>
                    <p class="card-text"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_STATUS_DESCRIPTION'); ?></p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_ENDPOINT_TITLE'); ?></h3>
                    <p class="card-text"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_ENDPOINT_DESCRIPTION'); ?></p>
                    <pre class="mb-3"><code><?php echo $this->escape($this->overridesEndpoint); ?></code></pre>
                    <p class="card-text">
                        <strong><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_AUTH_LABEL'); ?></strong>
                        <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_AUTH_DESCRIPTION'); ?>
                    </p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_SMOKE_TEST_TITLE'); ?></h3>
                    <p class="card-text"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_SMOKE_TEST_DESCRIPTION'); ?></p>
                    <pre class="mb-0"><code><?php echo $this->escape($curlExample); ?></code></pre>
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
                        <small class="text-muted">
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

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_ABOUT_TITLE'); ?></h3>
                    <p class="card-text"><?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_ABOUT_DESCRIPTION'); ?></p>
                    <p class="card-text">
                        <small class="text-muted">
                            <?php echo Text::_('COM_CSINTEGRITY_DASHBOARD_VERSION_LABEL'); ?>: 0.3.0
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
