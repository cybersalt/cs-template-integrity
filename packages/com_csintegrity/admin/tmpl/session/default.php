<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * @var \Cybersalt\Component\Csintegrity\Administrator\View\Session\HtmlView $this
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$session = $this->session;
?>

<div class="container-fluid csintegrity-dashboard">
    <div class="row">
        <div class="col-lg-9">
            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="card-title">
                        <span class="icon-eye" aria-hidden="true"></span>
                        <?php echo $this->escape($session->name); ?>
                    </h3>
                    <p class="card-text">
                        <span class="badge bg-secondary"><?php echo $this->escape($session->source); ?></span>
                        <small class="text-body-secondary ms-2">
                            <?php echo HTMLHelper::_('date', $session->created_at, Text::_('DATE_FORMAT_LC2')); ?>
                        </small>
                    </p>
                    <?php if (!empty($session->summary)) : ?>
                        <p class="card-text"><strong><?php echo $this->escape($session->summary); ?></strong></p>
                    <?php endif; ?>

                    <h4 class="mt-4"><?php echo Text::_('COM_CSINTEGRITY_SESSION_REPORT'); ?></h4>
                    <?php if (empty($session->report_markdown)) : ?>
                        <p class="text-body-secondary"><?php echo Text::_('COM_CSINTEGRITY_SESSION_REPORT_EMPTY'); ?></p>
                    <?php else : ?>
                        <pre class="csintegrity-codeblock"><code><?php echo $this->escape($session->report_markdown); ?></code></pre>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title"><?php echo Text::_('COM_CSINTEGRITY_SESSION_ACTIONS'); ?></h4>
                    <?php if (empty($this->actions)) : ?>
                        <p class="text-body-secondary"><?php echo Text::_('COM_CSINTEGRITY_SESSION_ACTIONS_EMPTY'); ?></p>
                    <?php else : ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($this->actions as $action) : ?>
                                <li class="mb-2">
                                    <code><?php echo $this->escape($action->action); ?></code>
                                    <br>
                                    <small class="text-body-secondary">
                                        <?php echo HTMLHelper::_('date', $action->created_at, Text::_('DATE_FORMAT_LC5')); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
