<?php

/**
 * @package     Csoverridechecker
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Common surface for the pluggable AI providers.
 *
 * Every provider client (Anthropic, OpenAI, Gemini) speaks a different
 * wire format, but the component only ever needs one thing from them:
 * "here is a system prompt and a conversation — give me the assistant's
 * next reply as a string". This interface is that contract, so
 * ScanRunnerHelper / ConversationRunner can hold a provider-agnostic
 * handle and the provider choice becomes a single factory decision.
 *
 * Deliberately narrow: no streaming, no tool-use loop, no token
 * accounting. Those are provider-specific enough that folding them in
 * now would leak one vendor's shape into the interface. They land as
 * separate capability interfaces if and when we need them.
 *
 * Implementations MUST NOT leak the API key — not into exception
 * messages, not into logs, not into a request URL. `keyFingerprint()`
 * exists precisely so diagnostics have a safe thing to print instead.
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csoverridechecker\Administrator\Helper;

defined('_JEXEC') or die;

interface AiClientInterface
{
    /**
     * Send a single non-streaming request and return the assistant's
     * reply as text.
     *
     * Implementations throw \RuntimeException on any upstream failure
     * (non-2xx status, unparseable body, empty completion) rather than
     * returning a partial or empty string. The exception message may
     * quote the provider's own error text but must never contain the
     * API key.
     *
     * @param  string                                                  $system       System prompt.
     * @param  list<array{role: 'user'|'assistant', content: string}>  $messages     Conversation so far.
     * @param  int                                                     $maxTokens    Hard cap on response length.
     * @param  int                                                     $timeoutSecs  HTTP timeout.
     *
     * @return string  The assistant's text.
     *
     * @throws \RuntimeException  On any upstream or decoding failure.
     */
    public function complete(string $system, array $messages, int $maxTokens = 8192, int $timeoutSecs = 120): string;

    /**
     * Non-secret fingerprint of the configured key, for diagnostics.
     *
     * Must be a one-way hash plus metadata (length, provider-specific
     * plausibility hints). Implementations MUST NOT return any
     * characters of the raw key — not a prefix, not a suffix.
     */
    public function keyFingerprint(): string;

    /**
     * Human-readable provider name for the UI and for error messages,
     * e.g. "Anthropic", "OpenAI", "Google Gemini".
     */
    public function providerLabel(): string;
}
