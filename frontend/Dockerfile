# ==========================================
# Multi-Stage Dockerfile Frontend — JejakBarang
# Stage 1: Build Aset Statis (Node 20 Alpine & pnpm)
# ==========================================
FROM node:20-alpine AS builder

WORKDIR /app

# Aktifkan pnpm via corepack
RUN corepack enable && corepack prepare pnpm@latest --activate

# Copy manifest dependensi
COPY package.json pnpm-lock.yaml ./

# Install dependensi
RUN pnpm install --frozen-lockfile

# Copy seluruh source code
COPY . .

# Build production bundle
RUN pnpm run build

# ==========================================
# Stage 2: Web Server Ringan (Nginx 1.27 Alpine)
# ==========================================
FROM nginx:1.27-alpine

# Hapus konfigurasi default Nginx
RUN rm -rf /etc/nginx/conf.d/default.conf

# Salin konfigurasi kustom Nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Salin aset hasil build dari Stage 1
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
