<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Cstemplateintegrity\Administrator\Controller;

defined('_JEXEC') or die;

use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\DisclaimerHelper;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\MarkReviewedHelper;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\PermissionHelper;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\RescanHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
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
                    'COM_CSTEMPLATEINTEGRITY_RESCAN_SUCCESS',
                    $stats['inserted'],
                    $stats['scanned'],
                    $stats['templates']
                ),
                'success'
            );
        } catch (Throwable $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_CSTEMPLATEINTEGRITY_RESCAN_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_cstemplateintegrity&view=dashboard', false));
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
                $app->enqueueMessage(Text::_('COM_CSTEMPLATEINTEGRITY_MARK_REVIEWED_NONE'), 'info');
            } else {
                $app->enqueueMessage(
                    Text::sprintf('COM_CSTEMPLATEINTEGRITY_MARK_REVIEWED_SUCCESS', $cleared),
                    'success'
                );
            }
        } catch (Throwable $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_CSTEMPLATEINTEGRITY_MARK_REVIEWED_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_cstemplateintegrity&view=dashboard', false));
    }

    /**
     * Persist a "don't show again" click on the first-run disclaimer
     * for the current logged-in user. Posted via fetch() from the
     * modal's inline JS — no redirect, returns a tiny JSON payload.
     */
    public function acknowledgeDisclaimer(): void
    {
        $this->checkToken();
        // No PermissionHelper gate here on purpose — every authenticated
        // admin who can SEE the modal must be able to dismiss it; we
        // gate the modal's APPEARANCE on hasAcknowledged(), not on
        // arbitrary permissions.

        /** @var CMSApplication $app */
        $app  = $this->app;
        $user = $app->getIdentity();
        $uid  = $user ? (int) $user->id : 0;

        if ($uid > 0) {
            DisclaimerHelper::acknowledge($uid);
        }

        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();
        echo json_encode(['acknowledged' => $uid > 0]);
        $app->close();
    }
}
