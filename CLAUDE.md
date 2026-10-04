# virage-ai-chat-widget (WordPress plugin) — conventions

Single-file WordPress plugin that injects the Virage chat-widget SDK on customer WP sites — the POPUP embed mode plus display rules, for customers who'd rather install a plugin than paste a script. GPLv2. Requires WP ≥ 5.0, PHP ≥ 7.4. System context: `../CLAUDE.md`; the SDK itself: `../chat-widget/CLAUDE.md`.

## How it works

- `virage_ai_add_widget_script()` on the `wp_footer` hook prints `<script src="https://storage.googleapis.com/virage-public/chat-widget/cdn/chat-widget-sdk-v1.min.js" data-channel-uuid="…" async defer>` (`virage-ai-chat-widget.php:294`). The SDK then fetches the `launch` config from `app-server.virage.ai` and mounts the launcher + iframe. The plugin itself makes **no** API calls; the public `channel_uuid` is its only Virage identity — all appearance/behavior lives on the platform.
- **Load the SDK from the GCS bucket, never from `chat-widget.virage.ai`.** The script is fetched on every page view of every install, whether or not a visitor opens the chat; the app origin sends `Cache-Control: max-age=0` against a scale-0/1 Cloud Run service, the bucket sends `max-age=3600, s-maxage=86400`. Safe since the SDK stopped deriving its iframe target from the script origin (`../chat-widget` PR #92) — the iframe still lands on `chat-widget.virage.ai` via the SDK's own fallback.
- Settings page: **Settings → Virage AI Chat** (capability `manage_options`), one option row `virage_ai_options` with: `enabled` (master switch), `channel_uuid` (required), `display_locations[]` (homepage, posts, pages, archives, categories, 404, plus auto-discovered public custom post types).
- Render guard order: bail unless `enabled` **and** `channel_uuid`; bail if **no display location is selected** (silent — classic support-ticket trap); then match `is_front_page` / `is_singular('post')` / `is_page` / `is_archive` / `is_category` / `is_404` / CPT.
- i18n: WordPress gettext, text domain `virage-ai-chat-widget`, `languages/` (.pot + fr_FR).

## Distribution & releases

- Updates ship through the vendored `plugin-update-checker` v5 pointed at **GitHub `Virage-AI/virage-ai-chat-widget`, branch `main`** (`virage-ai-chat-widget.php:30-35`).
- ⚠ **A GitHub Release is the gate — pushing to `main` ships nothing.** Because the configured branch is `main`, PUC tries *latest release* → *highest version tag* → branch head, in that order (`plugin-update-checker/Puc/v5p6/Vcs/GitHubApi.php:358`). Releases have existed since 2025-08-06, so the branch strategy is never reached. Back-office downloads gate on the same thing — `WordpressPluginController` in back-office-server reads `releases/latest` — so one Release feeds both channels.
- To release: bump the plugin header `Version:` and `readme.txt` `Stable tag:` together, push to `main`, **then publish a GitHub Release** (not draft, not pre-release) tagged with that version. What installs compare is the `Version:` header read at the release tag (`Puc/v5p6/Vcs/PluginUpdateChecker.php:79-86`) — PUC's GitHub path never reads `Stable tag` (only `BitBucketApi.php` does); keep it in sync for readers of the readme. `Tested up to` is read from `readme.txt` at the same tag: below the site's WP version, the Updates screen says "Compatibility: Not tested".
- **Tag names must not carry a `v` prefix** (`1.4.2`, not `v1.4.2`). back-office-server downloads `archive/refs/tags/$tagName.zip` and unzips into `virage-ai-chat-widget-$tagName`, while GitHub strips a leading `v` from the archive's folder name — a `v`-prefixed tag breaks the back-office download. Every tag to date is unprefixed.
- Manual install: the zip attached to the latest Release. No CI, no build step.

## Gotchas

- `register_activation_hook` loads `__DIR__ . '/defaults.php'` when present to pre-seed `virage_ai_options` (and force-enable) — **that file is not in the repo**; per-customer pre-configured zips are built by dropping it in. Documented nowhere else.
- The widget host is hardcoded to production — no dev switch, no filter hook; testing against dev means editing the plugin.
- The vendored `plugin-update-checker/` (with its own `vendor/` and 30+ locale files) is committed wholesale — ignore it when scanning the plugin.
- `.gitattributes` marks `CLAUDE.md` `export-ignore`: GitHub's release/tag zips (what PUC and the back-office download install) would otherwise ship it, and every customer site would serve it publicly. Add any other dev-only file there too.
