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

        $this->actions = ActionsHelper::listForSession($id);
        $this->backUrl = Route::_('index.php?option=com_csintegrity&view=sessions', false);

        HTMLHelper::_('stylesheet', 'com_csintegrity/dashboard.css', ['relative' => true, 'version' => 'auto']);

        ToolbarHelper::title(
            Text::sprintf('COM_CSINTEGRITY_SESSION_TITLE', $this->escape($this->session->name)),
            'eye'
        );

        parent::display($tpl);
    }
}
