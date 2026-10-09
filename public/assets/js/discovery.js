'use strict';

(function () {
    const section = document.querySelector('[data-discovery-search]');
    if (!section) return;

    const query = section.dataset.query || '';
    const languages = section.dataset.languages || 'fr,en';
    let sources = [];
    try { sources = JSON.parse(section.dataset.sources || '[]'); } catch (_) { return; }
    if (!query || !Array.isArray(sources) || sources.length === 0) return;

    const list = document.getElementById('external-result-list');
    const progress = document.getElementById('discovery-progress');
    if (!list || !progress) return;

    list.textContent = '';

    const loadingState = document.createElement('div');
    loadingState.className = 'discovery-state is-loading';
    loadingState.setAttribute('role', 'status');

    const loadingSpinner = document.createElement('span');
    loadingSpinner.className = 'discovery-spinner';
    loadingSpinner.setAttribute('aria-hidden', 'true');

    const loadingText = document.createElement('span');
    loadingText.textContent = 'Recherche dans les catalogues…';

    loadingState.append(loadingSpinner, loadingText);
    list.appendChild(loadingState);

    const removeLoadingState = () => {
        if (loadingState.isConnected) loadingState.remove();
    };

    const cards = new Map();
    let completed = 0;
    let failed = 0;
    let linksCount = 0;

    const normalize = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('fr')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();

    const resultKey = (result) => {
        const firstAuthor = Array.isArray(result.authors) && result.authors.length ? result.authors[0] : '';
        return normalize(result.title) + '|' + normalize(firstAuthor);
    };

    const addSourceLink = (container, result) => {
        const row = document.createElement('div');
        row.className = 'external-source';

        const link = document.createElement('a');
        link.className = 'text-link';
        link.href = result.access_url || result.source_url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = result.source_name || 'Source';
        row.appendChild(link);

        if (result.format) {
            const format = document.createElement('span');
            format.className = 'source-format';
            format.textContent = result.format;
            row.appendChild(format);
        }

        if (result.download_url) {
            const download = document.createElement('a');
            download.className = 'source-download';
            download.href = result.download_url;
            download.target = '_blank';
            download.rel = 'noopener noreferrer';
            download.textContent = 'EPUB';
            row.appendChild(download);
        }

        if (result.rights_note) {
            const rights = document.createElement('small');
            rights.textContent = result.rights_note;
            row.appendChild(rights);
        }

        container.appendChild(row);
        linksCount++;
    };

    const addResult = (result) => {
        if (!result || !result.title) return;
        removeLoadingState();
        const key = resultKey(result);
        let sourceContainer = cards.get(key);

        if (!sourceContainer) {
            const card = document.createElement('article');
            card.className = 'external-work-card';

            const heading = document.createElement('h3');
            heading.textContent = result.title;
            card.appendChild(heading);

            if (Array.isArray(result.authors) && result.authors.length) {
                const authors = document.createElement('p');
                authors.className = 'external-authors';
                authors.textContent = result.authors.join(', ');
                card.appendChild(authors);
            }

            const facts = [];
            if (result.year) facts.push(String(result.year));
            if (result.language) facts.push(String(result.language).toUpperCase());
            if (facts.length) {
                const meta = document.createElement('p');
                meta.className = 'external-meta';
                meta.textContent = facts.join(' · ');
                card.appendChild(meta);
            }

            sourceContainer = document.createElement('div');
            sourceContainer.className = 'external-sources';
            card.appendChild(sourceContainer);
            list.appendChild(card);
            cards.set(key, sourceContainer);
        }

        addSourceLink(sourceContainer, result);
    };

    const updateProgress = () => {
        const total = sources.length;
        if (completed < total) {
            progress.textContent = completed + '/' + total + ' sources interrogées…';
            return;
        }
        progress.textContent = linksCount === 0
            ? 'Aucun résultat supplémentaire'
            : linksCount + ' piste' + (linksCount > 1 ? 's trouvées' : ' trouvée');
        if (failed > 0) {
            progress.textContent += ' · ' + failed + ' source' + (failed > 1 ? 's indisponibles' : ' indisponible');
        }
    };

    const loadSource = async (source) => {
        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), 9000);
        try {
            const url = '/api/discovery.php?source=' + encodeURIComponent(source)
                + '&q=' + encodeURIComponent(query)
                + '&lang=' + encodeURIComponent(languages);
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                signal: controller.signal,
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            if (payload.error) failed++;
            if (Array.isArray(payload.results)) payload.results.forEach(addResult);
        } catch (_) {
            failed++;
        } finally {
            window.clearTimeout(timer);
            completed++;
            updateProgress();
        }
    };

    const runPool = async (items, concurrency) => {
        let cursor = 0;
        const worker = async () => {
            while (cursor < items.length) {
                const index = cursor++;
                await loadSource(items[index]);
            }
        };
        const workers = [];
        for (let i = 0; i < Math.min(concurrency, items.length); i++) workers.push(worker());
        await Promise.allSettled(workers);
    };

    updateProgress();
    runPool(sources, 4).then(() => {
        removeLoadingState();
        if (cards.size !== 0) return;
        const empty = document.createElement('div');
        empty.className = 'discovery-state is-empty';
        const title = document.createElement('h3');
        title.textContent = 'Rien de pertinent trouvé dans les catalogues externes.';
        const text = document.createElement('p');
        text.textContent = 'Essaie un titre plus court, le nom de l’auteur ou de l’autrice, ou un ISBN.';
        empty.appendChild(title);
        empty.appendChild(text);
        list.appendChild(empty);
    });
})();
