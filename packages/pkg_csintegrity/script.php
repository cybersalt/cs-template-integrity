<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Package-level installer for pkg_csintegrity. Auto-enables the
 * webservices plugin on install so the component's /v1/csintegrity/*
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

final class Pkg_CsintegrityInstallerScript
{
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'install' || $type === 'update') {
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
                ->where($db->quoteName('element') . ' = ' . $db->quote('csintegrity'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('webservices'));

            $db->setQuery($query)->execute();
        } catch (\Throwable $e) {
            Log::add(
                'Could not auto-enable plg_webservices_csintegrity: ' . $e->getMessage(),
                Log::WARNING,
                'pkg_csintegrity'
            );
        }
    }

    private function showPostInstallMessage(string $type): void
    {
        $messageKey = $type === 'update'
            ? 'PKG_CSINTEGRITY_POSTINSTALL_UPDATED'
            : 'PKG_CSINTEGRITY_POSTINSTALL_INSTALLED';
        $url = 'index.php?option=com_csintegrity&view=dashboard';

        echo '<div class="card mb-3" style="margin: 20px 0;">'
            . '<div class="card-body">'
            . '<h3 class="card-title">' . Text::_('PKG_CSINTEGRITY') . '</h3>'
            . '<p class="card-text">' . Text::_($messageKey) . '</p>'
            . '<a href="' . $url . '" class="btn btn-primary text-white">'
            . '<span class="icon-dashboard" aria-hidden="true"></span> '
            . Text::_('PKG_CSINTEGRITY_POSTINSTALL_OPEN')
            . '</a>'
            . '</div></div>';
    }
}
