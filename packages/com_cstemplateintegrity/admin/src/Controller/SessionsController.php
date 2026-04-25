<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Cstemplateintegrity\Administrator\Controller;

defined('_JEXEC') or die;

use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\PermissionHelper;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\SessionsHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Throwable;

final class SessionsController extends BaseController
{
    protected $default_view = 'sessions';

    public function save(): void
    {
        $this->checkToken();
        PermissionHelper::requireWrite();

        /** @var CMSApplication $app */
        $app  = $this->app;
        $data = (array) $app->getInput()->post->get('jform', [], 'array');

        try {
            $id = SessionsHelper::create(
                $data['name'] ?? null,
                $data['summary'] ?? null,
                $data['report_markdown'] ?? null,
                SessionsHelper::SOURCE_PASTE
            );

            $app->enqueueMessage(Text::sprintf('COM_CSTEMPLATEINTEGRITY_SESSIONS_SAVED', $id), 'success');
            $this->setRedirect(Route::_('index.php?option=com_cstemplateintegrity&view=session&id=' . $id, false));
        } catch (Throwable $e) {
            $app->enqueueMessage(Text::sprintf('COM_CSTEMPLATEINTEGRITY_SESSIONS_SAVE_ERROR', $e->getMessage()), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cstemplateintegrity&view=sessionform', false));
        }
    }

    public function delete(): void
    {
        $this->checkToken();
        PermissionHelper::requireWrite();

        /** @var CMSApplication $app */
        $app = $this->app;
        $ids = (array) $app->getInput()->get('cid', [], 'array');

        try {
            $count = SessionsHelper::delete(array_map('intval', $ids));
            $app->enqueueMessage(Text::sprintf('COM_CSTEMPLATEINTEGRITY_SESSIONS_DELETED', $count), 'success');
        } catch (Throwable $e) {
            $app->enqueueMessage(Text::sprintf('COM_CSTEMPLATEINTEGRITY_SESSIONS_DELETE_ERROR', $e->getMessage()), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_cstemplateintegrity&view=sessions', false));
    }
}
