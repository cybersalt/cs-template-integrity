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
        try {
            $this->showPostInstallMessage($type);
        } catch (\Throwable $e) {
            // Never block install on a postflight UI failure.
        }
        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    protected function showPostInstallMessage(string $type): void
    {
        $messageKey = $type === 'update'
            ? 'COM_CSINTEGRITY_POSTINSTALL_UPDATED'
            : 'COM_CSINTEGRITY_POSTINSTALL_INSTALLED';
        $url = 'index.php?option=com_csintegrity&view=dashboard';

        // Translated language strings are echoed escaped — the
        // post-install message is rendered into Joomla's installer
        // output frame, and even strings we control today shouldn't
        // be templated as raw HTML, since a future translation file
        // could carry markup that breaks the layout.
        $h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        echo '<div class="card mb-3" style="margin: 20px 0;">'
            . '<div class="card-body">'
            . '<h3 class="card-title">' . $h(Text::_('COM_CSINTEGRITY')) . '</h3>'
            . '<p class="card-text">' . $h(Text::_($messageKey)) . '</p>'
            . '<a href="' . $h($url) . '" class="btn btn-primary text-white">'
            . '<span class="icon-dashboard" aria-hidden="true"></span> '
            . $h(Text::_('COM_CSINTEGRITY_POSTINSTALL_OPEN'))
            . '</a>'
            . '</div></div>';
    }
}
