<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\Controller;

defined('_JEXEC') or die;

use Cybersalt\Component\Csintegrity\Administrator\Helper\BackupsHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

final class BackupsController extends BaseController
{
    protected $default_view = 'backups';

    public function download(): void
    {
        /** @var CMSApplication $app */
        $app = $this->app;
        $id  = (int) $app->getInput()->getInt('id', 0);

        if ($id <= 0) {
            $app->enqueueMessage(Text::_('COM_CSINTEGRITY_BACKUPS_DOWNLOAD_BAD_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=backups', false));
            return;
        }

        $row = BackupsHelper::find($id);
        if ($row === null) {
            $app->enqueueMessage(Text::_('COM_CSINTEGRITY_BACKUPS_DOWNLOAD_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=backups', false));
            return;
        }

        $contents = BackupsHelper::decodeContents($row);
        $basename = basename($row->file_path) ?: ('backup-' . $id . '.txt');

        $app->setHeader('Content-Type', 'application/octet-stream', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . str_replace('"', '', $basename) . '"', true);
        $app->setHeader('Content-Length', (string) strlen($contents), true);
        $app->sendHeaders();
        echo $contents;
        $app->close();
    }
}
