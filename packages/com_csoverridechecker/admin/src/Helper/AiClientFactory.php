<?php

/**
 * @package     Csoverridechecker
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csoverridechecker\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

/**
 * Resolves the configured AI provider into a concrete client.
 *
 * Only the one-shot scan path goes through here. The follow-up chat agent
 * (ConversationRunner) drives Anthropic's tool_use / tool_result protocol
 * directly and has no cross-provider equivalent yet, so it deliberately
 * does NOT use this factory — see hasChatSupport().
 */
final class AiClientFactory
{
    public const PROVIDER_ANTHROPIC = 'anthropic';
    public const PROVIDER_OPENAI    = 'openai';
    public const PROVIDER_GEMINI    = 'gemini';

    /**
     * Which component option holds the key, and which holds the scan model,
     * for each provider. Keeping this in one place means adding a fourth
     * provider is a single entry here plus a client class.
     */
    private const MAP = [
        self::PROVIDER_ANTHROPIC => ['key' => 'anthropic_api_key', 'model' => 'scan_model'],
        self::PROVIDER_OPENAI    => ['key' => 'openai_api_key',    'model' => 'scan_model_openai'],
        self::PROVIDER_GEMINI    => ['key' => 'gemini_api_key',    'model' => 'scan_model_gemini'],
    ];

    /**
     * The provider currently selected in Options, normalised. Anything
     * unrecognised falls back to Anthropic rather than throwing, so a
     * hand-edited or partially-migrated params blob can't brick the
     * dashboard.
     */
    public static function currentProvider(): string
    {
        $raw = (string) ComponentHelper::getParams('com_csoverridechecker')
            ->get('ai_provider', self::PROVIDER_ANTHROPIC);

        return isset(self::MAP[$raw]) ? $raw : self::PROVIDER_ANTHROPIC;
    }

    /**
     * The saved API key for a provider (defaults to the current one).
     * Returned raw — callers must never echo it.
     */
    public static function apiKeyFor(?string $provider = null): string
    {
        $provider = $provider ?? self::currentProvider();

        return trim((string) ComponentHelper::getParams('com_csoverridechecker')
            ->get(self::MAP[$provider]['key'], ''));
    }

    /**
     * True when the selected provider has a key saved — i.e. Method 2
     * (Run automated scan) is actually usable right now.
     */
    public static function hasKey(?string $provider = null): bool
    {
        return self::apiKeyFor($provider) !== '';
    }

    /**
     * The chat-with-your-AI follow-up on a session applies fixes through
     * tool calls. That loop is written against Anthropic's tool_use /
     * tool_result protocol; OpenAI and Gemini express the same idea with
     * different message shapes, so the loop is not portable as-is.
     *
     * Surfaced as a capability check rather than hidden, so the UI can say
     * plainly "scans work, chat needs Anthropic" instead of failing at the
     * moment the user tries to apply a fix.
     */
    public static function hasChatSupport(?string $provider = null): bool
    {
        return ($provider ?? self::currentProvider()) === self::PROVIDER_ANTHROPIC;
    }

    /**
     * Build a client for the selected provider.
     *
     * @param  string|null  $modelOverride  Bypass the configured model (used
     *                                      by the cheap connection test).
     *
     * @throws \RuntimeException when no key is saved for that provider.
     */
    public static function make(?string $provider = null, ?string $modelOverride = null): AiClientInterface
    {
        $provider = $provider ?? self::currentProvider();
        $key      = self::apiKeyFor($provider);

        if ($key === '') {
            throw new \RuntimeException(
                Text::sprintf('COM_CSOVERRIDECHECKER_ERROR_NO_KEY_FOR_PROVIDER', self::labelFor($provider))
            );
        }

        $model = $modelOverride ?? (string) ComponentHelper::getParams('com_csoverridechecker')
            ->get(self::MAP[$provider]['model'], '');

        return match ($provider) {
            self::PROVIDER_OPENAI => $model !== ''
                ? new OpenAiClient($key, $model)
                : new OpenAiClient($key),
            self::PROVIDER_GEMINI => $model !== ''
                ? new GeminiClient($key, $model)
                : new GeminiClient($key),
            default => $model !== ''
                ? new AnthropicClient($key, $model)
                : new AnthropicClient($key),
        };
    }

    /**
     * Cheapest model each provider offers — used by the Diagnostics
     * "Run test" button so verifying a key costs as close to nothing as
     * possible.
     */
    public static function cheapestModelFor(?string $provider = null): string
    {
        return match ($provider ?? self::currentProvider()) {
            self::PROVIDER_OPENAI => 'gpt-5-mini',
            self::PROVIDER_GEMINI => 'gemini-2.5-flash',
            default               => 'claude-haiku-4-5-20251001',
        };
    }

    public static function labelFor(?string $provider = null): string
    {
        return match ($provider ?? self::currentProvider()) {
            self::PROVIDER_OPENAI => 'OpenAI',
            self::PROVIDER_GEMINI => 'Google Gemini',
            default               => 'Anthropic',
        };
    }
}
