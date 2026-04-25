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
use Throwable;

final class BackupsController extends BaseController
{
    protected $default_view = 'backups';

    public function restore(): void
    {
        $this->checkToken();

        /** @var CMSApplication $app */
        $app = $this->app;
        $id  = (int) $app->getInput()->getInt('id', 0);

        if ($id <= 0) {
            $app->enqueueMessage(Text::_('COM_CSINTEGRITY_BACKUPS_RESTORE_BAD_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=backups', false));
            return;
        }

        try {
            $stats = BackupsHelper::restore($id);

            $msg = Text::sprintf(
                'COM_CSINTEGRITY_BACKUPS_RESTORE_SUCCESS',
                $stats['restored_path'],
                $stats['bytes_written'],
                $stats['pre_restore_backup_id'] ?? 0
            );
            $app->enqueueMessage($msg, 'success');
        } catch (Throwable $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_CSINTEGRITY_BACKUPS_RESTORE_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=backup&id=' . $id, false));
    }

    public function delete(): void
    {
        $this->checkToken();

        /** @var CMSApplication $app */
        $app = $this->app;
        $ids = (array) $app->getInput()->get('cid', [], 'array');
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($i) => $i > 0)));

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('COM_CSINTEGRITY_BACKUPS_DELETE_NONE'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=backups', false));
            return;
        }

        $deleted = 0;
        foreach ($ids as $id) {
            if (BackupsHelper::delete($id)) {
                $deleted++;
            }
        }

        $app->enqueueMessage(Text::sprintf('COM_CSINTEGRITY_BACKUPS_DELETED', $deleted), 'success');
        $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=backups', false));
    }

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
