<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Registers com_csintegrity's Web Services routes so that Joomla's
 * API dispatcher knows which controller handles /v1/csintegrity/...
 * Without this plugin, the component's api/ folder is dead code.
 */

declare(strict_types=1);

namespace Cybersalt\Plugin\WebServices\Csintegrity\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

final class Csintegrity extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeApiRoute' => 'onBeforeApiRoute',
        ];
    }

    public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
    {
        $router   = $event->getRouter();
        $defaults = ['component' => 'com_csintegrity'];

        $router->createCRUDRoutes('v1/csintegrity/overrides', 'overrides', $defaults);

        $router->addRoutes([
            new Route(
                ['GET'],
                'v1/csintegrity/overrides/:id/override-file',
                'overrides.overrideFile',
                ['id' => '(\d+)'],
                $defaults
            ),
            new Route(
                ['GET'],
                'v1/csintegrity/overrides/:id/core-file',
                'overrides.coreFile',
                ['id' => '(\d+)'],
                $defaults
            ),
            new Route(
                ['POST'],
                'v1/csintegrity/overrides/:id/apply-fix',
                'overrides.applyFix',
                ['id' => '(\d+)'],
                $defaults
            ),
            new Route(
                ['POST', 'DELETE'],
                'v1/csintegrity/overrides/:id/dismiss',
                'overrides.dismiss',
                ['id' => '(\d+)'],
                $defaults
            ),
            new Route(
                ['POST'],
                'v1/csintegrity/overrides/dismiss-all',
                'overrides.dismissAll',
                [],
                $defaults
            ),
            new Route(
                ['POST'],
                'v1/csintegrity/backups/:id/restore',
                'backups.restore',
                ['id' => '(\d+)'],
                $defaults
            ),
        ]);

        $router->createCRUDRoutes('v1/csintegrity/sessions', 'sessions', $defaults);
        $router->createCRUDRoutes('v1/csintegrity/backups',  'backups',  $defaults);
    }
}
