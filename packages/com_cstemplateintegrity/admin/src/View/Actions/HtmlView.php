<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Cstemplateintegrity\Administrator\View\Actions;

defined('_JEXEC') or die;

use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\ActionsHelper;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\PermissionHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    /** @var list<\stdClass> */
    public array $items = [];

    public function display($tpl = null): void
    {
        // ACL gate. Joomla's outer core.manage check lets a user with
        // admin access on another component reach this view by URL —
        // requireView() enforces the granular cstemplateintegrity.view
        // action declared in admin/access.xml.
        PermissionHelper::requireView();

        $this->items = ActionsHelper::listRecent(500);
        $this->addToolbar();
        parent::display($tpl);
    }

    private function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_CSTEMPLATEINTEGRITY_ACTIONS_TITLE'), 'list-2');
    }
}
