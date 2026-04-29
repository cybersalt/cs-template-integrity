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

use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;

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

        $inputId = htmlspecialchars((string) $this->id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $btnId   = $inputId . '-toggle';

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
.csti-apikey-input:focus,
.csti-apikey-input.is-revealed {
    filter: none;
}
.csti-apikey-toggle .csti-apikey-toggle-label {
    margin-left: 0.35rem;
}
</style>
CSS;

        $js = <<<JS
<script>
(function () {
    var btn = document.getElementById('{$btnId}');
    var input = document.getElementById('{$inputId}');
    if (!btn || !input) return;
    var labelEl = btn.querySelector('.csti-apikey-toggle-label');
    btn.addEventListener('click', function () {
        var revealed = input.classList.toggle('is-revealed');
        if (labelEl) {
            labelEl.textContent = revealed ? '{$hide}' : '{$reveal}';
        }
        if (revealed) {
            input.focus();
        }
    });
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
            . '</div>';

        return $css . $wrapped . $js;
    }
}
