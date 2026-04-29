<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Cstemplateintegrity\Administrator\Controller;

defined('_JEXEC') or die;

use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\AnthropicClient;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\DisclaimerHelper;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\MarkReviewedHelper;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\PermissionHelper;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\RescanHelper;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\ScanRunnerHelper;
use Cybersalt\Component\Cstemplateintegrity\Administrator\Helper\SessionsHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Throwable;

final class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    public function rescan(): void
    {
        $this->checkToken();
        PermissionHelper::requireWrite();

        /** @var CMSApplication $app */
        $app = $this->app;

        try {
            $stats = RescanHelper::rebuildOverrideTracker();
            $app->enqueueMessage(
                Text::sprintf(
                    'COM_CSTEMPLATEINTEGRITY_RESCAN_SUCCESS',
                    $stats['inserted'],
                    $stats['scanned'],
                    $stats['templates']
                ),
                'success'
            );
        } catch (Throwable $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_CSTEMPLATEINTEGRITY_RESCAN_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_cstemplateintegrity&view=dashboard', false));
    }

    public function markReviewed(): void
    {
        $this->checkToken();
        PermissionHelper::requireWrite();

        /** @var CMSApplication $app */
        $app = $this->app;

        try {
            $cleared = MarkReviewedHelper::clearAllOverrides();

            if ($cleared === 0) {
                $app->enqueueMessage(Text::_('COM_CSTEMPLATEINTEGRITY_MARK_REVIEWED_NONE'), 'info');
            } else {
                $app->enqueueMessage(
                    Text::sprintf('COM_CSTEMPLATEINTEGRITY_MARK_REVIEWED_SUCCESS', $cleared),
                    'success'
                );
            }
        } catch (Throwable $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_CSTEMPLATEINTEGRITY_MARK_REVIEWED_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_cstemplateintegrity&view=dashboard', false));
    }

    /**
     * Run an automated scan against the saved Anthropic API key.
     * Walks the override tracker, builds one consolidated prompt,
     * sends it to Claude, saves the resulting markdown report as a
     * new session, then redirects the user to it.
     *
     * Synchronous and blocking: an Anthropic call with 50+ overrides
     * inline can run 30-90 seconds. We bump set_time_limit so PHP
     * doesn't kill the request mid-call. A background-job version is
     * a future iteration.
     */
    public function runScan(): void
    {
        $this->checkToken();
        PermissionHelper::requireWrite();

        /** @var CMSApplication $app */
        $app   = $this->app;
        $back  = Route::_('index.php?option=com_cstemplateintegrity&view=dashboard', false);

        $params = ComponentHelper::getParams('com_cstemplateintegrity');
        // Pass the raw stored value through to AnthropicClient — the
        // client tracks the pre-strip length for diagnostics so we can
        // tell "saved with embedded whitespace" apart from "saved
        // truncated" when the key fails.
        $apiKey = (string) $params->get('anthropic_api_key', '');

        if (trim($apiKey) === '') {
            $app->enqueueMessage(Text::_('COM_CSTEMPLATEINTEGRITY_RUN_SCAN_NO_KEY'), 'warning');
            $this->setRedirect($back);
            return;
        }

        // Anthropic Messages calls take real time on a meaningful
        // override list. Without this PHP would 504 mid-call.
        @set_time_limit(180);

        try {
            $result   = ScanRunnerHelper::run($apiKey);
            $markdown = $result['markdown'];

            $summary = sprintf(
                'Automated scan: %d override(s) reviewed%s.',
                $result['count'],
                $result['truncated'] ? sprintf(' (first %d only)', ScanRunnerHelper::MAX_OVERRIDES_PER_RUN) : ''
            );

            $name = gmdate('Y-m-d-His');
            $id   = SessionsHelper::create(
                $name,
                $summary,
                $markdown,
                SessionsHelper::SOURCE_AUTO,
                null,
                $result['messages']
            );

            $msg = Text::sprintf(
                'COM_CSTEMPLATEINTEGRITY_RUN_SCAN_SUCCESS',
                $result['count'],
                $id
            );
            $app->enqueueMessage($msg, 'success');

            if ($result['truncated']) {
                $app->enqueueMessage(
                    Text::sprintf(
                        'COM_CSTEMPLATEINTEGRITY_RUN_SCAN_TRUNCATED',
                        ScanRunnerHelper::MAX_OVERRIDES_PER_RUN
                    ),
                    'warning'
                );
            }

            $this->setRedirect(Route::_('index.php?option=com_cstemplateintegrity&view=session&id=' . (int) $id . '&from=dashboard', false));
        } catch (Throwable $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_CSTEMPLATEINTEGRITY_RUN_SCAN_ERROR', $e->getMessage()),
                'error'
            );
            $this->setRedirect($back);
        }
    }

    /**
     * Make a tiny Anthropic call to verify the saved key is valid.
     * Posted via fetch() from the diagnostics modal; returns a JSON
     * payload the modal renders inline. No state change.
     */
    public function testApiConnection(): void
    {
        // GET-form CSRF: the button submits via fetch with the token
        // on the URL query string, not in a form body. Same pattern as
        // acknowledgeDisclaimer.
        $this->checkToken('get');
        PermissionHelper::requireWrite();

        /** @var CMSApplication $app */
        $app    = $this->app;
        $params = ComponentHelper::getParams('com_cstemplateintegrity');
        $apiKey = (string) $params->get('anthropic_api_key', '');

        $reply = function (array $payload, int $status = 200) use ($app): void {
            $app->setHeader('status', (string) $status, true);
            $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            $app->sendHeaders();
            echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $app->close();
        };

        if (trim($apiKey) === '') {
            $reply(['ok' => false, 'error' => 'No API key saved. Add one in Options first.'], 200);
            return;
        }

        try {
            $client = new AnthropicClient($apiKey);
            $start  = microtime(true);

            // Smallest possible test prompt — explicit max_tokens cap
            // so the test costs ~no tokens regardless of model.
            $reply_text = $client->complete(
                'Reply only with the literal word PONG.',
                [['role' => 'user', 'content' => 'ping']],
                16,
                30
            );

            $latency = (int) round((microtime(true) - $start) * 1000);

            $reply([
                'ok'           => true,
                'status'       => 200,
                'latency_ms'   => $latency,
                'sample_reply' => mb_substr(trim($reply_text), 0, 80),
                'fingerprint'  => $client->keyFingerprint(),
            ]);
        } catch (Throwable $e) {
            $reply([
                'ok'    => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Persist a "don't show again" click on the first-run disclaimer
     * for the current logged-in user. Posted via fetch() from the
     * modal's inline JS — no redirect, returns a tiny JSON payload.
     */
    public function acknowledgeDisclaimer(): void
    {
        // The disclaimer modal's inline JS sends the session token only
        // on the URL query string, not in a form body, so the default
        // checkToken() (which looks at $_POST) silently fails and the
        // ACK row never lands. Use the GET-form check instead — same
        // pattern the download endpoints use.
        $this->checkToken('get');
        // No PermissionHelper gate here on purpose — every authenticated
        // admin who can SEE the modal must be able to dismiss it; we
        // gate the modal's APPEARANCE on hasAcknowledged(), not on
        // arbitrary permissions.

        /** @var CMSApplication $app */
        $app  = $this->app;
        $user = $app->getIdentity();
        $uid  = $user ? (int) $user->id : 0;

        if ($uid > 0) {
            DisclaimerHelper::acknowledge($uid);
        }

        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();
        echo json_encode(['acknowledged' => $uid > 0]);
        $app->close();
    }
}
