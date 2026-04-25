<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

final class SessionController extends BaseController
{
    protected $default_view = 'session';

    public function cancel(): void
    {
        $this->setRedirect(Route::_('index.php?option=com_csintegrity&view=sessions', false));
    }
}
