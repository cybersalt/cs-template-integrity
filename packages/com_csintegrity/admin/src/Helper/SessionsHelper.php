<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Read/write helper for #__csintegrity_sessions. Used by both the
 * admin controllers and the Web Services API controller — keeps the
 * insert/list logic in one place so paste-in and POST-from-Claude
 * produce identical rows.
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class SessionsHelper
{
    public const SOURCE_PASTE       = 'paste';
    public const SOURCE_API         = 'api';
    public const SOURCE_CLAUDE_CODE = 'claude_code';

    /**
     * @return int  Newly inserted session id
     */
    public static function create(
        ?string $name,
        ?string $summary,
        ?string $reportMarkdown,
        string $source = self::SOURCE_PASTE,
        ?int $createdBy = null
    ): int {
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $now = Factory::getDate()->toSql();

        $row = (object) [
            'name'            => $name !== null && $name !== '' ? mb_substr($name, 0, 64) : self::autoName(),
            'summary'         => $summary !== null ? mb_substr($summary, 0, 500) : '',
            'source'          => in_array($source, [self::SOURCE_PASTE, self::SOURCE_API, self::SOURCE_CLAUDE_CODE], true)
                ? $source
                : self::SOURCE_PASTE,
            'report_markdown' => $reportMarkdown,
            'state'           => 1,
            'created_by'      => $createdBy ?? self::currentUserId(),
            'created_at'      => $now,
            'modified_at'     => $now,
        ];

        $db->insertObject('#__csintegrity_sessions', $row, 'id');

        $insertedId = (int) $row->id;

        ActionLogHelper::log(
            ActionLogHelper::ACTION_SESSION_CREATED,
            ['source' => $row->source, 'name' => $row->name],
            $insertedId
        );

        return $insertedId;
    }

    /**
     * @return list<\stdClass>
     */
    public static function listRecent(int $limit = 50): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $limit = max(1, min(200, $limit));

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name', 'summary', 'source', 'state', 'created_by', 'created_at']))
            ->from($db->quoteName('#__csintegrity_sessions'))
            ->order($db->quoteName('created_at') . ' DESC');

        $db->setQuery($query, 0, $limit);

        return $db->loadObjectList() ?: [];
    }

    public static function find(int $id): ?\stdClass
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__csintegrity_sessions'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $row = $db->setQuery($query)->loadObject();
        return $row ?: null;
    }

    /**
     * @param  list<int>  $ids
     */
    public static function delete(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($i) => $i > 0)));
        if (empty($ids)) {
            return 0;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__csintegrity_sessions'))
            ->whereIn($db->quoteName('id'), $ids);

        $db->setQuery($query)->execute();

        foreach ($ids as $id) {
            ActionLogHelper::log(ActionLogHelper::ACTION_SESSION_DELETED, ['id' => $id]);
        }

        return count($ids);
    }

    public static function autoName(?\DateTimeInterface $when = null): string
    {
        $dt = $when ?? new \DateTimeImmutable('now');
        return $dt->format('Y-m-d-Hi');
    }

    private static function currentUserId(): int
    {
        try {
            $app  = Factory::getApplication();
            $user = $app->getIdentity();
            return $user ? (int) $user->id : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
