<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Joomla Web Services routes:
 *   GET /api/index.php/v1/csintegrity/overrides
 *   GET /api/index.php/v1/csintegrity/overrides/:id/override-file
 *   GET /api/index.php/v1/csintegrity/overrides/:id/core-file
 *
 * Auth: `X-Joomla-Token: <token>` (Joomla rejects `Authorization: Bearer`).
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Api\Controller;

defined('_JEXEC') or die;

use Cybersalt\Component\Csintegrity\Administrator\Helper\PathResolver;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Throwable;

final class OverridesController extends ApiController
{
    /** Cap for file contents returned in a single response (1 MB). */
    private const MAX_FILE_SIZE = 1048576;

    protected $contentType = 'overrides';

    protected $default_view = 'overrides';

    public function overrideFile(): void
    {
        $this->respondFile('override');
    }

    public function coreFile(): void
    {
        $this->respondFile('core');
    }

    private function respondFile(string $side): void
    {
        $id = (int) $this->input->get('id', 0, 'int');

        if ($id <= 0) {
            $this->sendJsonApiError(400, 'INVALID_ID', 'A numeric override id is required.');
            return;
        }

        try {
            $row = $this->loadOverrideRow($id);
        } catch (Throwable $e) {
            $this->sendJsonApiError(500, 'DB_ERROR', $e->getMessage());
            return;
        }

        if ($row === null) {
            $this->sendJsonApiError(404, 'NOT_FOUND', 'No override row matches that id.');
            return;
        }

        $clientId = (int) $row->client_id;
        $template = (string) $row->template;
        $hashId   = (string) $row->hash_id;

        $path = $side === 'override'
            ? PathResolver::overridePath($template, $hashId, $clientId)
            : PathResolver::corePath($hashId, $clientId);

        if ($path === null) {
            $this->sendJsonApiError(
                422,
                'PATH_UNRESOLVED',
                'Could not derive a ' . $side . ' file path from this override row. The hash_id may decode to an unrecognized first segment.'
            );
            return;
        }

        if (!is_file($path)) {
            $this->sendJsonApiError(
                404,
                'FILE_MISSING',
                'The ' . $side . ' file does not exist on disk at the resolved path.'
            );
            return;
        }

        $size = (int) filesize($path);
        if ($size > self::MAX_FILE_SIZE) {
            $this->sendJsonApiError(
                413,
                'FILE_TOO_LARGE',
                'File exceeds the ' . self::MAX_FILE_SIZE . '-byte safety cap.'
            );
            return;
        }

        $contents = (string) @file_get_contents($path);
        $hash     = hash('sha256', $contents);
        $modified = gmdate('Y-m-d\TH:i:s\Z', (int) filemtime($path));

        $this->sendJsonApi([
            'data' => [
                'type'       => 'csintegrity-file-contents',
                'id'         => $id . ':' . $side,
                'attributes' => [
                    'side'     => $side,
                    'path'     => $this->relativizePath($path),
                    'absolute' => $path,
                    'hash'     => $hash,
                    'size'     => $size,
                    'modified' => $modified,
                    'encoding' => 'utf-8',
                    'contents' => $contents,
                ],
            ],
        ]);
    }

    private function loadOverrideRow(int $id): ?object
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'template', 'hash_id', 'client_id']))
            ->from($db->quoteName('#__template_overrides'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $row = $db->setQuery($query)->loadObject();
        return $row ?: null;
    }

    private function relativizePath(string $absolute): string
    {
        $base = JPATH_ROOT;
        if (str_starts_with($absolute, $base)) {
            return ltrim(substr($absolute, strlen($base)), '/\\');
        }
        return $absolute;
    }

    private function sendJsonApi(array $payload, int $status = 200): void
    {
        $app = $this->app ?? Factory::getApplication();
        $app->setHeader('status', (string) $status, true);
        $app->setHeader('Content-Type', 'application/vnd.api+json; charset=utf-8', true);
        $app->sendHeaders();
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $app->close();
    }

    private function sendJsonApiError(int $status, string $code, string $detail): void
    {
        $this->sendJsonApi(
            [
                'errors' => [
                    [
                        'status' => (string) $status,
                        'code'   => $code,
                        'title'  => $detail,
                    ],
                ],
            ],
            $status
        );
    }
}
