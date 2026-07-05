<?php

return [

    /*
    |--------------------------------------------------------------------------
    | yt-dlp binary path
    |--------------------------------------------------------------------------
    |
    | Full path to yt-dlp on Windows (e.g. C:\path\to\yt-dlp.exe) or just
    | "yt-dlp" if it is on your system PATH. Defaults to bin/yt-dlp.exe
    | inside the project when that file exists.
    |
    */

    'yt_dlp_path' => env('YT_DLP_PATH'),

    'yt_dlp_temp_path' => env('YT_DLP_TEMP_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Python for yt-dlp on Linux (3.10+ required)
    |--------------------------------------------------------------------------
    |
    | Hostinger default python3 is often 3.6. Set this to e.g.
    | /opt/alt/python311/bin/python3.11 from hPanel Python.
    |
    */

    'yt_dlp_python' => env('YT_DLP_PYTHON'),

    /*
    |--------------------------------------------------------------------------
    | JavaScript runtime for YouTube (required since 2025)
    |--------------------------------------------------------------------------
    |
    | yt-dlp needs Deno or Node 22+ to solve YouTube challenges. Auto-detects
    | bin/deno on Linux. See https://github.com/yt-dlp/yt-dlp/wiki/EJS
    |
    */

    'yt_dlp_deno_path' => env('YT_DLP_DENO_PATH'),

    'yt_dlp_node_path' => env('YT_DLP_NODE_PATH'),

    /*
    |--------------------------------------------------------------------------
    | FFmpeg binary path
    |--------------------------------------------------------------------------
    |
    | Required for merging video+audio (YouTube, etc.). Auto-detects bin/ffmpeg
    | (Linux) or bin/ffmpeg.exe (Windows) when unset.
    |
    */

    'ffmpeg_path' => env('FFMPEG_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Legacy fallback cookies file for yt-dlp (Netscape format)
    |--------------------------------------------------------------------------
    |
    | Kept for backward compatibility. New uploads are stored per platform
    | (YouTube, Instagram, Facebook, LinkedIn) under storage/app.
    |
    */

    'yt_dlp_cookies_path' => env('YT_DLP_COOKIES_PATH') ?: (
        is_file(storage_path('app/yt-dlp-cookies.txt'))
            ? storage_path('app/yt-dlp-cookies.txt')
            : null
    ),

    /*
    |--------------------------------------------------------------------------
    | Extra yt-dlp arguments (YouTube) — optional
    |--------------------------------------------------------------------------
    |
    | Leave empty for yt-dlp defaults (works best with cookies). Example:
    | YT_DLP_YOUTUBE_EXTRACTOR_ARGS="--extractor-args youtube:player_client=tv"
    |
    */

    'youtube_extractor_args' => env('YT_DLP_YOUTUBE_EXTRACTOR_ARGS')
        ? array_values(array_filter(preg_split('/\s+/', (string) env('YT_DLP_YOUTUBE_EXTRACTOR_ARGS')) ?: []))
        : [],

];
