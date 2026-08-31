<?php

/**
 * @package     Csoverridechecker
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Tiny HTTP wrapper around Google's Generative Language API (Gemini).
 *
 * Sibling of AnthropicClient — same job, same narrow surface: one
 * non-streaming `generateContent` request in, the assistant's reply
 * out as a markdown string. No tool-use loop, no streaming, no caching.
 *
 * Gemini's wire format differs from both of its siblings in three ways
 * that are easy to get wrong:
 *
 *  1. The model name lives in the URL path, not the payload:
 *     `…/models/<model>:generateContent`. We rawurlencode it so a
 *     mis-typed model from Options can never escape the path segment.
 *  2. The system prompt is a top-level `system_instruction` object
 *     shaped `{"parts":[{"text": "…"}]}` — not a plain string, and not
 *     a message in the list.
 *  3. Turns live in `contents`, and the assistant role is spelled
 *     `model`, not `assistant`. We translate on the way in.
 *
 * Auth is the `x-goog-api-key` header. Google also accepts `?key=…` in
 * the query string; we deliberately do NOT use it. A credential in a
 * URL ends up in access logs, proxy logs, and Joomla's own error
 * reporting the moment a request fails.
 *
 * Reference: https://ai.google.dev/api/generate-content
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csoverridechecker\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;

final class GeminiClient implements AiClientInterface
{
    public const DEFAULT_MODEL = 'gemini-2.5-pro';
    public const ENDPOINT_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';

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
            throw new \InvalidArgumentException('Google Gemini API key is empty.');
        }

        $model = trim($model);
        if ($model === '') {
            $model = self::DEFAULT_MODEL;
        }

        $this->apiKey = $apiKey;
        $this->model  = $model;
    }

    public function providerLabel(): string
    {
        return 'Google Gemini';
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
     * Google AI Studio keys are consistently 39 characters (an `AIza`
     * prefix plus 35 more). A length meaningfully different from 39
     * strongly suggests truncation during save — or a service-account
     * credential pasted in by mistake, which this endpoint will not
     * accept.
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
        if ($len < 35) {
            $expectationSuffix = ' — Google AI Studio keys are 39 chars; this one looks truncated';
        } elseif ($len > 45) {
            $expectationSuffix = ' — Google AI Studio keys are 39 chars; this one looks too long (a service-account key or OAuth token will not work here)';
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
     * Send a single generateContent request and return the concatenated
     * text from the assistant's reply.
     *
     * @param  string                                                  $system       System prompt.
     * @param  list<array{role: 'user'|'assistant', content: string}>  $messages
     * @param  int                                                     $maxTokens    Hard cap on response length.
     * @param  int                                                     $timeoutSecs  HTTP timeout.
     */
    public function complete(string $system, array $messages, int $maxTokens = 8192, int $timeoutSecs = 120): string
    {
        // Gemini calls the assistant role "model". Translate, and keep
        // anything else as "user" — the API rejects unknown roles.
        $contents = [];
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }
            $role = (string) ($message['role'] ?? 'user');
            $contents[] = [
                'role'  => $role === 'assistant' || $role === 'model' ? 'model' : 'user',
                'parts' => [['text' => (string) ($message['content'] ?? '')]],
            ];
        }

        $payload = [
            'system_instruction' => ['parts' => [['text' => $system]]],
            'contents'           => $contents,
            'generationConfig'   => ['maxOutputTokens' => $maxTokens],
        ];

        // rawurlencode the model so a stray slash or query char from
        // Options cannot break out of the path segment and retarget
        // the request at some other endpoint.
        $endpoint = self::ENDPOINT_BASE . rawurlencode($this->model) . ':generateContent';

        $http = HttpFactory::getHttp();
        $body = $headers = $status = null;

        // Retry-once-with-backoff on 429 (rate limit). Gemini's free
        // tier in particular caps requests per minute hard; if a recent
        // call ate the budget, this single sleep+retry usually bridges
        // back into the next minute window. Cap at 60s to keep the page
        // responsive.
        for ($attempt = 0; $attempt <= 1; $attempt++) {
            $response = $http->post(
                $endpoint,
                json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                [
                    'x-goog-api-key' => $this->apiKey,
                    'content-type'   => 'application/json',
                ],
                $timeoutSecs
            );

            $status  = (int) $response->code;
            $body    = (string) $response->body;
            $headers = is_array($response->headers ?? null) ? $response->headers : [];

            if ($status !== 429 || $attempt > 0) {
                break;
            }

            // Google usually returns its backoff in a RetryInfo detail
            // rather than a header, so retry-after is best-effort.
            // Cap at 60s so we don't sit on a single page for minutes.
            $retryAfter = isset($headers['retry-after'])
                ? (int) (is_array($headers['retry-after']) ? $headers['retry-after'][0] : $headers['retry-after'])
                : 30;
            $retryAfter = max(5, min(60, $retryAfter));
            @set_time_limit($timeoutSecs + $retryAfter + 30);
            sleep($retryAfter);
        }

        if ($status < 200 || $status >= 300) {
            // Try to surface Google's structured error message; fall back to the raw body.
            // Note the raw body is echoed, never the key — the key only
            // ever exists in the request header we just sent, which is
            // exactly why it is not in the URL.
            $detail  = $body;
            $decoded = json_decode($body, true);
            if (is_array($decoded) && isset($decoded['error']['message'])) {
                $detail = $decoded['error']['message'];
            }
            // For auth-shaped failures, append a non-secret key
            // fingerprint so the user can verify the saved key matches
            // what's in Google AI Studio without us echoing the secret.
            // Common causes: copy-paste truncation, key restricted to
            // the wrong API, key from a project without the Generative
            // Language API enabled.
            $hint = '';
            if ($status === 400 || $status === 401 || $status === 403) {
                $hint = sprintf(
                    ' — Diagnostics: %s. Compare against Google AI Studio; if they do not match, re-paste the key in Options. Also check the key is not restricted to a different API.',
                    $this->keyFingerprint()
                );
            } elseif ($status === 404) {
                $hint = sprintf(
                    ' — Model "%s" was not found for this API version. Check the model name in Options.',
                    $this->model
                );
            } elseif ($status === 429) {
                // Auto-retry already happened once; if we are still
                // here the per-minute budget is fully consumed.
                $hint = ' — The extension waited and retried once already, so the per-minute budget is fully consumed. Wait 1–2 minutes before trying again. If this happens often, lower Overrides per scan in Options, or move the project off the Gemini free tier at https://aistudio.google.com/';
            }
            throw new \RuntimeException(
                sprintf('Google Gemini API returned HTTP %d: %s%s', $status, mb_substr($detail, 0, 800), $hint)
            );
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Google Gemini API returned a non-JSON body.');
        }

        // A prompt refused up front comes back 200 with no candidates at
        // all and a promptFeedback.blockReason instead. Surface that
        // rather than reporting a bare empty response.
        $blockReason = $decoded['promptFeedback']['blockReason'] ?? null;
        if (is_string($blockReason) && $blockReason !== '') {
            throw new \RuntimeException(
                sprintf('Google Gemini blocked the request (%s).', $blockReason)
            );
        }

        $candidate = $decoded['candidates'][0] ?? null;
        if (!is_array($candidate)) {
            throw new \RuntimeException('Google Gemini API returned no candidates.');
        }

        // generateContent returns
        // { candidates: [{ content: { parts: [{text: "…"}, …] } }, …] }.
        // Concatenate every text part; ignore other part types
        // (functionCall, inlineData etc.) for now.
        $text = '';
        if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
            foreach ($candidate['content']['parts'] as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }

        if ($text === '') {
            // Gemini 2.5 spends hidden thinking tokens out of the same
            // budget, so a tight maxOutputTokens can finish with
            // MAX_TOKENS before any visible text lands. Say so, rather
            // than leaving the user staring at "empty response".
            $finish = (string) ($candidate['finishReason'] ?? '');
            $hint   = '';
            if ($finish === 'MAX_TOKENS') {
                $hint = ' The response hit the token cap before any text was produced — raise Max tokens in Options.';
            } elseif ($finish !== '' && $finish !== 'STOP') {
                $hint = sprintf(' Finish reason: %s.', $finish);
            }
            throw new \RuntimeException('Google Gemini API returned an empty text response.' . $hint);
        }

        return $text;
    }
}
