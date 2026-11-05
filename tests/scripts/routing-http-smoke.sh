#!/usr/bin/env bash
set -euo pipefail

base_url="${1:?Usage: routing-http-smoke.sh BASE_URL}"
base_url="${base_url%/}"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

status="$(curl --silent --show-error --output "$tmp_dir/user.json" --write-out '%{http_code}' "$base_url/users/42")"
[[ "$status" == "200" ]] || { echo "GET /users/42 expected 200, got $status" >&2; exit 1; }
php -r '$data = json_decode(file_get_contents($argv[1]), true); if (!is_array($data) || ($data["id"] ?? null) !== 42) { fwrite(STDERR, "GET /users/42 returned an unexpected payload\n"); exit(1); }' "$tmp_dir/user.json"

status="$(curl --silent --show-error --request POST --dump-header "$tmp_dir/post.headers" --output "$tmp_dir/post.body" --write-out '%{http_code}' "$base_url/users/42")"
[[ "$status" == "405" ]] || { echo "POST /users/42 expected 405, got $status" >&2; exit 1; }
grep -Eqi '^Allow:.*GET' "$tmp_dir/post.headers"

status="$(curl --silent --show-error --request OPTIONS --dump-header "$tmp_dir/options.headers" --output "$tmp_dir/options.body" --write-out '%{http_code}' "$base_url/users/42")"
[[ "$status" == "204" ]] || { echo "OPTIONS /users/42 expected 204, got $status" >&2; exit 1; }
grep -Eqi '^Allow:.*GET' "$tmp_dir/options.headers"
grep -Eqi '^Allow:.*OPTIONS' "$tmp_dir/options.headers"

status="$(curl --silent --show-error --head --output "$tmp_dir/head.body" --write-out '%{http_code}' "$base_url/users/42")"
[[ "$status" == "200" ]] || { echo "HEAD /users/42 expected 200, got $status" >&2; exit 1; }
[[ ! -s "$tmp_dir/head.body" ]] || { echo "HEAD /users/42 must not return a response body" >&2; exit 1; }

status="$(curl --silent --show-error --output "$tmp_dir/missing.body" --write-out '%{http_code}' "$base_url/__ci_route_that_does_not_exist__")"
[[ "$status" == "404" ]] || { echo "Unknown route expected 404, got $status" >&2; exit 1; }

echo "Routing HTTP contract passed for $base_url (GET, 405/Allow, OPTIONS, HEAD, 404)."
