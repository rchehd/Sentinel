# scripts/generate-secrets.sh
#!/bin/bash

echo "Generating secrets..."

APP_SECRET=$(openssl rand -hex 32)
MERCURE_JWT_SECRET=$(openssl rand -base64 32)
POSTGRES_PASSWORD=$(openssl rand -base64 16)

cat > .env << EOF
APP_ENV=dev
APP_SECRET=$APP_SECRET

POSTGRES_VERSION=16
POSTGRES_CHARSET=utf8
POSTGRES_DB=formbuilder
POSTGRES_USER=formbuilder
POSTGRES_PASSWORD=$POSTGRES_PASSWORD

MERCURE_URL=http://api/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:8000/.well-known/mercure
MERCURE_JWT_SECRET=$MERCURE_JWT_SECRET

CORS_ALLOW_ORIGIN=http://localhost:5173

VITE_API_URL=http://localhost:8000
VITE_WS_URL=ws://localhost:8000/.well-known/mercure

SERVER_NAME=:80
FRANKENPHP_CONFIG=worker ./public/index.php
EOF

echo "✅ Secrets generated in .env"