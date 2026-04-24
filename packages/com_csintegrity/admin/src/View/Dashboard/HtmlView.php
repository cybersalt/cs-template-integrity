<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

final class HtmlView extends BaseHtmlView
{
    public string $siteUrl = '';

    public string $overridesEndpoint = '';

    public function display($tpl = null): void
    {
        $errors = $this->get('Errors');

        if (!empty($errors)) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->siteUrl           = rtrim(Uri::root(), '/');
        $this->overridesEndpoint = $this->siteUrl . '/api/index.php/v1/csintegrity/overrides';

        $this->addToolbar();

        parent::display($tpl);
    }

    private function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_CSINTEGRITY_DASHBOARD_TITLE'), 'check-circle');
    }
}
