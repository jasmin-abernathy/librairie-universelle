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


-- Fichiers de couverture pour l'édition papier : séparés de l'ebook
-- et des fichiers généraux existants, sans migration risquée de CHECK.
CREATE TABLE IF NOT EXISTS submission_paper_covers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    submission_id INTEGER NOT NULL REFERENCES author_submissions(id) ON DELETE CASCADE,
    side TEXT NOT NULL CHECK (side IN ('front', 'back')),
    original_name TEXT NOT NULL,
    stored_name TEXT NOT NULL UNIQUE,
    mime_type TEXT NOT NULL,
    size_bytes INTEGER NOT NULL,
    sha256 TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(submission_id, side)
);
CREATE INDEX IF NOT EXISTS idx_submission_paper_covers_submission
    ON submission_paper_covers(submission_id);

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


-- Addition non destructive : conserve les anciens réglages Corep A5 et les anciens dépôts.
-- Les formats Bookelis sont mémorisés séparément pour ne pas reconstruire la table legacy SQLite.
CREATE TABLE IF NOT EXISTS submission_print_provider_options (
    submission_id INTEGER PRIMARY KEY REFERENCES author_submissions(id) ON DELETE CASCADE,
    provider TEXT NOT NULL CHECK (provider IN ('corep_metz', 'bookelis')),
    trim_size TEXT NOT NULL,
    settings_json TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
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
 
-- Fiches proposées par les auteurs : uniquement métadonnées, aucun manuscrit.
-- Circuit indépendant des anciennes soumissions de fichiers (fermées au public).
CREATE TABLE IF NOT EXISTS author_book_listings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','published','rejected','hidden')),
    title TEXT NOT NULL,
    subtitle TEXT,
    author_name TEXT NOT NULL,
    description TEXT NOT NULL,
    book_format TEXT NOT NULL CHECK(book_format IN ('paper','ebook','both')),
    isbn TEXT,
    price_cents INTEGER NOT NULL CHECK(price_cents BETWEEN 0 AND 99999999),
    ebook_price_cents INTEGER CHECK(ebook_price_cents IS NULL OR ebook_price_cents BETWEEN 0 AND 99999999),
    seller_type TEXT NOT NULL CHECK(seller_type IN ('professional','individual')),
    siret TEXT,
    registered_company_name TEXT,
    siret_verified_at TEXT,
    private_email TEXT NOT NULL,
    seller_key TEXT,
    seller_legal_name TEXT,
    seller_postal_address TEXT,
    seller_phone TEXT,
    public_email TEXT,
    seller_url TEXT,
    rights_confirmed INTEGER NOT NULL CHECK(rights_confirmed=1),
    public_contact_confirmed INTEGER NOT NULL CHECK(public_contact_confirmed=1),
    seller_responsibility_confirmed INTEGER NOT NULL CHECK(seller_responsibility_confirmed=1),
    review_note TEXT,
    submitted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TEXT,
    CHECK(public_email IS NOT NULL OR seller_url IS NOT NULL)
);
CREATE INDEX IF NOT EXISTS idx_author_book_listings_status ON author_book_listings(status, submitted_at);
CREATE INDEX IF NOT EXISTS idx_author_book_listings_title ON author_book_listings(title COLLATE NOCASE);
CREATE INDEX IF NOT EXISTS idx_author_book_listings_private_email ON author_book_listings(private_email, submitted_at);

-- Une convention signée par vendeur. Aucune fiche de vente directe n'est
-- publiable sans entrée signée correspondante (papier, EPUB et gratuit).
CREATE TABLE IF NOT EXISTS seller_agreements (
    seller_key TEXT PRIMARY KEY,
    signed_at TEXT NOT NULL,
    signature_reference TEXT NOT NULL,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- Enquête indépendante sur les lecteurs et librairies (2026).
-- Identifiants de reprise hachés ; aucune donnée de contact nominative.
CREATE TABLE IF NOT EXISTS reader_survey_sessions (
    id TEXT PRIMARY KEY,
    token_hash TEXT NOT NULL UNIQUE,
    source TEXT NOT NULL DEFAULT 'direct',
    answers_json TEXT NOT NULL DEFAULT '{}',
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','completed')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_reader_survey_status ON reader_survey_sessions(status, completed_at);
CREATE INDEX IF NOT EXISTS idx_reader_survey_updated ON reader_survey_sessions(updated_at);

CREATE TABLE IF NOT EXISTS bookseller_survey_sessions (
    id TEXT PRIMARY KEY,
    token_hash TEXT NOT NULL UNIQUE,
    source TEXT NOT NULL DEFAULT 'direct',
    answers_json TEXT NOT NULL DEFAULT '{}',
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','completed')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_bookseller_survey_status ON bookseller_survey_sessions(status, completed_at);
CREATE INDEX IF NOT EXISTS idx_bookseller_survey_updated ON bookseller_survey_sessions(updated_at);

-- Consentement déclaré et minimisé ; la suppression de la session supprime cette trace.
CREATE TABLE IF NOT EXISTS reader_survey_consents (
  session_id TEXT PRIMARY KEY REFERENCES reader_survey_sessions(id) ON DELETE CASCADE,
  guardian_agreed INTEGER NOT NULL DEFAULT 0 CHECK(guardian_agreed IN (0,1)),
  participant_agreed INTEGER NOT NULL DEFAULT 1 CHECK(participant_agreed=1),
  consent_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- Une édition ou une fiche auteur peut présenter plusieurs distributeurs.
-- Aucune association n'est créée sans lien exact, observé et vérifié.
CREATE TABLE IF NOT EXISTS distribution_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    edition_id INTEGER REFERENCES editions(id) ON DELETE CASCADE,
    author_book_id INTEGER REFERENCES author_book_listings(id) ON DELETE CASCADE,
    platform TEXT NOT NULL,
    channel TEXT NOT NULL CHECK(channel IN ('independent_portal','bookseller','publisher','author','other')),
    medium TEXT NOT NULL CHECK(medium IN ('paper','ebook','audio','other')),
    url TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'checked' CHECK(status IN ('checked','hidden')),
    checked_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK ((edition_id IS NOT NULL AND author_book_id IS NULL)
        OR (edition_id IS NULL AND author_book_id IS NOT NULL))
);
CREATE UNIQUE INDEX IF NOT EXISTS ux_distribution_edition_url
    ON distribution_links(edition_id,url) WHERE edition_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS ux_distribution_author_url
    ON distribution_links(author_book_id,url) WHERE author_book_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_distribution_edition_status
    ON distribution_links(edition_id,status);
CREATE INDEX IF NOT EXISTS idx_distribution_author_status
    ON distribution_links(author_book_id,status);


-- Entretiens éditoriaux privés. La base et les réponses sont hors dépôt Git.
-- Le lien invité est calculé depuis le secret d'administration sans stocker de jeton brut.
CREATE TABLE IF NOT EXISTS editorial_interviews (
    id TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    recipient_name TEXT NOT NULL,
    recipient_email TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','invited','in_progress','submitted','closed')),
    token_version INTEGER NOT NULL DEFAULT 1,
    cursor INTEGER NOT NULL DEFAULT 0,
    answers_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at TEXT,
    consent_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_editorial_interviews_status ON editorial_interviews(status,updated_at);
