<?php

/**
 * @package     Cstemplateintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Custom form field for the Anthropic API key in component Options.
 *
 * Why not type="password"?
 *   Joomla's password field truncated the saved value during testing
 *   (lost ~9 chars off the end of a 108-char Anthropic key). The
 *   v2.0.0 fix was switching to type="text" — which round-trips fully
 *   but renders the key in plaintext, vulnerable to shoulder-surfing
 *   (audit finding I-9).
 *
 * What this field does:
 *   Renders a normal type="text" input (so the key persists intact),
 *   wraps it in a Bootstrap input-group with a "Reveal" toggle, and
 *   applies a CSS blur filter to the input by default. Focusing the
 *   field OR clicking the toggle removes the blur. Click-out
 *   re-blurs unless the toggle was used.
 *
 * The CSS + JS ride along inline because Joomla's com_config form
 * doesn't auto-load this component's media bundle.
 */

declare(strict_types=1);

namespace Cybersalt\Component\Cstemplateintegrity\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class ApikeyField extends TextField
{
    /**
     * @var string
     */
    protected $type = 'Apikey';

    /**
     * @return string
     */
    protected function getInput()
    {
        // Build a default text input via the parent renderer, then
        // splice in a class so the CSS rule below targets it.
        $textInput = parent::getInput();
        $textInput = preg_replace(
            '/\bclass="([^"]*)"/',
            'class="$1 csti-apikey-input"',
            (string) $textInput,
            1
        );
        if ($textInput === null || strpos($textInput, 'csti-apikey-input') === false) {
            // No existing class attribute on the input — inject one.
            $textInput = (string) preg_replace(
                '/<input\s/',
                '<input class="csti-apikey-input" ',
                (string) parent::getInput(),
                1
            );
        }

        $reveal = htmlspecialchars(
            (string) Text::_('COM_CSTEMPLATEINTEGRITY_FIELD_APIKEY_REVEAL'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $hide = htmlspecialchars(
            (string) Text::_('COM_CSTEMPLATEINTEGRITY_FIELD_APIKEY_HIDE'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $clearLabel = htmlspecialchars(
            (string) Text::_('COM_CSTEMPLATEINTEGRITY_FIELD_APIKEY_CLEAR'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $clearTitle = htmlspecialchars(
            (string) Text::_('COM_CSTEMPLATEINTEGRITY_FIELD_APIKEY_CLEAR_TITLE'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $inputId   = htmlspecialchars((string) $this->id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $btnId     = $inputId . '-toggle';
        $clearId   = $inputId . '-clear';
        $helpBtnId = $inputId . '-helplink';

        // The currently-saved value drives the initial visibility of the
        // optional helplink button. With a value present the user almost
        // certainly doesn't need to navigate to the profile page; with no
        // value, the help link is the path forward. JS keeps this in
        // sync as the user types or clicks Clear.
        $hasValue = trim((string) $this->value) !== '';

        // Optional "help link" button rendered after the reveal toggle.
        // Set via <field helplink="..." helplinklabel="..."/> in config.xml.
        // Used on the joomla_api_token field to point users at their
        // profile page (Joomla doesn't expose the plaintext token after
        // generation, so the best we can do is open the right tab in a
        // new window for them to copy from).
        $helpLinkRaw  = (string) $this->element['helplink'];
        $helpLabelRaw = (string) $this->element['helplinklabel'];
        $helpButton   = '';
        if ($helpLinkRaw !== '') {
            // Substitute {user_id} with the currently-logged-in admin's
            // user id so config.xml can point at task=user.edit&id=…
            // (the admin's own profile) rather than the user list. The
            // Joomla API Token tab lives on that page; without an id the
            // URL falls through to com_users' default view (the user
            // list), which is what Tim hit on the first iteration.
            $userId = (int) (Factory::getApplication()->getIdentity()->id ?? 0);
            $helpLinkResolved = str_replace('{user_id}', (string) $userId, $helpLinkRaw);

            // Route::_() defaults to xhtml=true (returns &amp;); pass
            // false so we get raw ampersands, then htmlspecialchars
            // encodes them once. Calling Route::_() with the default
            // and then htmlspecialchars on top double-encodes to
            // &amp;amp; — browsers decode that to literal &amp; in the
            // URL, which Joomla then sees as an invalid query string.
            $helpUrl   = htmlspecialchars(
                Route::_($helpLinkResolved, false),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            $helpLabel = htmlspecialchars(
                $helpLabelRaw !== ''
                    ? (string) Text::_($helpLabelRaw)
                    : (string) Text::_('COM_CSTEMPLATEINTEGRITY_FIELD_APIKEY_HELP_DEFAULT'),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            $hiddenStyle = $hasValue ? ' style="display: none;"' : '';
            $helpButton = '<a href="' . $helpUrl . '" id="' . $helpBtnId . '"'
                . ' target="_blank" rel="noopener"'
                . ' class="btn btn-outline-secondary csti-apikey-helplink"'
                . $hiddenStyle . '>'
                . $helpLabel
                . '</a>';
        }

        // Inline CSS + JS ride along with the field so the Options
        // form, which is rendered by com_config and doesn't load our
        // media bundle, picks them up. The blur is removed on focus
        // (so the user can click the field to verify what they
        // pasted) AND on the toggle button click (sticky reveal).
        $css = <<<CSS
<style>
.csti-apikey-input {
    filter: blur(5px);
    transition: filter 0.15s ease-out;
    font-family: var(--bs-font-monospace, monospace);
    letter-spacing: 0.02em;
}
/*
 * Skip the blur when the field is empty so the user can see where
 * to paste. Also skip when focused (so they can verify what they
 * pasted) and when the Reveal toggle has been clicked.
 */
.csti-apikey-input:focus,
.csti-apikey-input.is-revealed,
.csti-apikey-input.is-empty {
    filter: none;
}
.csti-apikey-toggle .csti-apikey-toggle-label,
.csti-apikey-clear .csti-apikey-clear-label {
    margin-left: 0.35rem;
}
</style>
CSS;

        $js = <<<JS
<script>
(function () {
    var input    = document.getElementById('{$inputId}');
    var toggle   = document.getElementById('{$btnId}');
    var clearBtn = document.getElementById('{$clearId}');
    var helpBtn  = document.getElementById('{$helpBtnId}');
    if (!input) return;

    if (toggle) {
        var labelEl = toggle.querySelector('.csti-apikey-toggle-label');
        toggle.addEventListener('click', function () {
            var revealed = input.classList.toggle('is-revealed');
            if (labelEl) {
                labelEl.textContent = revealed ? '{$hide}' : '{$reveal}';
            }
            if (revealed) {
                input.focus();
            }
        });
    }

    // Clear button — empties the field client-side. Form save still
    // requires the user to click the toolbar Save (matches the rest of
    // the Options form's behavior).
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            syncToValue();
            input.focus();
        });
    }

    // Two things we keep in sync with input.value:
    //   1. The .is-empty class on the input — when set, CSS skips the
    //      blur filter so the user can see where to paste.
    //   2. Visibility of the optional Get-token / help link — only
    //      relevant while the field is empty; hidden once a value
    //      is present so it doesn't compete with the saved token.
    function syncToValue() {
        var empty = input.value === '';
        input.classList.toggle('is-empty', empty);
        if (helpBtn) {
            helpBtn.style.display = empty ? '' : 'none';
        }
    }
    input.addEventListener('input', syncToValue);
    syncToValue();
})();
</script>
JS;

        $wrapped = '<div class="input-group">'
            . $textInput
            . '<button type="button" id="' . $btnId . '"'
            . ' class="btn btn-outline-secondary csti-apikey-toggle"'
            . ' aria-controls="' . $inputId . '">'
            . '<span class="icon-eye" aria-hidden="true"></span>'
            . '<span class="csti-apikey-toggle-label">' . $reveal . '</span>'
            . '</button>'
            . '<button type="button" id="' . $clearId . '"'
            . ' class="btn btn-outline-secondary csti-apikey-clear"'
            . ' aria-controls="' . $inputId . '"'
            . ' title="' . $clearTitle . '">'
            . '<span class="icon-cancel" aria-hidden="true"></span>'
            . '<span class="csti-apikey-clear-label">' . $clearLabel . '</span>'
            . '</button>'
            . $helpButton
            . '</div>';

        return $css . $wrapped . $js;
    }
}
