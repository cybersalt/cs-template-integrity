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

        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $now = Factory::getDate()->toSql();

        $row = (object) [
            'session_id'   => $sessionId,
            'file_path'    => mb_substr($filePath, 0, 500),
            'file_hash'    => hash('sha256', $contents),
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
     * itself reversible. Refuses to write outside of JPATH_ROOT.
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
        if ($relativePath === '') {
            throw new \RuntimeException('Backup row has no file_path.');
        }

        $absolute = JPATH_ROOT . '/' . $relativePath;

        // Path-traversal guard. Resolve dirname through realpath() (which
        // follows symlinks and collapses .. segments) and verify it sits
        // under JPATH_ROOT.
        $parentReal = realpath(\dirname($absolute));
        $rootReal   = realpath(JPATH_ROOT);
        if ($parentReal === false || $rootReal === false || strpos($parentReal, $rootReal) !== 0) {
            throw new \RuntimeException('Refusing to restore: target path is outside the Joomla site root.');
        }

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
