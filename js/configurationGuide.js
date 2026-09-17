/**
 * @file js/configurationGuide.js
 *
 * Copyright (c) 2025-2026 Lepidus Tecnologia
 * Copyright (c) 2025-2026 Fundação Getulio Vargas
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Panel navigation for the FGV OpenRank configuration guide.
 */
(function () {
    'use strict';

    var guides = document.querySelectorAll('[data-rankingplugin-guide]:not([data-guide-initialized])');

    Array.prototype.forEach.call(guides, function (guide) {
        guide.setAttribute('data-guide-initialized', 'true');

        var panels = Array.prototype.slice.call(guide.querySelectorAll('[data-guide-panel]'));

        guide.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-guide-target]');
            if (!trigger) {
                return;
            }

            var target = Number(trigger.getAttribute('data-guide-target'));
            if (!Number.isInteger(target) || !panels[target]) {
                return;
            }

            panels.forEach(function (panel, index) {
                panel.hidden = index !== target;
            });

            var heading = panels[target].querySelector('h2');
            if (heading) {
                heading.focus();
            }
        });
    });
}());
