<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\Controller;

defined('_JEXEC') or die;

use Cybersalt\Component\Csintegrity\Administrator\Helper\SessionsHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

final class SessionController extends BaseController
{
    protected $default_view = 'session';

    public function cancel(): void
    {
        $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=sessions', false));
    }

    public function download(): void
    {
        /** @var CMSApplication $app */
        $app = $this->app;
        $id  = (int) $app->getInput()->getInt('id', 0);

        if ($id <= 0) {
            $app->enqueueMessage(Text::_('COM_CSINTEGRITY_SESSION_DOWNLOAD_BAD_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=sessions', false));
            return;
        }

        $session = SessionsHelper::find($id);
        if ($session === null) {
            $app->enqueueMessage(Text::_('COM_CSINTEGRITY_SESSION_DOWNLOAD_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=sessions', false));
            return;
        }

        $contents = (string) ($session->report_markdown ?? '');
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $session->name);
        $filename = 'csintegrity-' . ($safeName !== '' ? $safeName : 'session-' . $id) . '.md';

        $app->setHeader('Content-Type', 'text/markdown; charset=utf-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
        $app->setHeader('Content-Length', (string) strlen($contents), true);
        $app->sendHeaders();
        echo $contents;
        $app->close();
    }
}
