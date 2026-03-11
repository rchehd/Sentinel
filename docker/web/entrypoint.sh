#!/bin/sh
set -e

# Inject runtime environment variables into the static build.
# The web image is built once (VITE_API_URL is not known at build time),
# so we generate /srv/env.js at container startup from the API_URL env var.
# React reads window.__ENV.API_URL instead of import.meta.env.VITE_API_URL.
cat > /srv/env.js <<EOF
window.__ENV = {
  API_URL: "${API_URL:-https://api.sentinel.localhost}"
};
EOF

exec "$@"
