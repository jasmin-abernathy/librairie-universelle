# CI / GitHub Actions budget policy

These rules apply to automated work in this repository.

- Work in batches: edit many, validate once, build once.
- Do not trigger a heavy build after every small commit.
- README, documentation and this policy should not trigger CI.
- APK/ZIP/package artifacts should be built manually or on deliberate release tags unless continuous packaging is explicitly required.
- Keep lightweight syntax, security and deployment-safety checks automatic for relevant code paths.
- Use path filters, timeouts, caches and `concurrency` with `cancel-in-progress: true` for CI when safe.
- Do not cancel a production deployment mid-run unless the deployment is explicitly designed to be safely interruptible.
- Group related automated edits into as few pushes as practical.
- Prefer self-hosted runners for heavy builds when one is available.

## Public catalog and navigation

- Do not seed displayable ebook offers with historical samples or fictitious sellers in public SQLite databases. Fixtures must stay inside isolated CI tests. Avoid displaying suggested ebook offers before a visitor submits a search.
- Never render a mock offer, fictitious partner, or legacy CI book in the public search or detail pages, even if an old server database still contains it.
- Every outbound HTTP(S) link (PHP markup or dynamically created JavaScript anchor) must open in a new tab: `target="_blank"` and `rel="noopener noreferrer"`. Internal paths remain in the current tab; downloads using `blob:` and `mailto:`/`tel:` are not outbound website links.
- Keep the PHP regression check for external links, actual catalog results, and absence of preloaded demo offers in CI.
