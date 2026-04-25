<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\View\Session;

defined('_JEXEC') or die;

use Cybersalt\Component\Csintegrity\Administrator\Helper\ActionsHelper;
use Cybersalt\Component\Csintegrity\Administrator\Helper\SessionsHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public ?\stdClass $session = null;

    /** @var list<\stdClass> */
    public array $actions = [];

    public string $backUrl = '';

    public string $backLabelKey = 'COM_CSINTEGRITY_SESSION_BACK_TO_LIST';

    public string $downloadUrl = '';

    public function display($tpl = null): void
    {
        $id = (int) Factory::getApplication()->getInput()->getInt('id', 0);
        if ($id <= 0) {
            throw new GenericDataException(Text::_('COM_CSINTEGRITY_SESSION_NOT_FOUND'), 404);
        }

        $this->session = SessionsHelper::find($id);
        if ($this->session === null) {
            throw new GenericDataException(Text::_('COM_CSINTEGRITY_SESSION_NOT_FOUND'), 404);
        }

        $this->actions     = ActionsHelper::listForSession($id);
        $this->downloadUrl = Route::_('index.php?option=com_csintegrity&task=session.download&id=' . $id, false);

        // Back-button destination depends on where the user came from.
        // Pages that link to a session pass &from=<view> in the URL;
        // any unknown / missing value falls through to the sessions list.
        $from = (string) Factory::getApplication()->getInput()->getCmd('from', '');

        switch ($from) {
            case 'actions':
                $this->backUrl      = Route::_('index.php?option=com_csintegrity&view=actions', false);
                $this->backLabelKey = 'COM_CSINTEGRITY_SESSION_BACK_TO_ACTIONS';
                break;

            case 'backups':
                $this->backUrl      = Route::_('index.php?option=com_csintegrity&view=backups', false);
                $this->backLabelKey = 'COM_CSINTEGRITY_SESSION_BACK_TO_BACKUPS';
                break;

            case 'dashboard':
                $this->backUrl      = Route::_('index.php?option=com_csintegrity&view=dashboard', false);
                $this->backLabelKey = 'COM_CSINTEGRITY_SESSION_BACK_TO_DASHBOARD';
                break;

            default:
                $this->backUrl      = Route::_('index.php?option=com_csintegrity&view=sessions', false);
                $this->backLabelKey = 'COM_CSINTEGRITY_SESSION_BACK_TO_LIST';
                break;
        }

        HTMLHelper::_('stylesheet', 'com_csintegrity/dashboard.css', ['relative' => true, 'version' => 'auto']);
        HTMLHelper::_('script', 'com_csintegrity/dashboard.js', ['relative' => true, 'version' => 'auto', 'defer' => true]);

        ToolbarHelper::title(
            Text::sprintf('COM_CSINTEGRITY_SESSION_TITLE', $this->escape($this->session->name)),
            'eye'
        );

        parent::display($tpl);
    }
}
