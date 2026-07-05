#!/usr/bin/env bash

BIN_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$BIN_DIR/.." && pwd)"
TEMP_DIR="$ROOT/storage/app/yt-dlp-temp"
COOKIES_FILE="$ROOT/storage/app/yt-dlp-cookies.txt"

export TMPDIR="$TEMP_DIR"
export TEMP="$TEMP_DIR"
export TMP="$TEMP_DIR"
mkdir -p "$TEMP_DIR"

find_python() {
    local candidate

    if [[ -n "${YT_DLP_PYTHON:-}" ]]; then
        if "$YT_DLP_PYTHON" -c 'import sys; exit(0 if sys.version_info >= (3, 10) else 1)' 2>/dev/null; then
            echo "$YT_DLP_PYTHON"
            return 0
        fi
    fi

    for candidate in \
        python3.12 python3.11 python3.10 \
        /opt/alt/python312/bin/python3.12 \
        /opt/alt/python311/bin/python3.11 \
        /opt/alt/python310/bin/python3.10 \
        /usr/bin/python3.12 \
        /usr/bin/python3.11 \
        /usr/bin/python3.10; do
        if command -v "$candidate" >/dev/null 2>&1 \
            && "$candidate" -c 'import sys; exit(0 if sys.version_info >= (3, 10) else 1)' 2>/dev/null; then
            echo "$candidate"
            return 0
        fi
    done

    return 1
}

has_arg() {
    local needle="$1"
    shift

    for arg in "$@"; do
        if [[ "$arg" == "$needle" ]]; then
            return 0
        fi
    done

    return 1
}

build_extra_args() {
    local -n out=$1
    shift
    local user_args=("$@")
    local deno_path="${YT_DLP_DENO_PATH:-$BIN_DIR/deno}"

    if ! has_arg '--js-runtimes' "${user_args[@]}"; then
        if [[ -x "$deno_path" ]]; then
            out+=(--js-runtimes "deno:$deno_path")
        elif [[ -x "$BIN_DIR/deno" ]]; then
            out+=(--js-runtimes "deno:$BIN_DIR/deno")
        fi
    fi

    if ! has_arg '--cookies' "${user_args[@]}" && [[ -f "$COOKIES_FILE" ]]; then
        out+=(--cookies "$COOKIES_FILE")
    fi

    if ! has_arg '--remote-components' "${user_args[@]}" && [[ -f "$BIN_DIR/yt-dlp.py" ]]; then
        out+=(--remote-components ejs:github)
    fi
}

PYTHON="$(find_python)" || {
    echo "yt-dlp needs Python 3.10+. This server only has: $(python3 --version 2>&1 || echo 'unknown')" >&2
    echo "Set YT_DLP_PYTHON=/path/to/python3.11 in .env" >&2
    exit 1
}

EXTRA_ARGS=()
build_extra_args EXTRA_ARGS "$@"

if [[ -x "$BIN_DIR/yt-dlp.bin" ]] && "$BIN_DIR/yt-dlp.bin" --version >/dev/null 2>&1; then
    exec "$BIN_DIR/yt-dlp.bin" "${EXTRA_ARGS[@]}" "$@"
fi

if [[ -f "$BIN_DIR/yt-dlp.py" ]]; then
    exec "$PYTHON" "$BIN_DIR/yt-dlp.py" "${EXTRA_ARGS[@]}" "$@"
fi

if "$PYTHON" -m yt_dlp --version >/dev/null 2>&1; then
    exec "$PYTHON" -m yt_dlp "${EXTRA_ARGS[@]}" "$@"
fi

echo "yt-dlp.py missing. Run: bash bin/setup-linux.sh" >&2
exit 1
