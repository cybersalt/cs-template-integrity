<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\View\Backups;

defined('_JEXEC') or die;

use Cybersalt\Component\Csintegrity\Administrator\Helper\BackupsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    /** @var list<\stdClass> */
    public array $items = [];

    public function display($tpl = null): void
    {
        $this->items = BackupsHelper::listRecent(200);
        $this->addToolbar();
        parent::display($tpl);
    }

    private function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_CSINTEGRITY_BACKUPS_TITLE'), 'archive');
        ToolbarHelper::deleteList('', 'backups.delete', 'JTOOLBAR_DELETE');
    }
}
