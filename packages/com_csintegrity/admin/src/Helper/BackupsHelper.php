<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Pre-change file-snapshot helper. Stores a copy of a file's
 * contents in #__csintegrity_backups before a change is made, so
 * that "what was here before Claude rewrote it?" is answerable.
 *
 * v0.6: store + list. Restore-from-backup is intentionally deferred
 * because restoring arbitrary template / layout / plugin files is
 * destructive enough to deserve its own design pass.
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// resolved at call site via use Joomla\Database\ParameterType when needed

final class BackupsHelper
{
    /** Cap on a single backup row's contents. 1 MB. */
    public const MAX_SIZE = 1048576;

    public static function createFromContents(
        string $filePath,
        string $contents,
        ?int $sessionId = null,
        ?int $createdBy = null
    ): int {
        $size = strlen($contents);
        if ($size > self::MAX_SIZE) {
            throw new \RuntimeException(sprintf('Backup contents exceed the %d-byte cap.', self::MAX_SIZE));
        }

        $filePath = mb_substr($filePath, 0, 500);
        $hash     = hash('sha256', $contents);

        // Dedupe: if we already have a backup of this exact path+contents,
        // return its id instead of inserting a copy. Caller's audit-log
        // entry (e.g., fix_applied) will reference the existing backup,
        // which is still semantically correct — that snapshot already
        // captured this state.
        $existingId = self::findExistingByPathAndHash($filePath, $hash);
        if ($existingId !== null) {
            return $existingId;
        }

        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $now = Factory::getDate()->toSql();

        $row = (object) [
            'session_id'   => $sessionId,
            'file_path'    => $filePath,
            'file_hash'    => $hash,
            'file_size'    => $size,
            'contents_b64' => base64_encode($contents),
            'created_by'   => $createdBy ?? self::currentUserId(),
            'created_at'   => $now,
        ];

        $db->insertObject('#__csintegrity_backups', $row, 'id');

        $insertedId = (int) $row->id;

        ActionLogHelper::log(
            ActionLogHelper::ACTION_BACKUP_CREATED,
            ['id' => $insertedId, 'file_path' => $row->file_path, 'size' => $size, 'sha256' => $row->file_hash],
            $sessionId
        );

        return $insertedId;
    }

    private static function findExistingByPathAndHash(string $filePath, string $hash): ?int
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__csintegrity_backups'))
            ->where($db->quoteName('file_path') . ' = :path')
            ->where($db->quoteName('file_hash') . ' = :hash')
            ->bind(':path', $filePath)
            ->bind(':hash', $hash)
            ->order($db->quoteName('id') . ' ASC');

        $db->setQuery($query, 0, 1);
        $existingId = $db->loadResult();

        return $existingId !== null ? (int) $existingId : null;
    }

    /**
     * Delete a single backup row.
     */
    public static function delete(int $id): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__csintegrity_backups'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $db->setQuery($query)->execute();
        return $db->getAffectedRows() > 0;
    }

    /**
     * @return list<\stdClass>
     */
    public static function listRecent(int $limit = 100): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $limit = max(1, min(500, $limit));

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'session_id', 'file_path', 'file_hash', 'file_size', 'created_by', 'created_at']))
            ->from($db->quoteName('#__csintegrity_backups'))
            ->order($db->quoteName('created_at') . ' DESC');

        $db->setQuery($query, 0, $limit);

        return $db->loadObjectList() ?: [];
    }

    public static function find(int $id): ?\stdClass
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__csintegrity_backups'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $row = $db->setQuery($query)->loadObject();
        return $row ?: null;
    }

    public static function decodeContents(\stdClass $row): string
    {
        return base64_decode((string) ($row->contents_b64 ?? ''), true) ?: '';
    }

    /**
     * Restore a stored backup to its original file path.
     *
     * Before overwriting the live file, this method takes a fresh
     * backup of its CURRENT contents — so the restore operation is
     * itself reversible. Refuses to write outside of JPATH_ROOT and
     * refuses to write a PHP-executable file outside of a template
     * override path.
     *
     * @return array{backup_id: int, restored_path: string, pre_restore_backup_id: ?int, bytes_written: int}
     */
    public static function restore(int $id): array
    {
        $row = self::find($id);
        if ($row === null) {
            throw new \RuntimeException(sprintf('Backup #%d not found.', $id));
        }

        $relativePath = ltrim((string) $row->file_path, '/\\');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            throw new \RuntimeException('Backup row has an invalid file_path.');
        }

        $absolute = JPATH_ROOT . '/' . $relativePath;

        // Separator-anchored containment check + PHP-write whitelist.
        // assertWithinRoot's strpos predecessor was bypassable when
        // JPATH_ROOT had a sibling directory whose name began with the
        // same prefix (e.g. /var/www/joomla and /var/www/joomla-bak).
        PathSafetyHelper::assertWithinRoot($absolute);
        PathSafetyHelper::assertPhpWriteAllowed($absolute);

        $restoredContents = self::decodeContents($row);
        if ($restoredContents === '') {
            throw new \RuntimeException('Backup is empty; nothing to restore.');
        }

        // Pre-restore safety backup of the current file state, so this
        // restore is itself reversible.
        $preRestoreBackupId = null;
        if (is_file($absolute)) {
            $currentContents    = (string) @file_get_contents($absolute);
            $preRestoreBackupId = self::createFromContents(
                $relativePath,
                $currentContents,
                (int) ($row->session_id ?? 0) ?: null
            );
        }

        $written = file_put_contents($absolute, $restoredContents);
        if ($written === false) {
            throw new \RuntimeException(sprintf('Could not write to %s. Check filesystem permissions.', $relativePath));
        }

        PathSafetyHelper::invalidateOpcacheIfPhp($absolute);

        ActionLogHelper::log(
            ActionLogHelper::ACTION_BACKUP_RESTORED,
            [
                'backup_id'             => $id,
                'restored_path'         => $relativePath,
                'pre_restore_backup_id' => $preRestoreBackupId,
                'bytes_written'         => $written,
            ],
            isset($row->session_id) ? (int) $row->session_id : null
        );

        return [
            'backup_id'             => $id,
            'restored_path'         => $relativePath,
            'pre_restore_backup_id' => $preRestoreBackupId,
            'bytes_written'         => (int) $written,
        ];
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
