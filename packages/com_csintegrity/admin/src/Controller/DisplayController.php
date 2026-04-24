<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\Controller;

defined('_JEXEC') or die;

use Cybersalt\Component\Csintegrity\Administrator\Helper\RescanHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Throwable;

final class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    public function rescan(): void
    {
        $this->checkToken();

        $redirect = Route::_('index.php?option=com_csintegrity&view=dashboard', false);
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

        $this->setRedirect($redirect);
    }
}
