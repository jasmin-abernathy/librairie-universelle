(() => {
    const request = document.getElementById('print-preparation-requested');
    const settings = document.getElementById('print-layout-settings');
    const gutterMode = document.getElementById('print-gutter-mode');
    const customGutter = document.getElementById('print-gutter-custom');
    const gutterInput = document.getElementById('print-gutter-mm');

    if (!request || !settings || !gutterMode || !customGutter || !gutterInput) {
        return;
    }

    const syncGutter = () => {
        const custom = request.checked && gutterMode.value === 'custom';
        customGutter.hidden = !custom;
        gutterInput.required = custom;
    };

    const syncPrintSettings = () => {
        settings.hidden = !request.checked;
        request.setAttribute('aria-expanded', request.checked ? 'true' : 'false');
        syncGutter();
    };

    request.addEventListener('change', syncPrintSettings);
    gutterMode.addEventListener('change', syncGutter);
    syncPrintSettings();
})();
