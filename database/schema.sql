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

CREATE TABLE IF NOT EXISTS edition_contributors (
    edition_id INTEGER NOT NULL REFERENCES editions(id) ON DELETE CASCADE,
    contributor_id INTEGER NOT NULL REFERENCES contributors(id) ON DELETE CASCADE,
    role TEXT NOT NULL DEFAULT 'contributor',
    PRIMARY KEY (edition_id, contributor_id, role)
);

CREATE INDEX IF NOT EXISTS idx_edition_contributors_edition ON edition_contributors(edition_id);

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

CREATE TABLE IF NOT EXISTS author_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'needs_changes', 'approved', 'rejected', 'published')),
    author_name TEXT NOT NULL,
    pseudonym TEXT,
    email TEXT NOT NULL,
    title TEXT NOT NULL,
    subtitle TEXT,
    language TEXT NOT NULL DEFAULT 'fr',
    description TEXT NOT NULL,
    isbn_ebook TEXT,
    isbn_paper TEXT,
    ebook_price_cents INTEGER,
    paper_price_cents INTEGER,
    ebook_distribution TEXT NOT NULL DEFAULT 'paid'
        CHECK (ebook_distribution IN ('free', 'paid')),
    rights_confirmed INTEGER NOT NULL CHECK (rights_confirmed IN (0, 1)),
    quality_confirmed INTEGER NOT NULL CHECK (quality_confirmed IN (0, 1)),
    editorial_note TEXT,
    published_work_id INTEGER REFERENCES works(id) ON DELETE SET NULL,
    submitted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_author_submissions_status ON author_submissions(status);
CREATE INDEX IF NOT EXISTS idx_author_submissions_email ON author_submissions(email COLLATE NOCASE);

CREATE TABLE IF NOT EXISTS submission_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    submission_id INTEGER NOT NULL REFERENCES author_submissions(id) ON DELETE CASCADE,
    kind TEXT NOT NULL CHECK (kind IN ('ebook', 'cover', 'print_pdf')),
    original_name TEXT NOT NULL,
    stored_name TEXT NOT NULL UNIQUE,
    mime_type TEXT NOT NULL,
    size_bytes INTEGER NOT NULL,
    sha256 TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_submission_files_submission ON submission_files(submission_id);

CREATE TABLE IF NOT EXISTS submission_print_settings (
    submission_id INTEGER PRIMARY KEY REFERENCES author_submissions(id) ON DELETE CASCADE,
    trim_size TEXT NOT NULL DEFAULT '140x210'
        CHECK (trim_size IN ('a5', '140x210', '135x215', '152x229')),
    binding TEXT NOT NULL DEFAULT 'paperback'
        CHECK (binding IN ('paperback', 'hardcover')),
    toc_enabled INTEGER NOT NULL DEFAULT 1 CHECK (toc_enabled IN (0, 1)),
    chapter_start TEXT NOT NULL DEFAULT 'right'
        CHECK (chapter_start IN ('right', 'next')),
    page_number_position TEXT NOT NULL DEFAULT 'outside'
        CHECK (page_number_position IN ('outside', 'center', 'none')),
    hide_chapter_openers INTEGER NOT NULL DEFAULT 1 CHECK (hide_chapter_openers IN (0, 1)),
    front_matter_numbering TEXT NOT NULL DEFAULT 'roman'
        CHECK (front_matter_numbering IN ('roman', 'hidden', 'arabic')),
    bleed_mm INTEGER NOT NULL DEFAULT 0 CHECK (bleed_mm IN (0, 3)),
    gutter_mode TEXT NOT NULL DEFAULT 'auto'
        CHECK (gutter_mode IN ('auto', 'custom')),
    gutter_mm REAL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (
        (gutter_mode = 'auto' AND gutter_mm IS NULL)
        OR
        (gutter_mode = 'custom' AND gutter_mm BETWEEN 5 AND 40)
    )
);


CREATE TABLE IF NOT EXISTS feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_url TEXT,
    kind TEXT NOT NULL DEFAULT 'general'
        CHECK (kind IN ('general', 'error', 'accessibility', 'catalog', 'self_publishing')),
    message TEXT NOT NULL,
    email TEXT,
    status TEXT NOT NULL DEFAULT 'new'
        CHECK (status IN ('new', 'reviewed', 'closed')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_feedback_status ON feedback(status);

CREATE TABLE IF NOT EXISTS sync_runs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    source_name TEXT NOT NULL,
    started_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at TEXT,
    status TEXT NOT NULL DEFAULT 'running'
        CHECK (status IN ('running', 'success', 'partial', 'failed')),
    imported_count INTEGER NOT NULL DEFAULT 0,
    skipped_count INTEGER NOT NULL DEFAULT 0,
    conflict_count INTEGER NOT NULL DEFAULT 0,
    error_message TEXT
);

CREATE INDEX IF NOT EXISTS idx_sync_runs_source ON sync_runs(source_name, started_at DESC);

-- Annuaire local des librairies indépendantes / réseaux indépendants de Metz et de Moselle.
CREATE TABLE IF NOT EXISTS bookstores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    directory_key TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    group_name TEXT,
    address TEXT NOT NULL,
    postal_code TEXT NOT NULL,
    city TEXT NOT NULL,
    department_code TEXT NOT NULL DEFAULT '57',
    department_name TEXT NOT NULL DEFAULT 'Moselle',
    country TEXT NOT NULL DEFAULT 'France',
    category TEXT NOT NULL,
    independence_status TEXT NOT NULL,
    specialization TEXT,
    phone TEXT,
    website_url TEXT,
    website_status TEXT,
    ordering_status TEXT NOT NULL DEFAULT 'à vérifier',
    ordering_notes TEXT,
    directory_status TEXT NOT NULL DEFAULT 'review'
        CHECK (directory_status IN ('listed', 'review', 'hidden')),
    curation_note TEXT,
    source_label TEXT,
    source_url TEXT,
    last_checked TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_bookstores_directory_status
    ON bookstores(directory_status, city, name);
CREATE INDEX IF NOT EXISTS idx_bookstores_postal_code
    ON bookstores(postal_code);

