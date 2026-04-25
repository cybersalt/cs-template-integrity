<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Cstemplateintegrity\Administrator\View\Sessions;

defined('_JEXEC') or die;

use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\SessionsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    /** @var list<\stdClass> */
    public array $items = [];

    public function display($tpl = null): void
    {
        $this->items = SessionsHelper::listRecent(200);
        $this->addToolbar();
        parent::display($tpl);
    }

    private function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_CSTEMPLATEINTEGRITY_SESSIONS_TITLE'), 'list');
        ToolbarHelper::addNew('sessionform.add', Text::_('COM_CSTEMPLATEINTEGRITY_SESSIONS_NEW'));
        ToolbarHelper::deleteList('', 'sessions.delete', 'JTOOLBAR_DELETE');
    }
}
