<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Hard guards around any code path that writes to disk under
 * JPATH_ROOT. Encapsulates the path-traversal check and the
 * "where is it safe to write a .php file" policy in one place so
 * we cannot accidentally drift apart in restore vs. apply-fix.
 *
 * The traversal check is separator-anchored (str_starts_with with a
 * trailing DIRECTORY_SEPARATOR) — strpos-based prefix checks are
 * bypassable when the site root has a sibling directory whose name
 * begins with the same prefix, e.g. /var/www/joomla and /var/www/joomla-bak.
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\Helper;

defined('_JEXEC') or die;

final class PathSafetyHelper
{
    /**
     * PHP-executable extensions. Writing one of these outside of a
     * Joomla template's html/ folder is refused, regardless of who
     * the caller is — defence in depth against an authenticated
     * write becoming an RCE primitive.
     */
    private const PHP_EXECUTABLE_EXTENSIONS = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'pht'];

    /**
     * Resolve and assert that $absolute lives under JPATH_ROOT.
     *
     * Resolves dirname($absolute) through realpath() so symlinks and
     * .. segments collapse, then compares with a separator-anchored
     * str_starts_with — preventing the /var/www/joomla vs
     * /var/www/joomla-bak prefix-collision bypass.
     *
     * Returns the canonical $rootReal so callers can reuse it.
     *
     * @return array{rootReal: string, parentReal: string}
     *
     * @throws \RuntimeException if the parent dir is missing or escapes the root.
     */
    public static function assertWithinRoot(string $absolute): array
    {
        $parentReal = realpath(\dirname($absolute));
        $rootReal   = realpath(JPATH_ROOT);

        if ($parentReal === false || $rootReal === false) {
            throw new \RuntimeException('Refusing to write: target path could not be resolved.');
        }

        $rootSep   = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $parentSep = $parentReal . DIRECTORY_SEPARATOR;

        if (!str_starts_with($parentSep, $rootSep)) {
            throw new \RuntimeException('Refusing to write: target path is outside the Joomla site root.');
        }

        return ['rootReal' => $rootReal, 'parentReal' => $parentReal];
    }

    /**
     * Reject PHP-executable writes outside of templates/<tpl>/html/.
     *
     * Rationale: the ONLY place this extension legitimately writes
     * .php to is a template override file, which always lives under
     * <root>/templates/<name>/html/ (site) or
     * <root>/administrator/templates/<name>/html/ (admin). Anywhere
     * else — components, modules, plugins, libraries, /tmp, /cache —
     * writing PHP is either a misconfiguration or an attack.
     *
     * Files whose extension isn't PHP-executable are allowed anywhere
     * inside JPATH_ROOT (still subject to assertWithinRoot()).
     *
     * @throws \RuntimeException if the extension is PHP-ish and the
     *                           path is not under templates/.../html/.
     */
    public static function assertPhpWriteAllowed(string $absolute): void
    {
        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        if (!in_array($ext, self::PHP_EXECUTABLE_EXTENSIONS, true)) {
            return;
        }

        $normalized = str_replace('\\', '/', $absolute);

        // Allowed: <root>/templates/<tpl>/html/<...>.php
        // Allowed: <root>/administrator/templates/<tpl>/html/<...>.php
        $isSite  = (bool) preg_match('#/templates/[^/]+/html/.+\.[A-Za-z0-9]+$#', $normalized);
        $isAdmin = (bool) preg_match('#/administrator/templates/[^/]+/html/.+\.[A-Za-z0-9]+$#', $normalized);

        if (!$isSite && !$isAdmin) {
            throw new \RuntimeException(
                'Refusing to write a PHP-executable file outside of a template override path. '
                . 'Target: ' . $normalized
            );
        }
    }

    /**
     * Drop the PHP opcache entry for $absolute after a write so the
     * next request reads the new bytes. No-op when opcache isn't
     * loaded or has API access disabled.
     */
    public static function invalidateOpcacheIfPhp(string $absolute): void
    {
        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        if (!in_array($ext, self::PHP_EXECUTABLE_EXTENSIONS, true)) {
            return;
        }

        if (\function_exists('opcache_invalidate')) {
            @opcache_invalidate($absolute, true);
        }
    }
}
