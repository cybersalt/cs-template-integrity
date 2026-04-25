<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ActionsHelper
{
    /**
     * @return list<\stdClass>
     */
    public static function listRecent(int $limit = 200): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $limit = max(1, min(500, $limit));

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'session_id', 'action', 'details', 'user_id', 'created_at']))
            ->from($db->quoteName('#__csintegrity_actions'))
            ->order($db->quoteName('created_at') . ' DESC');

        $db->setQuery($query, 0, $limit);

        return $db->loadObjectList() ?: [];
    }

    /**
     * @return list<\stdClass>
     */
    public static function listForSession(int $sessionId): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'session_id', 'action', 'details', 'user_id', 'created_at']))
            ->from($db->quoteName('#__csintegrity_actions'))
            ->where($db->quoteName('session_id') . ' = :sid')
            ->bind(':sid', $sessionId, ParameterType::INTEGER)
            ->order($db->quoteName('created_at') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }
}
