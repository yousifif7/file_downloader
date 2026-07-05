# Bundled tools

Portable binaries for zip deploy. On Linux shared hosting the app uses **`bin/yt-dlp.sh`** (not the raw binary directly).

## Files

| File | Platform | Purpose |
|------|----------|---------|
| `yt-dlp.sh` | **Linux server** | Launcher — used by the Laravel app |
| `yt-dlp.py` | Linux | Python script from [yt-dlp releases](https://github.com/yt-dlp/yt-dlp/releases) (no pip) |
| `yt-dlp` / `yt-dlp.bin` | Linux | PyInstaller binary (often blocked on Hostinger) |
| `yt-dlp.exe` | Windows (local dev) | For your PC only |
| `ffmpeg-linux.tar.xz` | Linux | Download on server (too large for GitHub — see below) |
| `ffmpeg` | Linux | Created by `setup-linux.sh` |
| `ffmpeg.exe` | Windows | Optional for local remux |

## After uploading to Hostinger

Download FFmpeg on the server first (not stored in git):

```bash
curl -L -o bin/ffmpeg-linux.tar.xz https://github.com/yt-dlp/FFmpeg-Builds/releases/download/latest/ffmpeg-master-latest-linux64-gpl.tar.xz
```

Then:

```bash
cd /path/to/your/project
bash bin/setup-linux.sh
php artisan config:clear
```

If the bundled binary fails with `libz.so.1: failed to map segment`, setup installs **yt-dlp via Python** automatically.

## Local Windows

- `bin/yt-dlp.exe` is used when `YT_DLP_PATH` is empty in `.env`.
- For merges, add `ffmpeg.exe` or install FFmpeg on your PC.

## Refresh binaries

**yt-dlp (Linux)**

```bash
curl -L -o bin/yt-dlp.bin https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_linux
chmod +x bin/yt-dlp.bin bin/yt-dlp.sh
```

**FFmpeg (Linux)**

```bash
curl -L -o bin/ffmpeg-linux.tar.xz https://github.com/yt-dlp/FFmpeg-Builds/releases/download/latest/ffmpeg-master-latest-linux64-gpl.tar.xz
bash bin/setup-linux.sh
```
