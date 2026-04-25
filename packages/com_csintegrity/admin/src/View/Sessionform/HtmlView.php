<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\View\Sessionform;

defined('_JEXEC') or die;

use Cybersalt\Component\Csintegrity\Administrator\Helper\SessionsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public string $defaultName = '';

    public string $backUrl = '';

    public function display($tpl = null): void
    {
        $this->defaultName = SessionsHelper::autoName();
        $this->backUrl     = Route::_('index.php?option=com_csintegrity&view=sessions', false);

        HTMLHelper::_('stylesheet', 'com_csintegrity/dashboard.css', ['relative' => true, 'version' => 'auto']);

        ToolbarHelper::title(Text::_('COM_CSINTEGRITY_SESSIONFORM_TITLE'), 'plus');

        parent::display($tpl);
    }
}
