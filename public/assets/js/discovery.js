'use strict';

(function () {
    const section = document.querySelector('[data-discovery-search]');
    if (!section) return;

    const query = section.dataset.query || '';
    const languages = section.dataset.languages || 'fr,en';
    const configuredLimit = Number(section.dataset.resultLimit || '0');
    let visibleLimit = Number.isFinite(configuredLimit) && configuredLimit > 0 ? configuredLimit : Infinity;
    let sources = [];

    try {
        sources = JSON.parse(section.dataset.sources || '[]');
    } catch (_) {
        return;
    }

    if (!query || !Array.isArray(sources) || sources.length === 0) return;

    const list = document.getElementById('external-result-list');
    const progress = document.getElementById('discovery-progress');
    if (!list || !progress) return;

    const normalize = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('fr')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();

    const normalizedQuery = normalize(query);
    const queryTokens = normalizedQuery.split(' ').filter(Boolean);
    const groups = new Map();
    let completed = 0;
    let failed = 0;
    let accessCount = 0;

    const loadingState = document.createElement('div');
    loadingState.className = 'discovery-state is-loading';
    loadingState.setAttribute('role', 'status');

    const loadingSpinner = document.createElement('span');
    loadingSpinner.className = 'discovery-spinner';
    loadingSpinner.setAttribute('aria-hidden', 'true');

    const loadingText = document.createElement('span');
    loadingText.textContent = 'Recherche dans les catalogues…';

    loadingState.append(loadingSpinner, loadingText);
    list.replaceChildren(loadingState);

    const scoreGroup = (group) => {
        const title = normalize(group.title);
        const authors = normalize(group.authors.join(' '));
        let score = 0;

        if (title === normalizedQuery) {
            score = 1000;
        } else if (normalizedQuery && title.startsWith(normalizedQuery)) {
            score = 850;
        } else if (normalizedQuery && title.includes(normalizedQuery)) {
            score = 750;
        } else if (queryTokens.length && queryTokens.every((token) => title.includes(token))) {
            score = 650;
        } else if (authors === normalizedQuery) {
            score = 500;
        } else if (normalizedQuery && authors.includes(normalizedQuery)) {
            score = 420;
        } else if (queryTokens.length && queryTokens.every((token) => authors.includes(token))) {
            score = 360;
        } else {
            score = 100;
        }

        return score + Math.min(group.sources.length, 20);
    };

    const addResult = (result) => {
        if (!result || !result.title) return;

        const key = normalize(result.title);
        if (!key) return;

        let group = groups.get(key);
        if (!group) {
            group = {
                key,
                title: String(result.title),
                authors: [],
                years: new Set(),
                languages: new Set(),
                sources: [],
            };
            groups.set(key, group);
        }

        if (Array.isArray(result.authors)) {
            result.authors.forEach((author) => {
                const value = String(author || '').trim();
                if (value && !group.authors.includes(value)) group.authors.push(value);
            });
        }

        if (result.year) group.years.add(String(result.year));
        if (result.language) group.languages.add(String(result.language).toUpperCase());

        const sourceKey = [
            result.source_name || '',
            result.access_url || result.source_url || '',
            result.download_url || '',
        ].join('|');

        if (!group.sources.some((source) => source.key === sourceKey)) {
            group.sources.push({ key: sourceKey, result });
            accessCount++;
        }
    };

    const makeSourceRow = (result) => {
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

        return row;
    };

    const makeCard = (group) => {
        const card = document.createElement('article');
        card.className = 'external-work-card';

        const heading = document.createElement('h3');
        heading.textContent = group.title;
        card.appendChild(heading);

        if (group.authors.length) {
            const authors = document.createElement('p');
            authors.className = 'external-authors';
            authors.textContent = group.authors.join(', ');
            card.appendChild(authors);
        }

        const facts = [];
        if (group.years.size) facts.push(Array.from(group.years).slice(0, 2).join(' / '));
        if (group.languages.size) facts.push(Array.from(group.languages).join(' · '));
        if (facts.length) {
            const meta = document.createElement('p');
            meta.className = 'external-meta';
            meta.textContent = facts.join(' · ');
            card.appendChild(meta);
        }

        const sourceContainer = document.createElement('div');
        sourceContainer.className = 'external-sources';
        group.sources.forEach(({ result }) => sourceContainer.appendChild(makeSourceRow(result)));
        card.appendChild(sourceContainer);

        const rights = group.sources
            .map(({ result }) => ({
                source: result.source_name || 'Source',
                note: String(result.rights_note || '').trim(),
            }))
            .filter((item) => item.note);

        if (rights.length) {
            const details = document.createElement('details');
            details.className = 'external-rights';
            const summary = document.createElement('summary');
            summary.textContent = 'Informations sur les droits';
            details.appendChild(summary);

            rights.slice(0, 3).forEach((item) => {
                const note = document.createElement('p');
                note.textContent = item.source + ' : ' + item.note;
                details.appendChild(note);
            });
            card.appendChild(details);
        }

        return card;
    };

    const renderResults = () => {
        const ordered = Array.from(groups.values())
            .sort((a, b) => {
                const scoreDifference = scoreGroup(b) - scoreGroup(a);
                if (scoreDifference !== 0) return scoreDifference;
                return a.title.localeCompare(b.title, 'fr', { sensitivity: 'base' });
            });

        list.replaceChildren();

        if (ordered.length === 0 && completed < sources.length) {
            list.appendChild(loadingState);
            return;
        }

        const visible = ordered.slice(0, visibleLimit);
        visible.forEach((group) => list.appendChild(makeCard(group)));

        if (ordered.length > visible.length) {
            const more = document.createElement('button');
            more.type = 'button';
            more.className = 'discovery-more';
            more.textContent = 'Afficher ' + (ordered.length - visible.length) + ' autre'
                + (ordered.length - visible.length > 1 ? 's résultats' : ' résultat');
            more.addEventListener('click', () => {
                visibleLimit = Infinity;
                renderResults();
            });
            list.appendChild(more);
        }

        if (ordered.length === 0 && completed === sources.length) {
            const empty = document.createElement('div');
            empty.className = 'discovery-state is-empty';
            const title = document.createElement('strong');
            title.textContent = 'Rien de pertinent trouvé dans les catalogues externes.';
            const text = document.createElement('span');
            text.textContent = 'Essayez un titre plus court, le nom de l’auteur ou de l’autrice, ou un ISBN.';
            empty.append(title, text);
            list.appendChild(empty);
        }
    };

    const updateProgress = () => {
        const total = sources.length;
        if (completed < total) {
            progress.textContent = completed + '/' + total + ' sources interrogées…';
            return;
        }

        const workCount = groups.size;
        progress.textContent = workCount === 0
            ? 'Aucun résultat supplémentaire'
            : workCount + ' œuvre' + (workCount > 1 ? 's' : '') + ' · '
                + accessCount + ' accès trouvé' + (accessCount > 1 ? 's' : '');

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
                headers: { Accept: 'application/json' },
                signal: controller.signal,
                credentials: 'same-origin',
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
            renderResults();
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
    runPool(sources, 4);
})();
