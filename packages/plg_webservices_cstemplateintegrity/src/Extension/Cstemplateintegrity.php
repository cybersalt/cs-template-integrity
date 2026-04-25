<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Registers com_cstemplateintegrity's Web Services routes so that Joomla's
 * API dispatcher knows which controller handles /v1/cstemplateintegrity/...
 * Without this plugin, the component's api/ folder is dead code.
 */

declare(strict_types=1);

namespace Cybersalt\Plugin\WebServices\Cstemplateintegrity\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

final class Cstemplateintegrity extends CMSPlugin implements SubscriberInterface
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
        $defaults = ['component' => 'com_cstemplateintegrity'];

        $router->createCRUDRoutes('v1/cstemplateintegrity/overrides', 'overrides', $defaults);

        $router->addRoutes([
            new Route(
                ['GET'],
                'v1/cstemplateintegrity/overrides/:id/override-file',
                'overrides.overrideFile',
                ['id' => '(\d+)'],
                $defaults
            ),
            new Route(
                ['GET'],
                'v1/cstemplateintegrity/overrides/:id/core-file',
                'overrides.coreFile',
                ['id' => '(\d+)'],
                $defaults
            ),
            new Route(
                ['POST'],
                'v1/cstemplateintegrity/overrides/:id/apply-fix',
                'overrides.applyFix',
                ['id' => '(\d+)'],
                $defaults
            ),
            new Route(
                ['POST', 'DELETE'],
                'v1/cstemplateintegrity/overrides/:id/dismiss',
                'overrides.dismiss',
                ['id' => '(\d+)'],
                $defaults
            ),
            new Route(
                ['POST'],
                'v1/cstemplateintegrity/overrides/dismiss-all',
                'overrides.dismissAll',
                [],
                $defaults
            ),
            new Route(
                ['POST'],
                'v1/cstemplateintegrity/backups/:id/restore',
                'backups.restore',
                ['id' => '(\d+)'],
                $defaults
            ),
        ]);

        $router->createCRUDRoutes('v1/cstemplateintegrity/sessions', 'sessions', $defaults);
        $router->createCRUDRoutes('v1/cstemplateintegrity/backups',  'backups',  $defaults);
    }
}
