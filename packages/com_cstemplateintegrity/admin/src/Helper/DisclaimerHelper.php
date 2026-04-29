<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * First-run "make sure you have a backup; AI can make mistakes; ask
 * for help if you're not sure" disclaimer. Per-user — each admin
 * acknowledges separately, so handing a site to a client still
 * surfaces the warning the first time they open the component.
 *
 * State is persisted as a row in #__cstemplateintegrity_actions with
 * action = ACTION_DISCLAIMER_ACKNOWLEDGED. Reusing the action log
 * avoids a schema migration AND gives us a free audit trail of who
 * acknowledged when. To reset for a single user (e.g. for testing),
 * delete that row.
 */

declare(strict_types=1);

namespace Cybersalt\Component\Cstemplateintegrity\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class DisclaimerHelper
{
    /**
     * Has the current user already clicked "don't show again"?
     */
    public static function hasAcknowledged(int $userId): bool
    {
        if ($userId <= 0) {
            return true; // anonymous shouldn't see admin pages anyway; act as acknowledged
        }

        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__cstemplateintegrity_actions'))
                ->where($db->quoteName('action') . ' = ' . $db->quote(ActionLogHelper::ACTION_DISCLAIMER_ACKNOWLEDGED))
                ->where($db->quoteName('user_id') . ' = :uid')
                ->bind(':uid', $userId, ParameterType::INTEGER);

            $db->setQuery($query, 0, 1);
            return (bool) $db->loadResult();
        } catch (\Throwable $e) {
            // Database hiccup shouldn't bury the user under a modal
            // every page load. Treat as acknowledged on error.
            return true;
        }
    }

    /**
     * Persist a "don't show again" click for the current user.
     */
    public static function acknowledge(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        // ActionLogHelper::log already pulls the current identity, but
        // we pass the id explicitly here in case it's ever called from
        // a context where the application identity hasn't been resolved.
        ActionLogHelper::log(
            ActionLogHelper::ACTION_DISCLAIMER_ACKNOWLEDGED,
            ['acknowledged_user_id' => $userId]
        );
    }

    /**
     * Render the modal HTML + inline JS for the current request, OR
     * return an empty string if the current user has already
     * dismissed it permanently. Templates call this once near the
     * top of every default.php; identical output across views so
     * navigating between dashboard/sessions/etc. is consistent.
     */
    public static function renderModalIfNeeded(): string
    {
        $userId = self::currentUserId();
        if ($userId <= 0 || self::hasAcknowledged($userId)) {
            return '';
        }

        $token = Session::getFormToken();
        $url   = Route::_(
            'index.php?option=com_cstemplateintegrity&task=display.acknowledgeDisclaimer&' . $token . '=1',
            false
        );
        $urlEsc = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $title    = self::esc(Text::_('COM_CSTEMPLATEINTEGRITY_DISCLAIMER_TITLE'));
        $body     = Text::_('COM_CSTEMPLATEINTEGRITY_DISCLAIMER_BODY');           // contains intentional HTML
        $checkbox = self::esc(Text::_('COM_CSTEMPLATEINTEGRITY_DISCLAIMER_DONT_SHOW_AGAIN'));
        $confirm  = self::esc(Text::_('COM_CSTEMPLATEINTEGRITY_DISCLAIMER_CONFIRM_BUTTON'));

        // Inline rendering rather than Bootstrap modal asset so we
        // don't have to wire `bootstrap.modal` into every view's
        // HtmlView::display(). Vanilla overlay + card; Bootstrap CSS
        // tokens for color so it tracks Atum light/dark.
        return <<<HTML
<div id="csti-disclaimer-overlay" class="csti-disclaimer-overlay" role="dialog" aria-modal="true" aria-labelledby="csti-disclaimer-title" data-acknowledge-url="{$urlEsc}">
    <div class="csti-disclaimer-card">
        <h3 id="csti-disclaimer-title" class="csti-disclaimer-title">{$title}</h3>
        <div class="csti-disclaimer-body">{$body}</div>
        <div class="csti-disclaimer-checkrow">
            <label>
                <input type="checkbox" id="csti-disclaimer-dismiss-check"> {$checkbox}
            </label>
        </div>
        <div class="csti-disclaimer-actions">
            <button type="button" class="btn btn-primary" id="csti-disclaimer-ok-btn">{$confirm}</button>
        </div>
    </div>
</div>

<style>
.csti-disclaimer-overlay {
    position: fixed; inset: 0; z-index: 1080;
    background: rgba(0, 0, 0, 0.62);
    display: flex; align-items: center; justify-content: center;
    padding: 1rem;
}
.csti-disclaimer-card {
    background: var(--bs-body-bg, #fff);
    color: var(--bs-body-color, #212529);
    border: 1px solid var(--bs-border-color, rgba(0,0,0,0.15));
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.4);
    max-width: 640px; width: 100%;
    padding: 1.75rem 2rem;
    line-height: 1.5;
}
.csti-disclaimer-title {
    margin: 0 0 1rem 0;
    font-size: 1.4rem;
}
.csti-disclaimer-body p { margin-bottom: 0.85rem; }
.csti-disclaimer-body strong { color: var(--bs-body-color); }
.csti-disclaimer-checkrow {
    margin: 1.25rem 0 1rem 0;
    padding: 0.75rem 1rem;
    background: var(--bs-tertiary-bg, rgba(0,0,0,0.04));
    border-radius: 0.375rem;
}
.csti-disclaimer-checkrow label { cursor: pointer; user-select: none; }
.csti-disclaimer-checkrow input { margin-right: 0.5rem; }
.csti-disclaimer-actions { text-align: right; }
</style>

<script>
(function () {
    var overlay = document.getElementById('csti-disclaimer-overlay');
    if (!overlay) return;

    var url     = overlay.dataset.acknowledgeUrl;
    var checkbox = document.getElementById('csti-disclaimer-dismiss-check');
    var okBtn    = document.getElementById('csti-disclaimer-ok-btn');

    // Prevent the rest of the page from scrolling under the modal.
    var prevOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    function close() {
        document.body.style.overflow = prevOverflow;
        overlay.parentNode && overlay.parentNode.removeChild(overlay);
    }

    okBtn.addEventListener('click', function () {
        if (checkbox.checked) {
            // Fire-and-forget. If the persist call fails the worst
            // outcome is the modal reappears next page load, which is
            // a fine fallback.
            try {
                fetch(url, { method: 'POST', credentials: 'same-origin' });
            } catch (e) { /* swallow */ }
        }
        close();
    });
})();
</script>
HTML;
    }

    private static function currentUserId(): int
    {
        try {
            $app  = Factory::getApplication();
            $user = $app->getIdentity();
            return $user ? (int) $user->id : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
