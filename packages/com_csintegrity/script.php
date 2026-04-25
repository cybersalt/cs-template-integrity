<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

final class Com_CsintegrityInstallerScript
{
    private string $minimumJoomla = '5.0.0';
    private string $minimumPhp    = '8.1.0';

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Log::add(
                Text::sprintf('COM_CSINTEGRITY_ERROR_PHP_VERSION', $this->minimumPhp, PHP_VERSION),
                Log::WARNING,
                'jerror'
            );
            return false;
        }

        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Log::add(
                Text::sprintf('COM_CSINTEGRITY_ERROR_JOOMLA_VERSION', $this->minimumJoomla, JVERSION),
                Log::WARNING,
                'jerror'
            );
            return false;
        }

        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        $this->logLifecycleEvent($type);
        $this->showPostInstallMessage($type);
        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    private function logLifecycleEvent(string $type): void
    {
        $helper = '\\Cybersalt\\Component\\Csintegrity\\Administrator\\Helper\\ActionLogHelper';
        if (!class_exists($helper)) {
            return;
        }

        $action = $type === 'update'
            ? $helper::ACTION_UPDATE
            : $helper::ACTION_INSTALL;

        try {
            $helper::log($action, ['type' => $type]);
        } catch (\Throwable $e) {
            // Swallow — install must not crash on logging.
        }
    }

    protected function showPostInstallMessage(string $type): void
    {
        $messageKey = $type === 'update'
            ? 'COM_CSINTEGRITY_POSTINSTALL_UPDATED'
            : 'COM_CSINTEGRITY_POSTINSTALL_INSTALLED';
        $url = 'index.php?option=com_csintegrity&view=dashboard';

        echo '<div class="card mb-3" style="margin: 20px 0;">'
            . '<div class="card-body">'
            . '<h3 class="card-title">' . Text::_('COM_CSINTEGRITY') . '</h3>'
            . '<p class="card-text">' . Text::_($messageKey) . '</p>'
            . '<a href="' . $url . '" class="btn btn-primary text-white">'
            . '<span class="icon-dashboard" aria-hidden="true"></span> '
            . Text::_('COM_CSINTEGRITY_POSTINSTALL_OPEN')
            . '</a>'
            . '</div></div>';
    }
}
