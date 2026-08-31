<?php

/**
 * @package     Csoverridechecker
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Tiny HTTP wrapper around OpenAI's Chat Completions API.
 *
 * Sibling of AnthropicClient — same job, same narrow surface: one
 * non-streaming request in, the assistant's reply out as a markdown
 * string. No tool-use loop, no streaming, no caching.
 *
 * Two shape differences from Anthropic worth remembering:
 *
 *  1. Auth is `Authorization: Bearer <key>` here (Anthropic uses the
 *     bare `x-api-key` header). Sending the wrong one silently 401s.
 *  2. There is no top-level `system` field. The system prompt is
 *     prepended to `messages` as a `role: system` entry.
 *
 * Also note the token cap parameter: current OpenAI models reject the
 * legacy `max_tokens` field outright, so we send `max_completion_tokens`.
 *
 * Reference: https://platform.openai.com/docs/api-reference/chat
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csoverridechecker\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;

final class OpenAiClient implements AiClientInterface
{
    public const DEFAULT_MODEL = 'gpt-5';
    public const ENDPOINT      = 'https://api.openai.com/v1/chat/completions';

    /** @var non-empty-string */
    private string $apiKey;

    private string $model;

    private int $keyRawLength = 0;

    public function __construct(string $apiKey, string $model = self::DEFAULT_MODEL)
    {
        // Track the raw length (after the caller's trim, before our
        // own whitespace strip) so the fingerprint can show both —
        // tells us whether truncation happened at save time vs in
        // copy-paste with embedded whitespace.
        $this->keyRawLength = strlen($apiKey);

        // Strip ALL whitespace, not just leading/trailing — copy-paste
        // from a website console occasionally drops a soft hyphen or
        // a stray newline mid-key, and a header value with an embedded
        // newline is worse than useless (header-injection shaped).
        $apiKey = (string) preg_replace('/\s+/', '', $apiKey);
        if ($apiKey === '') {
            throw new \InvalidArgumentException('OpenAI API key is empty.');
        }
        $this->apiKey = $apiKey;
        $this->model  = $model;
    }

    public function providerLabel(): string
    {
        return 'OpenAI';
    }

    /**
     * Non-secret fingerprint of the key for diagnostics. Returns the
     * length plus a 12-char SHA-256 hash prefix — enough to verify
     * "yes I'm looking at the same key as before" without leaking any
     * actual chars of the key.
     *
     * Same scheme as AnthropicClient: never a prefix, never a suffix,
     * because those narrow the brute-force search space if combined
     * with any other channel that leaks middle bytes. SHA-256 reveals
     * nothing about the input, so the fingerprint is safe to display
     * to any tier that can reach the diagnostics modal.
     *
     * OpenAI key lengths vary far more than Anthropic's — legacy
     * `sk-` keys run about 51 chars, project `sk-proj-` keys run well
     * past 150 — so the only length claim worth making is that
     * anything under ~40 chars is almost certainly truncated.
     */
    public function keyFingerprint(): string
    {
        $len = strlen($this->apiKey);
        if ($len <= 12) {
            return sprintf('len=%d (too short to fingerprint)', $len);
        }

        $rawSuffix = $this->keyRawLength !== $len
            ? sprintf(' (raw=%d before whitespace strip)', $this->keyRawLength)
            : '';

        $expectationSuffix = '';
        if ($len < 40) {
            $expectationSuffix = ' — OpenAI keys are at least ~51 chars; this one looks truncated';
        }

        return sprintf(
            'len=%d%s, fingerprint=sha256:%s%s',
            $len,
            $rawSuffix,
            substr(hash('sha256', $this->apiKey), 0, 12),
            $expectationSuffix
        );
    }

    /**
     * Send a single Chat Completions request and return the text from
     * the assistant's reply.
     *
     * @param  string                                                  $system       System prompt.
     * @param  list<array{role: 'user'|'assistant', content: string}>  $messages
     * @param  int                                                     $maxTokens    Hard cap on response length.
     * @param  int                                                     $timeoutSecs  HTTP timeout.
     */
    public function complete(string $system, array $messages, int $maxTokens = 8192, int $timeoutSecs = 120): string
    {
        // OpenAI has no top-level system field — the system prompt is
        // just the first message in the array.
        $wireMessages = [['role' => 'system', 'content' => $system]];
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }
            $wireMessages[] = [
                'role'    => (string) ($message['role'] ?? 'user'),
                'content' => (string) ($message['content'] ?? ''),
            ];
        }

        $payload = [
            'model'                 => $this->model,
            'max_completion_tokens' => $maxTokens,
            'messages'              => $wireMessages,
        ];

        $http = HttpFactory::getHttp();
        $body = $headers = $status = null;

        // Retry-once-with-backoff on 429 (rate limit). OpenAI caps both
        // requests and tokens per minute; if a recent call ate the
        // budget, this single sleep+retry usually bridges back into the
        // next minute window. Cap at 60s to keep the page responsive.
        for ($attempt = 0; $attempt <= 1; $attempt++) {
            $response = $http->post(
                self::ENDPOINT,
                json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'content-type'  => 'application/json',
                ],
                $timeoutSecs
            );

            $status  = (int) $response->code;
            $body    = (string) $response->body;
            $headers = is_array($response->headers ?? null) ? $response->headers : [];

            if ($status !== 429 || $attempt > 0) {
                break;
            }

            // OpenAI returns retry-after in seconds. Cap at 60s so we
            // don't sit on a single page for minutes.
            $retryAfter = isset($headers['retry-after'])
                ? (int) (is_array($headers['retry-after']) ? $headers['retry-after'][0] : $headers['retry-after'])
                : 30;
            $retryAfter = max(5, min(60, $retryAfter));
            @set_time_limit($timeoutSecs + $retryAfter + 30);
            sleep($retryAfter);
        }

        if ($status < 200 || $status >= 300) {
            // Try to surface OpenAI's structured error message; fall back to the raw body.
            // Note the raw body is echoed, never the key — the key only
            // ever exists in the request header we just sent.
            $detail  = $body;
            $decoded = json_decode($body, true);
            if (is_array($decoded) && isset($decoded['error']['message'])) {
                $detail = $decoded['error']['message'];
            }
            // For auth-shaped failures, append a non-secret key
            // fingerprint so the user can verify the saved key matches
            // what's in their OpenAI dashboard without us echoing the
            // secret. Common causes: copy-paste truncation, key for a
            // different org/project, key revoked, no billing on file.
            $hint = '';
            if ($status === 401 || $status === 403) {
                $hint = sprintf(
                    ' — Diagnostics: %s. Compare against your OpenAI dashboard; if they do not match, re-paste the key in Options.',
                    $this->keyFingerprint()
                );
            } elseif ($status === 429) {
                // Auto-retry already happened once; if we are still
                // here the per-minute budget is fully consumed (or the
                // account is out of credit — OpenAI uses 429 for both).
                $hint = ' — The extension waited and retried once already. Wait 1–2 minutes before trying again, and check that your account has credit. If this happens often, lower Overrides per scan in Options, or raise your limits at https://platform.openai.com/settings/organization/limits';
            }
            throw new \RuntimeException(
                sprintf('OpenAI API returned HTTP %d: %s%s', $status, mb_substr($detail, 0, 800), $hint)
            );
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('OpenAI API returned a non-JSON body.');
        }

        // Chat Completions returns
        // { choices: [{ message: {role, content}, finish_reason }, ...] }.
        // We only ever ask for one choice, so read the first.
        $choice = $decoded['choices'][0] ?? null;
        if (!is_array($choice)) {
            throw new \RuntimeException('OpenAI API returned no completion choices.');
        }

        // A refusal comes back as a sibling of `content`, with `content`
        // null. Surfacing it beats reporting a bare empty response.
        $refusal = $choice['message']['refusal'] ?? null;
        if (is_string($refusal) && $refusal !== '') {
            throw new \RuntimeException(
                sprintf('OpenAI declined the request: %s', mb_substr($refusal, 0, 800))
            );
        }

        $content = $choice['message']['content'] ?? null;
        $text    = is_string($content) ? $content : '';

        if ($text === '') {
            // Reasoning models burn the whole budget on hidden reasoning
            // tokens if max_completion_tokens is too tight, and return an
            // empty content string with finish_reason "length". Say so,
            // rather than leaving the user staring at "empty response".
            $finish = (string) ($choice['finish_reason'] ?? '');
            $hint   = $finish === 'length'
                ? ' The response hit the token cap before any text was produced — raise Max tokens in Options.'
                : '';
            throw new \RuntimeException('OpenAI API returned an empty text response.' . $hint);
        }

        return $text;
    }
}
