<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Centralised authorisation checks for the API + admin controllers.
 *
 * Every endpoint that returns or mutates csintegrity data must call
 * either requireView() or requireWrite() before doing any work. The
 * helper resolves the current Joomla identity (token-authenticated for
 * API requests, session-authenticated for admin requests) and asserts
 * an ACL action defined in admin/access.xml.
 *
 * Super Users always pass. Anyone else must have either core.manage on
 * com_csintegrity OR the matching csintegrity.view / csintegrity.write
 * action explicitly granted to a group they belong to.
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;

final class PermissionHelper
{
    public const COMPONENT = 'com_csintegrity';

    public const ACTION_VIEW  = 'csintegrity.view';
    public const ACTION_WRITE = 'csintegrity.write';

    /**
     * Throw if the current user cannot read csintegrity data.
     *
     * @throws \RuntimeException with a 403 marker for the controller to translate.
     */
    public static function requireView(): User
    {
        return self::requireAny([self::ACTION_VIEW, 'core.manage', 'core.admin']);
    }

    /**
     * Throw if the current user cannot mutate csintegrity-managed state
     * (apply fixes, restore backups, dismiss overrides, create sessions).
     *
     * @throws \RuntimeException with a 403 marker for the controller to translate.
     */
    public static function requireWrite(): User
    {
        return self::requireAny([self::ACTION_WRITE, 'core.manage', 'core.admin']);
    }

    /**
     * @param list<string> $actions
     */
    private static function requireAny(array $actions): User
    {
        $user = self::currentUser();

        if ($user === null || $user->guest) {
            throw new \RuntimeException('AUTH_REQUIRED', 401);
        }

        foreach ($actions as $action) {
            if ($user->authorise($action, self::COMPONENT)) {
                return $user;
            }
        }

        throw new \RuntimeException('FORBIDDEN', 403);
    }

    private static function currentUser(): ?User
    {
        try {
            $app  = Factory::getApplication();
            $user = $app->getIdentity();
            if ($user instanceof User) {
                return $user;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        try {
            return Factory::getUser();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
