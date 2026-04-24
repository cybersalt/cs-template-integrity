/**
 * Cybersalt Template Integrity — dashboard behaviour
 *
 * Wires up the "Copy prompt" button. No-op if either the button or
 * the prompt block is missing (e.g. on a future view that reuses the
 * media file).
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        wireCopyButton();
        wireMarkReviewedModal();
    });

    function wireMarkReviewedModal() {
        var checkbox    = document.getElementById('csintegrity-mark-reviewed-confirm-check');
        var confirmBtn  = document.getElementById('csintegrity-mark-reviewed-confirm-btn');
        var modalEl     = document.getElementById('csintegrity-mark-reviewed-modal');

        if (!checkbox || !confirmBtn) {
            return;
        }

        checkbox.addEventListener('change', function () {
            confirmBtn.disabled = !checkbox.checked;
        });

        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                checkbox.checked = false;
                confirmBtn.disabled = true;
            });
        }
    }

    function wireCopyButton() {
        var btn    = document.getElementById('csintegrity-copy-btn');
        var prompt = document.getElementById('csintegrity-prompt');

        if (!btn || !prompt || !navigator.clipboard) {
            return;
        }

        var defaultLabel = btn.getAttribute('data-default-label') || 'Copy prompt';
        var copiedLabel  = btn.getAttribute('data-copied-label')  || 'Copied!';

        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(prompt.innerText).then(function () {
                btn.classList.remove('btn-info');
                btn.classList.add('btn-success');
                btn.innerHTML = '<span class="icon-checkmark" aria-hidden="true"></span> ' + copiedLabel;

                setTimeout(function () {
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-info');
                    btn.innerHTML = '<span class="icon-copy" aria-hidden="true"></span> ' + defaultLabel;
                }, 2000);
            }).catch(function () {
                btn.innerHTML = btn.innerHTML; // leave label alone on failure
            });
        });
    }
}());
