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

use Cybersalt\Component\Csintegrity\Administrator\Helper\MarkReviewedHelper;
use Cybersalt\Component\Csintegrity\Administrator\Helper\OverridesHelper;
use Cybersalt\Component\Csintegrity\Administrator\Helper\PathResolver;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
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

    public function displayList()
    {
        $apiFilterInfo = $this->input->get('filter', [], 'array');
        $filter        = InputFilter::getInstance();

        if (\array_key_exists('template', $apiFilterInfo)) {
            $this->modelState->set('filter.template', $filter->clean($apiFilterInfo['template'], 'STRING'));
        }

        if (\array_key_exists('client_id', $apiFilterInfo)) {
            $this->modelState->set('filter.client_id', $filter->clean($apiFilterInfo['client_id'], 'INT'));
        }

        if (\array_key_exists('state', $apiFilterInfo)) {
            $this->modelState->set('filter.state', $filter->clean($apiFilterInfo['state'], 'INT'));
        }

        if (\array_key_exists('extension_id', $apiFilterInfo)) {
            $this->modelState->set('filter.extension_id', $filter->clean($apiFilterInfo['extension_id'], 'INT'));
        }

        return parent::displayList();
    }

    public function overrideFile(): void
    {
        $this->respondFile('override');
    }

    public function coreFile(): void
    {
        $this->respondFile('core');
    }

    public function applyFix($id = null): void
    {
        try {
            $id = (int) ($id ?? $this->input->getInt('id', 0));
            if ($id <= 0) {
                $this->sendJsonApiError(400, 'INVALID_ID', 'A numeric override id is required.');
                return;
            }

            $body      = $this->parseJsonBody();
            $contents  = isset($body['contents']) ? (string) $body['contents'] : '';
            $sessionId = isset($body['session_id']) ? (int) $body['session_id'] : null;

            if ($contents === '') {
                $this->sendJsonApiError(400, 'MISSING_CONTENTS', 'Body must include a non-empty contents field.');
                return;
            }

            $result = OverridesHelper::applyFix($id, $contents, $sessionId);

            $this->sendJsonApi(
                [
                    'data' => [
                        'type'       => 'csintegrity-fix',
                        'id'         => (string) $id,
                        'attributes' => $result,
                    ],
                ],
                201
            );
        } catch (Throwable $e) {
            $this->sendJsonApiError(500, 'APPLY_FIX_FAILED', $e->getMessage());
        }
    }

    public function dismissAll(): void
    {
        try {
            $cleared = MarkReviewedHelper::clearAllOverrides();

            $this->sendJsonApi([
                'data' => [
                    'type'       => 'csintegrity-dismiss-all',
                    'id'         => 'all',
                    'attributes' => ['cleared' => $cleared],
                ],
            ], 200);
        } catch (Throwable $e) {
            $this->sendJsonApiError(500, 'DISMISS_ALL_FAILED', $e->getMessage());
        }
    }

    public function dismiss($id = null): void
    {
        try {
            $id = (int) ($id ?? $this->input->getInt('id', 0));
            if ($id <= 0) {
                $this->sendJsonApiError(400, 'INVALID_ID', 'A numeric override id is required.');
                return;
            }

            $deleted = OverridesHelper::dismissOne($id);

            $this->sendJsonApi([
                'data' => [
                    'type'       => 'csintegrity-dismiss',
                    'id'         => (string) $id,
                    'attributes' => ['dismissed' => $deleted],
                ],
            ], $deleted ? 200 : 404);
        } catch (Throwable $e) {
            $this->sendJsonApiError(500, 'DISMISS_FAILED', $e->getMessage());
        }
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

    /**
     * @return array<string,mixed>
     */
    private function parseJsonBody(): array
    {
        $raw = (string) file_get_contents('php://input');
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
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

    private function relativizePath(string $absolute): string
    {
        $base = JPATH_ROOT;
        if (str_starts_with($absolute, $base)) {
            return ltrim(substr($absolute, strlen($base)), '/\\');
        }
        return $absolute;
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
