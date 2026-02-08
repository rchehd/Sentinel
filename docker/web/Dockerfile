# Stage 1: Build
FROM node:20-alpine AS build

WORKDIR /app

COPY app/web/package.json app/web/package-lock.json ./
RUN npm ci

COPY app/web/ ./
RUN npm run build

# Stage 2: Serve
FROM caddy:2-alpine AS prod

COPY docker/web/Caddyfile /etc/caddy/Caddyfile
COPY --from=build /app/dist /srv
