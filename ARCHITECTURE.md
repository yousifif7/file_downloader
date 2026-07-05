# File Downloader — Architecture & Handoff

This document is the current handoff for future agents. It reflects the project as it exists now, including the live deployment target, recent YouTube/server fixes, and the main work still left.

---

## Project Summary

`Downloader` is a Laravel 11 application deployed on the subdomain:

- `https://downloader.yousiffarra.com`

Main user flow:

1. Visitor pastes a URL on the landing page
2. App analyzes the URL and shows available formats
3. User signs in to start the download
4. Download is queued and processed in the background
5. User gets the finished file in `My account`

This is not just a local demo. The project is designed around real shared-hosting constraints on Hostinger.

---

## Current Product Scope

### Working now

- Public landing page with downloader UI
- Auth flow
- User account page with quota + history
- Admin panel
- Queue-based downloads
- YouTube support
- TikTok support
- Twitter / X support
- Direct file URL support
- SEO basics for the subdomain
- Hostinger shared-hosting deployment flow

### Not built yet

- Payments
- Actual paid package purchase flow
- Subscription lifecycle
- Advanced monitoring / analytics
- Additional unstable platforms like Instagram / Facebook / LinkedIn

---

## Stack

| Layer | Choice |
|------|--------|
| Backend | PHP 8.2 + Laravel 11 |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| Auth | Laravel Breeze (Blade) |
| Database | MySQL |
| Queue | Laravel `database` queue |
| Sessions | `database` driver |
| Cache | `database` driver |
| Storage | local disk under `storage/app` |
| Video extraction | `yt-dlp` |
| JS runtime for YouTube | `Deno` |
| Media merge / audio extraction | `FFmpeg` |
| Build tool | Vite |
| Hosting target | Hostinger shared hosting |

Important principle: no paid SaaS dependencies are required for core app behavior.

---

## Live Deployment Assumptions

Production target used throughout the project:

- Domain: `downloader.yousiffarra.com`
- Project root on server:
  `/home/u623700213/domains/yousiffarra.com/public_html/downloader`

The app is deployed by zip upload, not a full VPS workflow.

Operational assumptions:

- cron is available
- long-running daemon processes are not assumed
- bundled binaries/scripts under `bin/` are used to make Hostinger workable

---

## Runtime Architecture

```text
Browser
  -> Laravel routes/controllers
  -> Extractor service layer
  -> yt-dlp / ffmpeg / deno subprocesses
  -> local storage for files
  -> MySQL for users/downloads/jobs/settings

Scheduler (cron)
  -> queue:work --stop-when-empty
  -> download cleanup
  -> monthly quota reset
```

Key point: queue processing is driven by Laravel scheduler every minute, not a permanently running worker.

---

## Main Routes

### Public

- `GET /` — landing page
- `POST /analyze` — analyze a URL and return formats
- `GET /terms`
- `GET /privacy`
- `GET /robots.txt`
- `GET /sitemap.xml`

### Authenticated user

- `GET /account`
- `POST /downloads`
- `GET /downloads/{download}`
- `GET /downloads/{download}/file`
- profile routes

### Admin

- `/admin`
- `/admin/users`
- `/admin/downloads`
- `/admin/plans`
- `/admin/platforms`
- `/admin/settings`
- `/admin/settings/youtube-cookies`

---

## Key App Layers

### Controllers

- `DownloadController` — landing page, analyze, create download, serve finished file
- `AccountController` — user dashboard
- `PageController` — terms/privacy
- `SeoController` — `robots.txt`, `sitemap.xml`
- `Admin/*` — users, downloads, plans, platforms, settings

### Services

- `UrlDetector` — maps URL to platform slug
- `ExtractorRegistry` — returns matching extractor
- `DownloadQuotaService` — monthly free-tier enforcement
- `SignedDownloadService` — secure file access
- `YtDlp` — wraps binary/runtime detection for yt-dlp, Deno, FFmpeg, Python
- `Extractors/*` — per-platform implementations

### Jobs / commands

- `ProcessDownloadJob`
- `CleanupExpiredDownloads`
- `ResetMonthlyQuotas`
- `DownloaderDoctor`

---

## Supported Platforms

### Stable enough for current product

- YouTube
- TikTok
- Twitter / X
- Direct file links

### Not ready / not confirmed

- Instagram
- Facebook
- LinkedIn

These should not be marketed as production-ready until tested and enabled intentionally.

---

## Extractor Strategy

### Generic yt-dlp platforms

`YtDlpExtractor` handles yt-dlp-backed platforms in a generic way:

- metadata via `yt-dlp --dump-json`
- download to `storage/app/downloads/{uuid}.ext`
- file extension normalization
- error cleanup / normalization

### YouTube

`YoutubeExtractor` is special-cased because YouTube is the hardest platform on shared hosting.

Current behavior:

- prefers reliable `360p MP4` progressive stream
- exposes higher qualities only when FFmpeg is available
- uses Deno for YouTube JS challenges
- retries using alternate YouTube player clients
- supports admin-uploaded cookies
- audio-only now extracts audio from a known-working video stream when possible

Why it is special:

- direct `bestaudio` streams are more likely to be blocked than the working 360p progressive stream
- high-quality video usually needs separate audio + video merging
- Hostinger IP reputation causes periodic cookie-related failures

---

## Queue / Scheduler Model

Defined in `routes/console.php`:

- `downloads:cleanup` daily
- `quotas:reset` monthly
- `queue:work --stop-when-empty --max-time=55 --tries=2` every minute

This means one Hostinger cron entry is required to keep the app functional.

If cron stops:

- downloads remain pending
- cleanup stops
- quotas stop resetting

---

## Filesystem / Binary Expectations

### Important bundled files

- `bin/yt-dlp.sh`
- `bin/yt-dlp.py`
- `bin/install-deno.php`
- `bin/setup-linux.sh`
- `bin/hostinger-cron.sh`
- `bin/ffmpeg-linux.tar.xz` (must be uploaded)

### Expected server-generated binaries

- `bin/deno`
- `bin/ffmpeg`

These can go missing after re-uploading the project and often need to be reinstalled.

---

## Environment Notes

Production-critical `.env` expectations:

```env
APP_URL=https://downloader.yousiffarra.com
APP_DEBUG=false

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

YT_DLP_PYTHON=/opt/alt/python311/bin/python3.11
```

Usually leave these unset unless auto-detect fails:

```env
# YT_DLP_PATH=
# YT_DLP_DENO_PATH=
# FFMPEG_PATH=
```

Very important:

- after changing `.env`, run `php artisan config:clear`
- stale config cache is a common production issue

---

## Current SEO State

Implemented:

- dynamic `robots.txt`
- dynamic `sitemap.xml`
- canonical/meta tags partial
- Open Graph / Twitter tags
- FAQ / WebApplication structured data on landing page
- noindex on auth pages
- branded logo / favicon assets

Branding used now:

- product name: `Downloader`
- parent brand: `Yousif ElFarra`

---

## Known Production Constraints

These are normal for this project and should not surprise future agents:

1. **YouTube on shared hosting is fragile**
   - cookies expire
   - YouTube blocks some server IP behavior
   - Deno is required

2. **Re-uploading the project can break media tooling**
   - `bin/deno` may disappear
   - `bin/ffmpeg` may disappear
   - permissions may need `chmod +x`

3. **360p works more reliably than higher qualities**
   - because it is often a progressive stream
   - higher qualities usually require FFmpeg

4. **Audio-only is not “free”**
   - current approach depends on FFmpeg for robust extraction from a working source stream

---

## Important Diagnostic Command

Use this first on the server:

```bash
php artisan downloader:doctor
```

It checks:

- yt-dlp
- Python path
- Deno / Node runtime
- FFmpeg
- cookies file presence

If deploy/runtime issues appear, this should be the first step before deeper changes.

---

## What Has Been Fixed Recently

Recent work that future agents should know is already done:

- SEO integration for subdomain launch
- branded `Downloader` naming instead of `GrabLink`
- transparent logo / favicon assets
- dynamic `robots.txt` / `sitemap.xml`
- `downloader:doctor` command
- PHP installer for Deno (`bin/install-deno.php`)
- better FFmpeg detection
- YouTube quality exposure tied to FFmpeg availability
- audio-only no longer silently falls back to MP4 video
- audio-only path changed to extract audio from a known-working source stream

---

## Main Remaining Work

This is the section future agents should treat as the active backlog.

### 1. Production verification on Hostinger

Still needs live verification after the latest extractor/runtime changes:

- `downloader:doctor` on the server
- `bin/deno` exists and is executable
- `bin/ffmpeg` exists and is executable
- `Audio only (M4A)` works in production
- 480p / 720p appear when FFmpeg is present

This is the highest-priority operational task.

### 2. Final YouTube reliability pass

The current YouTube logic is much better, but still needs real-world confirmation:

- confirm audio extraction path is stable
- confirm extension/output naming is correct in production
- confirm higher qualities work with FFmpeg on Hostinger
- watch for cases where cookies are needed for audio but not 360p

If issues remain, future agents should prefer targeted fixes in `YoutubeExtractor` and `YtDlpExtractor`, not major rewrites.

### 3. Better admin observability

Still needed:

- better visibility into failure reasons in admin
- filter/group failed downloads by platform
- identify repeated YouTube/cookie/runtime failures faster

This is useful before scaling traffic.

### 4. Paid packages / monetization

Not started. Current pricing UI is placeholder only.

Needed later:

- package definitions beyond free tier
- payment provider selection
- checkout flow
- webhook handling
- subscription/plan state changes
- quota differences by plan

Do not assume any billing integration exists yet.

### 5. Platform expansion

Still open:

- Instagram
- Facebook
- LinkedIn

These should be treated as separate risky tasks, not quick toggles.

### 6. Product polish

Possible next improvements:

- better download status UX
- better empty states / retry guidance
- download analytics
- package comparison page
- OG image custom asset
- Search Console / indexing follow-up

---

## What Future Agents Should Avoid

- Do not switch the app away from Laravel + Blade unless explicitly asked
- Do not introduce paid infra/services just to “clean things up”
- Do not remove Hostinger compatibility assumptions
- Do not assume persistent background workers exist
- Do not set hardcoded binary paths unless auto-detect truly fails
- Do not reintroduce `GrabLink` branding

---

## Suggested Next-Agent Starting Checklist

When a future agent picks this up, start with:

1. Read `DEPLOY.md`
2. Read `ARCHITECTURE.md`
3. If issue is production/media-related, inspect:
   - `app/Services/YtDlp.php`
   - `app/Services/Extractors/YoutubeExtractor.php`
   - `app/Services/Extractors/YtDlpExtractor.php`
   - `app/Console/Commands/DownloaderDoctor.php`
4. If issue is deployment/runtime-related, ask for:
   - output of `php artisan downloader:doctor`
   - output of `php artisan config:clear`
   - whether `bin/setup-linux.sh` was re-run after upload
5. If issue is SEO/branding-related, inspect:
   - `config/seo.php`
   - `resources/views/partials/seo.blade.php`
   - `resources/views/landing.blade.php`

---

## Short Status Summary

The app is already a working downloadable-media SaaS MVP on Laravel with a public landing page, auth-gated downloads, admin, SEO, Hostinger deployment support, and queue-based processing.

The main unfinished area is no longer core CRUD or UI. It is now:

- production hardening
- YouTube runtime reliability
- FFmpeg / Deno verification on Hostinger
- future paid packages

That is the current state of the project.
