<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Package-level installer for pkg_cstemplateintegrity. Auto-enables the
 * webservices plugin on install so the component's /v1/cstemplateintegrity/*
 * routes work immediately — otherwise Joomla would install the plugin
 * disabled and the endpoint would 404 until the admin flipped it on
 * manually.
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

final class Pkg_CstemplateintegrityInstallerScript
{
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        // Joomla also calls postflight() on uninstall. Skip everything but
        // install/update so we don't auto-enable a plugin that's about to
        // be removed and don't render an "installed, click here" card on
        // an uninstall.
        if (!\in_array($type, ['install', 'update', 'discover_install'], true)) {
            return true;
        }

        if ($type === 'install' || $type === 'update' || $type === 'discover_install') {
            $this->enableWebservicesPlugin();
        }

        $this->showPostInstallMessage($type);

        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    private function enableWebservicesPlugin(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('cstemplateintegrity'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('webservices'));

            $db->setQuery($query)->execute();
        } catch (\Throwable $e) {
            Log::add(
                'Could not auto-enable plg_webservices_cstemplateintegrity: ' . $e->getMessage(),
                Log::WARNING,
                'pkg_cstemplateintegrity'
            );
        }
    }

    private function showPostInstallMessage(string $type): void
    {
        $messageKey = $type === 'update'
            ? 'PKG_CSTEMPLATEINTEGRITY_POSTINSTALL_UPDATED'
            : 'PKG_CSTEMPLATEINTEGRITY_POSTINSTALL_INSTALLED';
        $url = 'index.php?option=com_cstemplateintegrity&view=dashboard';

        // Translated language strings are echoed escaped — see the
        // matching note in com_cstemplateintegrity/script.php.
        $h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        echo '<div class="card mb-3" style="margin: 20px 0;">'
            . '<div class="card-body">'
            . '<h3 class="card-title">' . $h(Text::_('PKG_CSTEMPLATEINTEGRITY')) . '</h3>'
            . '<p class="card-text">' . $h(Text::_($messageKey)) . '</p>'
            . '<a href="' . $h($url) . '" class="btn btn-primary text-white">'
            . '<span class="icon-dashboard" aria-hidden="true"></span> '
            . $h(Text::_('PKG_CSTEMPLATEINTEGRITY_POSTINSTALL_OPEN'))
            . '</a>'
            . '</div></div>';
    }
}
