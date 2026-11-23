#!/usr/bin/env bash
# Builds plugin/dist/mediawise-course-app-<version>.zip: the plugin source plus the app from the repo root
# (index.html, brand/, lib/, and courses/sample/ as the fallback course). Run from anywhere: bash plugin/build.sh
set -euo pipefail
here="$(cd "$(dirname "$0")" && pwd)"; root="$(dirname "$here")"
ver="$(grep -m1 "Version:" "$here/mediawise-course-app/mediawise-course-app.php" | awk '{print $NF}')"
work="$(mktemp -d)"; trap 'rm -rf "$work"' EXIT
cp -R "$here/mediawise-course-app" "$work/"
mkdir -p "$work/mediawise-course-app/app"
cp -R "$root/index.html" "$root/brand" "$root/lib" "$root/courses" "$work/mediawise-course-app/app/"
mkdir -p "$here/dist"; rm -f "$here/dist/mediawise-course-app-$ver.zip"
(cd "$work" && zip -qr "$here/dist/mediawise-course-app-$ver.zip" mediawise-course-app -x '*.DS_Store')
echo "built plugin/dist/mediawise-course-app-$ver.zip ($(du -h "$here/dist/mediawise-course-app-$ver.zip" | cut -f1))"
