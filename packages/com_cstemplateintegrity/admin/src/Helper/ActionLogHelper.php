<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Lightweight action log. Records administrative events to
 * #__cstemplateintegrity_actions so a future investigator can reconstruct
 * what happened on a site after a Claude review session.
 *
 * Action ids are strings; the recommended set is documented in the
 * class constants. Callers may use other values; the column accepts
 * any varchar(64).
 */

declare(strict_types=1);

namespace Cybersalt\Component\Cstemplateintegrity\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Throwable;

final class ActionLogHelper
{
    public const ACTION_INSTALL          = 'install';
    public const ACTION_UPDATE           = 'update';
    public const ACTION_UNINSTALL        = 'uninstall';
    public const ACTION_RESCAN           = 'rescan';
    public const ACTION_MARK_REVIEWED    = 'mark_reviewed';
    public const ACTION_SESSION_CREATED  = 'session_created';
    public const ACTION_SESSION_DELETED  = 'session_deleted';
    public const ACTION_BACKUP_CREATED   = 'backup_created';
    public const ACTION_BACKUP_RESTORED  = 'backup_restored';
    public const ACTION_FIX_APPLIED      = 'fix_applied';
    public const ACTION_OVERRIDE_DISMISSED = 'override_dismissed';
    public const ACTION_DISCLAIMER_ACKNOWLEDGED = 'disclaimer_acknowledged';

    /**
     * @param  array<string,mixed>  $details  arbitrary metadata; JSON-encoded into the row
     */
    public static function log(string $action, array $details = [], ?int $sessionId = null): void
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $row = (object) [
                'session_id' => $sessionId,
                'action'     => $action,
                'details'    => empty($details) ? null : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'user_id'    => self::currentUserId(),
                'created_at' => Factory::getDate()->toSql(),
            ];

            $db->insertObject('#__cstemplateintegrity_actions', $row);
        } catch (Throwable $e) {
            // Logging must never crash the parent operation. Swallow.
        }
    }

    private static function currentUserId(): int
    {
        try {
            $app  = Factory::getApplication();
            $user = $app->getIdentity();
            return $user ? (int) $user->id : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }
}
