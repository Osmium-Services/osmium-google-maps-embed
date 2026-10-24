/**
 * Google Maps Embed editor: saves one map in one request.
 */
(function () {
    'use strict';

    const config = window.MapConfig || {};
    const form = document.getElementById('mapForm');
    if (!form) return;

    const result = document.getElementById('mapResult');
    const saveButton = document.getElementById('mapSaveButton');
    const nameInput = document.getElementById('mapName');
    const slugInput = document.getElementById('mapSlug');
    const urlInput = document.getElementById('mapUrl');
    const idInput = document.getElementById('mapId');

    function showResult(message, type) {
        result.className = 'alert alert-' + type;
        result.textContent = message;
        result.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    let slugEdited = slugInput.value !== '';
    slugInput.addEventListener('input', function () { slugEdited = true; });
    nameInput.addEventListener('input', function () {
        if (slugEdited) return;
        slugInput.value = nameInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        saveButton.disabled = true;

        try {
            const response = await fetch(config.actionUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: window.csrfToken,
                    action: 'save',
                    id: Number(idInput.value) || 0,
                    name: nameInput.value,
                    slug: slugInput.value,
                    embed_url: urlInput.value
                })
            });
            const data = await response.json();

            if (!data.success) {
                showResult(data.error || data.message || 'Could not save the map.', 'danger');
                saveButton.disabled = false;
                return;
            }

            // Go to the map by id rather than reloading: core strips the query string from this page's address
            window.location.href = config.editUrl + '?id=' + data.id;
        } catch (error) {
            console.error('Save error:', error);
            showResult('Could not save the map.', 'danger');
            saveButton.disabled = false;
        }
    });
})();
