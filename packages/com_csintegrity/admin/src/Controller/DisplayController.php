<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\Controller;

defined('_JEXEC') or die;

use Cybersalt\Component\Csintegrity\Administrator\Helper\MarkReviewedHelper;
use Cybersalt\Component\Csintegrity\Administrator\Helper\PermissionHelper;
use Cybersalt\Component\Csintegrity\Administrator\Helper\RescanHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Throwable;

final class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    public function rescan(): void
    {
        $this->checkToken();
        PermissionHelper::requireWrite();

        /** @var CMSApplication $app */
        $app = $this->app;

        try {
            $stats = RescanHelper::rebuildOverrideTracker();
            $app->enqueueMessage(
                Text::sprintf(
                    'COM_CSINTEGRITY_RESCAN_SUCCESS',
                    $stats['inserted'],
                    $stats['scanned'],
                    $stats['templates']
                ),
                'success'
            );
        } catch (Throwable $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_CSINTEGRITY_RESCAN_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=dashboard', false));
    }

    public function markReviewed(): void
    {
        $this->checkToken();
        PermissionHelper::requireWrite();

        /** @var CMSApplication $app */
        $app = $this->app;

        try {
            $cleared = MarkReviewedHelper::clearAllOverrides();

            if ($cleared === 0) {
                $app->enqueueMessage(Text::_('COM_CSINTEGRITY_MARK_REVIEWED_NONE'), 'info');
            } else {
                $app->enqueueMessage(
                    Text::sprintf('COM_CSINTEGRITY_MARK_REVIEWED_SUCCESS', $cleared),
                    'success'
                );
            }
        } catch (Throwable $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_CSINTEGRITY_MARK_REVIEWED_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=dashboard', false));
    }
}
