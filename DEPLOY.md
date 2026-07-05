# Deploy to Hostinger (zip upload)

This project is set up so you can zip the folder, upload it, and import your MySQL database.

## What to include in the zip

Include the full project **except** (to save space):

- `node_modules/` — run `npm ci && npm run build` on the server, or build locally and include `public/build/`
- `.git/` — optional
- **`public/hot`** — **never upload this file.** It tells Laravel to load CSS/JS from your local Vite dev server (`localhost:5173`) and breaks the live site.

**Do include:**

- `vendor/` (run `composer install --no-dev` locally first for production)
- `bin/yt-dlp`, `bin/ffmpeg-linux.tar.xz` (FFmpeg is extracted on the server)
- `public/build/` if you built assets locally

Approximate `bin/` size: ~175 MB (yt-dlp + FFmpeg archive).

## 1. Prepare locally

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Export your database from phpMyAdmin or:

```bash
mysqldump -u root downloader > downloader.sql
```

## 2. Production `.env`

Copy `.env.example` to `.env` on the server and set:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-subdomain.yourdomain.com

DB_HOST=localhost
DB_DATABASE=your_hostinger_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Auto-detects bin/yt-dlp and bin/ffmpeg — leave empty
# YT_DLP_PATH=
# FFMPEG_PATH=

# Required on Hostinger — use database queue with cron (not sync)
QUEUE_CONNECTION=database
```

Generate a new app key on the server:

```bash
php artisan key:generate
```

## 3. Upload and extract

1. Zip the project folder.
2. Upload via Hostinger File Manager or FTP.
3. Extract into your subdomain folder (e.g. `public_html/subdomain`).

## 4. Point subdomain to `public/`

In Hostinger:

- **Websites → Subdomains → Manage** — set document root to `.../your-project/public`

If you cannot change the document root, move contents of `public/` to the web root and adjust `index.php` paths (standard Laravel shared-hosting workaround).

## 5. Server setup (once)

Via SSH or Hostinger terminal:

```bash
cd /home/you/domains/subdomain/public_html/..   # project root, not public/
bash bin/setup-linux.sh
chmod -R 775 storage bootstrap/cache
php artisan migrate --force
php artisan storage:link
```

Import `downloader.sql` in phpMyAdmin.

## 6. Cron job (queue + cleanup) — required

Downloads are queued in the database. **Without cron, jobs stay pending forever.**

You only need **one** cron job. Laravel’s scheduler runs the queue worker every minute.

### Option A — Hostinger hPanel (recommended)

1. Log in to [hPanel](https://hpanel.hostinger.com/)
2. Go to **Advanced** → **Cron Jobs**
3. Create a new cron job:
   - **Type:** Custom
   - **Schedule:** Every minute (`* * * * *`)
   - **Command:** use **one** of the following

**Using the project script** (easiest):

```bash
/bin/bash /home/u623700213/domains/yousiffarra.com/public_html/downloader/bin/hostinger-cron.sh
```

**Or direct PHP** (if bash path differs):

```bash
/usr/bin/php /home/u623700213/domains/yousiffarra.com/public_html/downloader/artisan schedule:run >> /home/u623700213/domains/yousiffarra.com/public_html/downloader/storage/logs/cron.log 2>&1
```

4. Save the cron job.

5. Once via SSH, make the script executable:

```bash
chmod +x /home/u623700213/domains/yousiffarra.com/public_html/downloader/bin/hostinger-cron.sh
```

### What runs automatically

| Task | When |
|------|------|
| `queue:work` — process downloads | Every minute |
| `downloads:cleanup` — delete old files | Daily |
| `quotas:reset` — monthly free tier reset | 1st of month |

### Verify cron is working

1. Start a download on the site (status should move from *Pending* to *Processing* → *Completed* within 1–2 minutes).
2. Check log files on the server:
   - `storage/logs/cron.log`
   - `storage/logs/queue-cron.log`
3. Or SSH: `php artisan queue:work --once` (manual one-off test).

### Production `.env` reminder

```env
QUEUE_CONNECTION=database
```

Do **not** use `QUEUE_CONNECTION=sync` on Hostinger unless you accept slow page loads and no background processing.

If `php` is not found in cron, find the path via SSH (`which php`) and use the full path in the cron command.

## 7. Verify

1. Open the site URL — landing page loads.
2. Log in as admin (`admin@example.com` / `password` — **change this**).
3. Paste a YouTube URL and test analyze + download.

If downloads fail, check `storage/logs/laravel.log` for yt-dlp or permission errors.

## 8. YouTube blocked on shared hosting

If you see *"YouTube blocked this server"*, Hostinger’s IP is flagged by YouTube. Use a **cookies file**:

1. In Chrome/Firefox, install **"Get cookies.txt LOCALLY"** (browser extension).
2. Go to [youtube.com](https://www.youtube.com) while logged in.
3. Export cookies for `youtube.com` → save as `cookies.txt` (Netscape format).
4. Upload to `storage/app/yt-dlp-cookies.txt` on the server (File Manager or FTP).
5. Run `php artisan config:clear`.
6. Test analyze again.

Cookies expire after a while — re-export when YouTube blocks again.

## 9. YouTube “Signature solving failed” / “No video formats found”

Since 2025, yt-dlp needs **Deno** (or Node 22+) to solve YouTube’s JavaScript challenges. See [yt-dlp EJS wiki](https://github.com/yt-dlp/yt-dlp/wiki/EJS).

### Diagnose first

```bash
cd /home/u623700213/domains/yousiffarra.com/public_html/downloader
php artisan downloader:doctor
```

### Fix (SSH or hPanel Terminal)

```bash
cd /home/u623700213/domains/yousiffarra.com/public_html/downloader
bash bin/setup-linux.sh
# if bash is unavailable:
php bin/install-deno.php
chmod +x bin/deno bin/yt-dlp.sh bin/ffmpeg
php artisan config:clear
```

This downloads `bin/deno` (~40 MB) and extracts `bin/ffmpeg` from `bin/ffmpeg-linux.tar.xz`. The app passes both to yt-dlp automatically.

### Verify FFmpeg (480p / 720p / 1080p need this)

```bash
php artisan downloader:doctor
bin/ffmpeg -version
ls -la bin/ffmpeg
```

If doctor says FFmpeg is missing:

1. Make sure `bin/ffmpeg-linux.tar.xz` was included in your zip (~50 MB archive).
2. Run `bash bin/setup-linux.sh` to extract `bin/ffmpeg`.
3. Run `chmod +x bin/ffmpeg`.

Without FFmpeg you only get **360p** (single-file format) and **audio** — higher qualities merge separate video + audio streams.

### After changing `.env` or re-uploading the project

Re-uploading **without** `bin/deno` is a common cause — Deno is downloaded on the server, not in your zip.

1. Run `bash bin/setup-linux.sh` or `php bin/install-deno.php` again.
2. **Always** run `php artisan config:clear` after editing `.env` (or `config:cache` will serve stale values).
3. Keep these set on Hostinger:

```env
APP_URL=https://downloader.yousiffarra.com
QUEUE_CONNECTION=database
YT_DLP_PYTHON=/opt/alt/python311/bin/python3.11
```

4. Leave **empty** unless you have a good reason (wrong paths break auto-detect):

```env
# YT_DLP_PATH=
# YT_DLP_DENO_PATH=
# FFMPEG_PATH=
```

Optional `.env` only if auto-detect fails:

```env
YT_DLP_DENO_PATH=/home/u623700213/domains/yousiffarra.com/public_html/downloader/bin/deno
```

## Notes

- Hostinger shared plans may block executing binaries in `bin/`. If so, contact support or use a VPS plan.
- Change the seeded admin password before going live.
- Set `APP_DEBUG=false` in production.
