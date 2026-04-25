<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Cstemplateintegrity\Administrator\View\Sessionform;

defined('_JEXEC') or die;

use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\SessionsHelper;
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
        $this->backUrl     = Route::_('index.php?option=com_cstemplateintegrity&view=sessions', false);

        HTMLHelper::_('stylesheet', 'com_cstemplateintegrity/dashboard.css', ['relative' => true, 'version' => 'auto']);

        ToolbarHelper::title(Text::_('COM_CSTEMPLATEINTEGRITY_SESSIONFORM_TITLE'), 'plus');

        parent::display($tpl);
    }
}
