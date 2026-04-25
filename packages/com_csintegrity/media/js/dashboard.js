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
        wireCopyButton('csintegrity-copy-btn',     'csintegrity-prompt',     'btn-primary');
        wireCopyButton('csintegrity-fix-copy-btn', 'csintegrity-fix-prompt', 'btn-primary');
        wireMarkReviewedModal();
        wireFullscreenButton();
        wireGatedConfirmModal('csintegrity-restore-modal',
                              'csintegrity-restore-confirm-check',
                              'csintegrity-restore-confirm-btn');
        wireSyntaxHighlight();
    });

    function wireSyntaxHighlight() {
        var codeEl = document.getElementById('csintegrity-backup-contents-code');
        if (!codeEl) {
            return;
        }

        // Poll for window.hljs in case the script load order put us
        // ahead of highlight.js (defer attribute may or may not have
        // landed on the tag depending on how HTMLHelper rendered it).
        var attempts = 0;
        var maxAttempts = 50;  // 50 * 100ms = 5 seconds
        var poll = function () {
            if (typeof window.hljs !== 'undefined' && typeof window.hljs.highlightElement === 'function') {
                try {
                    window.hljs.highlightElement(codeEl);
                } catch (e) {
                    if (window.console && console.warn) {
                        console.warn('csintegrity highlight failed:', e);
                    }
                }
                return;
            }
            attempts++;
            if (attempts >= maxAttempts) {
                if (window.console && console.warn) {
                    console.warn('csintegrity: window.hljs never became available; backup contents will render unhighlighted.');
                }
                return;
            }
            setTimeout(poll, 100);
        };
        poll();
    }

    /**
     * Bootstrap modal that gates a destructive submit button on a checkbox.
     * Re-used by Mark-all-reviewed and Restore-backup modals.
     */
    function wireGatedConfirmModal(modalId, checkboxId, confirmBtnId) {
        var checkbox   = document.getElementById(checkboxId);
        var confirmBtn = document.getElementById(confirmBtnId);
        var modalEl    = document.getElementById(modalId);

        if (!checkbox || !confirmBtn) {
            return;
        }

        checkbox.addEventListener('change', function () {
            confirmBtn.disabled = !checkbox.checked;
        });

        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                checkbox.checked    = false;
                confirmBtn.disabled = true;
            });
        }
    }

    function wireFullscreenButton() {
        var btn = document.getElementById('csintegrity-fullscreen-btn');
        var pre = document.getElementById('csintegrity-report');

        if (!btn || !pre) {
            return;
        }

        if (!document.fullscreenEnabled) {
            // Browser doesn't support the Fullscreen API — hide the button
            // rather than leaving a button that does nothing.
            btn.style.display = 'none';
            return;
        }

        var enterLabel = btn.getAttribute('data-enter-label') || 'Fullscreen';
        var exitLabel  = btn.getAttribute('data-exit-label')  || 'Exit fullscreen';

        btn.addEventListener('click', function () {
            if (document.fullscreenElement === pre) {
                document.exitFullscreen();
            } else {
                pre.requestFullscreen().catch(function () { /* user dismissed */ });
            }
        });

        document.addEventListener('fullscreenchange', function () {
            if (document.fullscreenElement === pre) {
                pre.classList.add('csintegrity-report-fullscreen');
                btn.innerHTML = '<span class="icon-shrink" aria-hidden="true"></span> ' + exitLabel;
            } else {
                pre.classList.remove('csintegrity-report-fullscreen');
                btn.innerHTML = '<span class="icon-expand" aria-hidden="true"></span> ' + enterLabel;
            }
        });
    }

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

    function wireCopyButton(buttonId, promptId, restoreClass) {
        var btn    = document.getElementById(buttonId);
        var prompt = document.getElementById(promptId);

        if (!btn || !prompt || !navigator.clipboard) {
            return;
        }

        var defaultLabel = btn.getAttribute('data-default-label') || 'Copy prompt';
        var copiedLabel  = btn.getAttribute('data-copied-label')  || 'Copied!';

        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(prompt.innerText).then(function () {
                btn.classList.remove(restoreClass);
                btn.classList.add('btn-success');
                btn.innerHTML = '<span class="icon-checkmark" aria-hidden="true"></span> ' + copiedLabel;

                setTimeout(function () {
                    btn.classList.remove('btn-success');
                    btn.classList.add(restoreClass);
                    btn.innerHTML = '<span class="icon-copy" aria-hidden="true"></span> ' + defaultLabel;
                }, 2000);
            }).catch(function () {
                btn.innerHTML = btn.innerHTML;
            });
        });
    }
}());
