<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Web Services routes for file backups:
 *
 *   GET  /api/index.php/v1/csintegrity/backups
 *   POST /api/index.php/v1/csintegrity/backups
 *
 * Body for POST:
 *   { "file_path": "templates/.../foo.php",
 *     "contents":  "<?php\n... raw original content ...",
 *     "session_id": 42 (optional) }
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Api\Controller;

defined('_JEXEC') or die;

use Cybersalt\Component\Csintegrity\Administrator\Helper\BackupsHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\Uri\Uri;
use Throwable;

final class BackupsController extends ApiController
{
    protected $contentType = 'backups';

    protected $default_view = 'backups';

    public function add(): void
    {
        $this->createBackup();
    }

    public function edit(): void
    {
        $this->createBackup();
    }

    public function restore($id = null): void
    {
        try {
            // Resolve :id across method-arg, input, and URL-path regex.
            // Joomla's API dispatcher populates input for GET captures
            // but not POST captures; the regex fallback covers POST.
            if ($id === null || (int) $id <= 0) {
                $id = $this->input->getInt('id', 0);
            }
            if ((int) $id <= 0) {
                $path = (string) $this->input->server->get('REQUEST_URI', '', 'string');
                if (preg_match('#/backups/(\d+)/#', $path, $m)) {
                    $id = (int) $m[1];
                }
            }
            $id = (int) $id;
            if ($id <= 0) {
                $this->sendJsonApi(
                    ['errors' => [['status' => '400', 'code' => 'INVALID_ID', 'title' => 'A numeric backup id is required.']]],
                    400
                );
                return;
            }

            $stats = BackupsHelper::restore($id);

            $this->sendJsonApi(
                [
                    'data' => [
                        'type'       => 'csintegrity-restore',
                        'id'         => (string) $id,
                        'attributes' => $stats,
                    ],
                ],
                200
            );
        } catch (Throwable $e) {
            $this->sendJsonApi(
                ['errors' => [['status' => '500', 'code' => 'RESTORE_FAILED', 'title' => $e->getMessage()]]],
                500
            );
        }
    }

    private function createBackup(): void
    {
        try {
            $body = $this->parseJsonBody();

            $filePath  = isset($body['file_path']) ? (string) $body['file_path'] : '';
            $contents  = isset($body['contents']) ? (string) $body['contents'] : '';
            $sessionId = isset($body['session_id']) ? (int) $body['session_id'] : null;

            if ($filePath === '' || $contents === '') {
                $this->sendJsonApi(
                    [
                        'errors' => [[
                            'status' => '400',
                            'code'   => 'MISSING_FIELDS',
                            'title'  => 'Both file_path and contents are required.',
                        ]],
                    ],
                    400
                );
                return;
            }

            $id = BackupsHelper::createFromContents($filePath, $contents, $sessionId);

            $this->sendJsonApi(
                [
                    'data' => [
                        'type'       => 'csintegrity-backups',
                        'id'         => (string) $id,
                        'attributes' => [
                            'id'         => $id,
                            'file_path'  => $filePath,
                            'file_size'  => strlen($contents),
                            'session_id' => $sessionId,
                        ],
                        'links' => [
                            'self' => rtrim(Uri::root(), '/') . '/api/index.php/v1/csintegrity/backups/' . $id,
                        ],
                    ],
                ],
                201
            );
        } catch (Throwable $e) {
            $this->sendJsonApi(
                [
                    'errors' => [['status' => '500', 'code' => 'CREATE_FAILED', 'title' => $e->getMessage()]],
                ],
                500
            );
        }
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
}
