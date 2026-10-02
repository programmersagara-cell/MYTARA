/**
 * IT Asset Management System - Asset Create Form Script
 *
 * Keeps the prefilled `asset_tag` in the org standard format
 * (ORG-YY-DEPT-NNNN) in sync with the selected department and
 * purchase date by asking GET /assets/next-tag for a fresh tag.
 * The input always stays editable so admins can key an existing
 * vendor tag manually.
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initAssetTagGenerator();
    });

    // ─── Asset Tag Auto-Generation ───
    function initAssetTagGenerator() {
        const tagInput = document.getElementById('assetTag');
        const deptSelect = document.getElementById('departmentId');
        const purchaseDate = document.getElementById('purchaseDate');
        const generateBtn = document.getElementById('generateTagBtn');

        // Form without the generator markup (e.g. rendered read-only) — bail out.
        if (!tagInput) return;

        let controller = null;

        function refreshTag() {
            const params = new URLSearchParams();
            if (deptSelect && deptSelect.value) {
                params.set('department_id', deptSelect.value);
            }
            // Acquisition year = year part of purchase_date; server falls back
            // to the current year when it is empty.
            if (purchaseDate && /^\d{4}-\d{2}-\d{2}$/.test(purchaseDate.value)) {
                params.set('year', purchaseDate.value.substring(0, 4));
            }

            const url = (window.BASE_PATH || '') + '/assets/next-tag?' + params.toString();

            // Abandon the previous request so fast consecutive changes cannot
            // arrive out of order and overwrite a newer tag with an older one.
            if (controller) {
                controller.abort();
            }
            controller = new AbortController();

            fetch(url, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal
            })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data && data.success && data.tag) {
                        tagInput.value = data.tag;
                    }
                })
                .catch(function(err) {
                    if (err && err.name === 'AbortError') return;
                    console.warn('Could not refresh asset tag:', err);
                });
        }

        if (deptSelect) {
            deptSelect.addEventListener('change', refreshTag);
        }
        if (purchaseDate) {
            purchaseDate.addEventListener('change', refreshTag);
        }
        if (generateBtn) {
            generateBtn.addEventListener('click', refreshTag);
        }
    }
})();
