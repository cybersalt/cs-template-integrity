<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Joomla Web Services route:
 *   GET /api/index.php/v1/csintegrity/overrides
 *
 * Auth: `X-Joomla-Token: <token>` (Joomla rejects `Authorization: Bearer`).
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\ApiController;

final class OverridesController extends ApiController
{
    protected $contentType = 'overrides';

    protected $default_view = 'overrides';
}
