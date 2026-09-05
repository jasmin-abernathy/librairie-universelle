PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS works (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    subtitle TEXT,
    original_title TEXT,
    first_publication_year INTEGER,
    language TEXT,
    public_domain_status TEXT NOT NULL DEFAULT 'unknown'
        CHECK (public_domain_status IN ('unknown', 'yes', 'no', 'review')),
    public_domain_note TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_works_title ON works(title COLLATE NOCASE);

CREATE TABLE IF NOT EXISTS contributors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    birth_year INTEGER,
    death_year INTEGER,
    authority_uri TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_contributors_name ON contributors(name COLLATE NOCASE);

CREATE TABLE IF NOT EXISTS work_contributors (
    work_id INTEGER NOT NULL REFERENCES works(id) ON DELETE CASCADE,
    contributor_id INTEGER NOT NULL REFERENCES contributors(id) ON DELETE CASCADE,
    role TEXT NOT NULL DEFAULT 'author',
    PRIMARY KEY (work_id, contributor_id, role)
);

CREATE TABLE IF NOT EXISTS editions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    work_id INTEGER NOT NULL REFERENCES works(id) ON DELETE CASCADE,
    isbn13 TEXT,
    title TEXT,
    publisher TEXT,
    publication_date TEXT,
    language TEXT,
    medium TEXT NOT NULL DEFAULT 'paper'
        CHECK (medium IN ('paper', 'ebook', 'audio', 'other')),
    file_format TEXT,
    drm_type TEXT,
    accessibility_note TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_editions_isbn13
    ON editions(isbn13)
    WHERE isbn13 IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_editions_work ON editions(work_id);

CREATE TABLE IF NOT EXISTS sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    source_type TEXT NOT NULL
        CHECK (source_type IN ('bookseller', 'used_bookseller', 'publisher', 'library', 'public_domain', 'distributor', 'other')),
    base_url TEXT,
    terms_url TEXT,
    data_license TEXT,
    active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS offers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    source_id INTEGER NOT NULL REFERENCES sources(id) ON DELETE CASCADE,
    work_id INTEGER REFERENCES works(id) ON DELETE CASCADE,
    edition_id INTEGER REFERENCES editions(id) ON DELETE CASCADE,
    offer_type TEXT NOT NULL
        CHECK (offer_type IN ('new', 'used', 'ebook', 'audio', 'free_download', 'borrow', 'read_online', 'other')),
    price_cents INTEGER,
    currency TEXT DEFAULT 'EUR',
    availability TEXT,
    url TEXT NOT NULL,
    file_format TEXT,
    drm_type TEXT,
    location_label TEXT,
    latitude REAL,
    longitude REAL,
    checked_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (work_id IS NOT NULL OR edition_id IS NOT NULL)
);

CREATE INDEX IF NOT EXISTS idx_offers_work ON offers(work_id);
CREATE INDEX IF NOT EXISTS idx_offers_edition ON offers(edition_id);
CREATE INDEX IF NOT EXISTS idx_offers_type ON offers(offer_type);

CREATE TABLE IF NOT EXISTS source_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    source_id INTEGER NOT NULL REFERENCES sources(id) ON DELETE CASCADE,
    external_id TEXT NOT NULL,
    entity_type TEXT NOT NULL CHECK (entity_type IN ('work', 'edition', 'contributor', 'offer')),
    local_id INTEGER,
    source_url TEXT,
    imported_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (source_id, external_id, entity_type)
);
