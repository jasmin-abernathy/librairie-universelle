'use strict';

(() => {
    const root = document.querySelector('[data-bookstore-locator]');
    if (!root) return;

    const input = root.querySelector('[data-bookstore-address]');
    const radius = root.querySelector('[data-bookstore-radius]');
    const submit = root.querySelector('[data-bookstore-submit]');
    const suggestionsBox = root.querySelector('[data-bookstore-suggestions]');
    const status = root.querySelector('[data-bookstore-status]');
    const resultsBox = document.querySelector('[data-bookstore-results]');

    if (!input || !radius || !submit || !suggestionsBox || !status || !resultsBox) return;

    const completionEndpoint = 'https://data.geopf.fr/geocodage/completion/';
    const cacheKey = 'librairie:bookstore-geocodes:v1';
    let selectedLocation = null;
    let suggestionTimer = null;
    let suggestions = [];
    let activeSuggestion = -1;

    const wait = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

    function readCache() {
        try {
            const parsed = JSON.parse(window.localStorage.getItem(cacheKey) || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch {
            return {};
        }
    }

    function writeCache(cache) {
        try {
            window.localStorage.setItem(cacheKey, JSON.stringify(cache));
        } catch {
            // Le repérage fonctionne aussi sans stockage local.
        }
    }

    async function complete(text, options = {}) {
        const url = new URL(completionEndpoint);
        url.searchParams.set('text', text);
        url.searchParams.set('type', options.type || 'StreetAddress');
        url.searchParams.set('maximumResponses', String(options.maximumResponses || 6));
        if (options.terr) url.searchParams.set('terr', options.terr);
        if (options.depcode) url.searchParams.set('depcode', options.depcode);

        const response = await fetch(url.toString(), {
            method: 'GET',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) throw new Error('geocoding_unavailable');
        const payload = await response.json();
        return Array.isArray(payload.results) ? payload.results : [];
    }

    function asLocation(item) {
        const longitude = Number(item && item.x);
        const latitude = Number(item && item.y);
        if (!Number.isFinite(longitude) || !Number.isFinite(latitude)) return null;
        return {
            longitude,
            latitude,
            label: String(item.fulltext || item.street || '').trim(),
        };
    }

    function clearSuggestions() {
        suggestions = [];
        activeSuggestion = -1;
        suggestionsBox.replaceChildren();
        suggestionsBox.hidden = true;
        input.setAttribute('aria-expanded', 'false');
    }

    function chooseSuggestion(index) {
        const item = suggestions[index];
        const location = asLocation(item);
        if (!location) return;
        selectedLocation = location;
        input.value = location.label || input.value;
        clearSuggestions();
        status.textContent = '';
        input.focus();
    }

    function renderSuggestions(items) {
        suggestions = items;
        activeSuggestion = -1;
        suggestionsBox.replaceChildren();

        if (items.length === 0) {
            clearSuggestions();
            return;
        }

        items.forEach((item, index) => {
            const location = asLocation(item);
            if (!location) return;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'address-suggestion';
            button.setAttribute('role', 'option');
            button.setAttribute('aria-selected', 'false');
            button.textContent = location.label;
            button.addEventListener('click', () => chooseSuggestion(index));
            suggestionsBox.appendChild(button);
        });

        suggestionsBox.hidden = suggestionsBox.children.length === 0;
        input.setAttribute('aria-expanded', suggestionsBox.hidden ? 'false' : 'true');
    }

    function updateActiveSuggestion(nextIndex) {
        const buttons = Array.from(suggestionsBox.querySelectorAll('[role="option"]'));
        if (buttons.length === 0) return;
        activeSuggestion = (nextIndex + buttons.length) % buttons.length;
        buttons.forEach((button, index) => {
            button.setAttribute('aria-selected', index === activeSuggestion ? 'true' : 'false');
        });
        buttons[activeSuggestion].scrollIntoView({ block: 'nearest' });
    }

    input.addEventListener('input', () => {
        selectedLocation = null;
        window.clearTimeout(suggestionTimer);
        const value = input.value.trim();
        if (value.length < 3) {
            clearSuggestions();
            return;
        }

        suggestionTimer = window.setTimeout(async () => {
            try {
                const items = await complete(value, {
                    terr: 'METROPOLE',
                    type: 'StreetAddress',
                    maximumResponses: 6,
                });
                renderSuggestions(items);
            } catch {
                clearSuggestions();
            }
        }, 350);
    });

    input.addEventListener('keydown', (event) => {
        if (suggestionsBox.hidden) return;
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            updateActiveSuggestion(activeSuggestion + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            updateActiveSuggestion(activeSuggestion - 1);
        } else if (event.key === 'Enter' && activeSuggestion >= 0) {
            event.preventDefault();
            chooseSuggestion(activeSuggestion);
        } else if (event.key === 'Escape') {
            clearSuggestions();
        }
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) clearSuggestions();
    });

    async function resolveTypedAddress() {
        if (selectedLocation) return selectedLocation;
        const value = input.value.trim();
        if (value.length < 3) return null;
        const items = await complete(value, {
            terr: 'METROPOLE',
            type: 'StreetAddress',
            maximumResponses: 1,
        });
        const location = asLocation(items[0]);
        if (location) {
            selectedLocation = location;
            input.value = location.label || value;
        }
        return location;
    }

    async function loadBookstores() {
        const response = await fetch('/api/bookstores.php', {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) throw new Error('bookstores_unavailable');
        const payload = await response.json();
        return Array.isArray(payload.bookstores) ? payload.bookstores : [];
    }

    async function geocodeBookstores(bookstores) {
        const cache = readCache();
        const located = [];
        const pending = [];

        bookstores.forEach((bookstore) => {
            const versionedKey = bookstore.directory_key + ':' + bookstore.last_checked;
            const cached = cache[versionedKey];
            if (cached && Number.isFinite(Number(cached.longitude)) && Number.isFinite(Number(cached.latitude))) {
                located.push({
                    bookstore,
                    longitude: Number(cached.longitude),
                    latitude: Number(cached.latitude),
                });
            } else {
                pending.push({ bookstore, versionedKey });
            }
        });

        let completed = located.length;
        for (let offset = 0; offset < pending.length; offset += 5) {
            const batch = pending.slice(offset, offset + 5);
            const batchResults = await Promise.all(batch.map(async ({ bookstore, versionedKey }) => {
                try {
                    const text = bookstore.address + ', ' + bookstore.postal_code + ' ' + bookstore.city;
                    const items = await complete(text, {
                        depcode: '57',
                        type: 'StreetAddress',
                        maximumResponses: 1,
                    });
                    const location = asLocation(items[0]);
                    if (!location) return null;
                    cache[versionedKey] = {
                        longitude: location.longitude,
                        latitude: location.latitude,
                    };
                    return { bookstore, ...location };
                } catch {
                    return null;
                }
            }));

            batchResults.forEach((entry) => {
                if (entry) located.push(entry);
            });
            completed += batch.length;
            status.textContent = 'Repérage des librairies : ' + completed + '/' + bookstores.length + '…';
            writeCache(cache);

            if (offset + 5 < pending.length) await wait(700);
        }

        return located;
    }

    function distanceKm(from, to) {
        const earthRadius = 6371;
        const toRadians = (value) => value * Math.PI / 180;
        const dLat = toRadians(to.latitude - from.latitude);
        const dLon = toRadians(to.longitude - from.longitude);
        const lat1 = toRadians(from.latitude);
        const lat2 = toRadians(to.latitude);
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) ** 2;
        return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function safeHttpUrl(value) {
        if (!value) return null;
        try {
            const url = new URL(value);
            return url.protocol === 'http:' || url.protocol === 'https:' ? url.toString() : null;
        } catch {
            return null;
        }
    }

    function addLink(container, label, href, className = 'text-link') {
        const safeUrl = safeHttpUrl(href);
        if (!safeUrl) return;
        const link = document.createElement('a');
        link.href = safeUrl;
        link.target = '_blank';
        link.rel = 'noreferrer';
        link.className = className;
        link.textContent = label;
        container.appendChild(link);
    }

    function renderBookstores(entries, searchRadius) {
        resultsBox.replaceChildren();

        if (entries.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'empty-state';
            const title = document.createElement('h3');
            title.textContent = 'Aucune librairie recensée dans ce rayon.';
            const text = document.createElement('p');
            text.textContent = searchRadius < 50
                ? 'Essayez un rayon plus large. Notre annuaire couvre pour l’instant Metz et la Moselle.'
                : 'Notre annuaire est encore en construction et couvre pour l’instant Metz et la Moselle.';
            empty.append(title, text);
            resultsBox.appendChild(empty);
            return;
        }

        const heading = document.createElement('div');
        heading.className = 'bookstore-results-heading';
        const title = document.createElement('h2');
        title.textContent = entries.length + ' librairie' + (entries.length > 1 ? 's' : '') + ' dans un rayon de ' + searchRadius + ' km';
        heading.appendChild(title);
        resultsBox.appendChild(heading);

        const grid = document.createElement('div');
        grid.className = 'bookstore-grid';

        entries.forEach(({ bookstore, distance }) => {
            const card = document.createElement('article');
            card.className = 'bookstore-card';

            const top = document.createElement('div');
            top.className = 'bookstore-card-top';
            const name = document.createElement('h3');
            name.textContent = bookstore.name;
            const distanceBadge = document.createElement('span');
            distanceBadge.className = 'distance-badge';
            distanceBadge.textContent = distance < 10 ? distance.toFixed(1) + ' km' : Math.round(distance) + ' km';
            top.append(name, distanceBadge);

            const address = document.createElement('p');
            address.className = 'bookstore-address';
            address.textContent = bookstore.address + ', ' + bookstore.postal_code + ' ' + bookstore.city;

            const details = document.createElement('p');
            details.className = 'bookstore-details';
            details.textContent = [bookstore.independence_status, bookstore.specialization]
                .filter(Boolean)
                .join(' · ');

            card.append(top, address, details);

            if (String(bookstore.ordering_status || '').toLowerCase().startsWith('oui')) {
                const ordering = document.createElement('p');
                ordering.className = 'availability-badge';
                ordering.textContent = 'Commande en ligne / retrait signalé';
                card.appendChild(ordering);
            }

            const actions = document.createElement('div');
            actions.className = 'bookstore-actions';
            addLink(actions, 'Voir le site', bookstore.website_url, 'button-link secondary');
            if (bookstore.phone) {
                const phone = document.createElement('a');
                phone.href = 'tel:' + String(bookstore.phone).replace(/\s+/g, '');
                phone.className = 'text-link';
                phone.textContent = 'Appeler';
                actions.appendChild(phone);
            }
            addLink(actions, 'Source', bookstore.source_url);
            if (actions.children.length > 0) card.appendChild(actions);

            grid.appendChild(card);
        });

        resultsBox.appendChild(grid);
    }

    root.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearSuggestions();
        submit.disabled = true;
        resultsBox.replaceChildren();
        status.textContent = 'Recherche de l’adresse…';

        try {
            const origin = await resolveTypedAddress();
            if (!origin) {
                status.textContent = 'Adresse introuvable. Choisissez une suggestion ou précisez davantage l’adresse.';
                return;
            }

            status.textContent = 'Chargement de l’annuaire…';
            const bookstores = await loadBookstores();
            const located = await geocodeBookstores(bookstores);
            const searchRadius = Number(radius.value) || 10;

            const matches = located
                .map((entry) => ({
                    bookstore: entry.bookstore,
                    distance: distanceKm(origin, entry),
                }))
                .filter((entry) => entry.distance <= searchRadius)
                .sort((a, b) => a.distance - b.distance);

            renderBookstores(matches, searchRadius);
            status.textContent = located.length < bookstores.length
                ? 'Résultats affichés. Certaines adresses de librairies n’ont pas pu être localisées.'
                : 'Résultats triés par distance.';
        } catch {
            status.textContent = 'Le service de localisation est temporairement indisponible. Réessayez dans quelques instants.';
        } finally {
            submit.disabled = false;
        }
    });
})();
