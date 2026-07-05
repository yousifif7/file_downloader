#!/usr/bin/env bash

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BIN="$ROOT/bin"
TEMP_DIR="$ROOT/storage/app/yt-dlp-temp"
YTDLP_URL="https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp"

mkdir -p "$TEMP_DIR"

echo "Setting up bundled tools in $BIN"

if [[ -f "$BIN/yt-dlp" && ! -f "$BIN/yt-dlp.bin" ]]; then
    if head -1 "$BIN/yt-dlp" 2>/dev/null | grep -q '^#!.*bash'; then
        :
    elif head -1 "$BIN/yt-dlp" 2>/dev/null | grep -q '^#!'; then
        if ! python3 "$BIN/yt-dlp" --version >/dev/null 2>&1; then
            mv "$BIN/yt-dlp" "$BIN/yt-dlp.bin"
            echo "  renamed yt-dlp -> yt-dlp.bin (blocked PyInstaller binary)"
        fi
    else
        mv "$BIN/yt-dlp" "$BIN/yt-dlp.bin"
        echo "  renamed yt-dlp -> yt-dlp.bin (PyInstaller binary)"
    fi
fi

if [[ -f "$BIN/yt-dlp.bin" ]]; then
    chmod +x "$BIN/yt-dlp.bin"
fi

chmod +x "$BIN/yt-dlp.sh"

download_python_script() {
    echo "  downloading yt-dlp.py (no pip needed) ..."
    if command -v curl >/dev/null 2>&1; then
        curl -fsSL -o "$BIN/yt-dlp.py" "$YTDLP_URL"
    elif command -v wget >/dev/null 2>&1; then
        wget -q -O "$BIN/yt-dlp.py" "$YTDLP_URL"
    else
        echo "  yt-dlp: FAILED — install curl or wget"
        return 1
    fi
    chmod +x "$BIN/yt-dlp.py"
}

if [[ ! -f "$BIN/yt-dlp.py" ]]; then
    download_python_script || true
fi

if "$BIN/yt-dlp.sh" --version >/dev/null 2>&1; then
    echo "  yt-dlp: OK ($("$BIN/yt-dlp.sh" --version 2>/dev/null | head -1))"
else
    echo "  yt-dlp: trying Python script download ..."
    download_python_script || true
    if "$BIN/yt-dlp.sh" --version >/dev/null 2>&1; then
        echo "  yt-dlp: OK via python3 ($("$BIN/yt-dlp.sh" --version 2>/dev/null | head -1))"
    elif command -v python3 >/dev/null 2>&1 && python3 -m pip --version >/dev/null 2>&1; then
        echo "  yt-dlp: trying pip ..."
        python3 -m pip install --user -U yt-dlp
        if "$BIN/yt-dlp.sh" --version >/dev/null 2>&1; then
            echo "  yt-dlp: OK via pip ($("$BIN/yt-dlp.sh" --version 2>/dev/null | head -1))"
        else
            echo "  yt-dlp: FAILED — run: python3 bin/yt-dlp.py --version"
        fi
    else
        echo "  yt-dlp: FAILED — run: python3 bin/yt-dlp.py --version"
    fi
fi

extract_ffmpeg() {
    local archive="$BIN/ffmpeg-linux.tar.xz"
    local dest="$BIN/ffmpeg"
    local tmp
    local found

    if [[ -x "$dest" ]]; then
        echo "  ffmpeg: already present"
        return 0
    fi

    if [[ ! -f "$archive" ]]; then
        echo "  ffmpeg: MISSING — add bin/ffmpeg-linux.tar.xz or upload bin/ffmpeg directly"
        return 0
    fi

    echo "  ffmpeg: extracting from ffmpeg-linux.tar.xz ..."
    tmp="$(mktemp -d)"

    if tar -xf "$archive" -C "$tmp" 2>/dev/null; then
        :
    elif command -v python3 >/dev/null 2>&1; then
        python3 - "$archive" "$tmp" <<'PY'
import lzma
import sys
import tarfile

archive, dest = sys.argv[1], sys.argv[2]

with lzma.open(archive) as xz_file:
    with tarfile.open(fileobj=xz_file) as tar:
        try:
            tar.extractall(dest, filter='data')
        except TypeError:
            tar.extractall(dest)
PY
    else
        echo "  ffmpeg: SKIPPED — this server has no xz or python3."
        rm -rf "$tmp"
        return 0
    fi

    found="$(find "$tmp" -type f -name ffmpeg | head -1)"

    if [[ -z "$found" ]]; then
        echo "  ffmpeg: ERROR — binary not found inside archive"
        rm -rf "$tmp"
        return 0
    fi

    cp "$found" "$dest"
    chmod +x "$dest"
    rm -rf "$tmp"
    echo "  ffmpeg: OK ($("$dest" -version 2>/dev/null | head -1 || echo 'extracted'))"
}

extract_ffmpeg

install_deno() {
    if [[ -x "$BIN/deno" ]]; then
        echo "  deno: OK ($("$BIN/deno" --version 2>/dev/null | head -1))"
        return 0
    fi

    echo "  deno: downloading (required for YouTube) ..."
    local zip="$BIN/deno.zip"
    local url="https://github.com/denoland/deno/releases/latest/download/deno-x86_64-unknown-linux-gnu.zip"

    if ! curl -fsSL -o "$zip" "$url"; then
        echo "  deno: FAILED — download manually from https://github.com/denoland/deno/releases"
        return 1
    fi

    if command -v unzip >/dev/null 2>&1; then
        unzip -o -j "$zip" deno -d "$BIN"
    elif command -v python3 >/dev/null 2>&1; then
        python3 - "$zip" "$BIN" <<'PY'
import sys
import zipfile

zipfile.ZipFile(sys.argv[1]).extract("deno", sys.argv[2])
PY
    else
        echo "  deno: FAILED — need unzip or python3 to extract"
        rm -f "$zip"
        return 1
    fi

    rm -f "$zip"
    chmod +x "$BIN/deno"

    if "$BIN/deno" --version >/dev/null 2>&1; then
        echo "  deno: OK ($("$BIN/deno" --version 2>/dev/null | head -1))"
    else
        echo "  deno: FAILED after extract"
        return 1
    fi
}

install_deno || true

echo "Done. The app uses bin/yt-dlp.sh + bin/deno for YouTube."
