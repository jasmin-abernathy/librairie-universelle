(() => {
    const template = document.getElementById('book-template');
    const target = document.getElementById('book-pages');
    const status = document.getElementById('render-status');
    const button = document.getElementById('print-button');

    if (!template || !target || !status || !button || !window.Paged) {
        if (status) status.textContent = 'Moteur de pagination indisponible.';
        return;
    }

    const previewer = new window.Paged.Previewer();
    previewer.preview(template.content.cloneNode(true), [], target)
        .then((flow) => {
            const pages = Number(flow.total || 0);
            const sheets = Math.ceil(pages / 2);
            status.textContent = pages + ' page' + (pages > 1 ? 's' : '') +
                ' · ' + sheets + ' feuille' + (sheets > 1 ? 's' : '') + ' recto-verso';
            button.disabled = false;
        })
        .catch(() => {
            status.textContent = 'La pagination a échoué. Revenez aux réglages et vérifiez l’EPUB.';
        });

    button.addEventListener('click', () => window.print());
})();
