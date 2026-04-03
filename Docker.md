# Docker Setup — Laravel + PostgreSQL

> Dev-first, CI/CD-ready. Hướng dẫn từ local đến production.

---

## Cấu trúc file

```
project/
├── Dockerfile            # Multi-stage: base → dev → production (sau này)
├── docker-compose.yml    # Dev only
└── DOCKER.md             # File này
```

---

## Kiến trúc Dockerfile — Multi-stage

```
┌─────────────────────────────────────────────────┐
│  FROM php:8.3-cli-alpine  ← base image (~35MB)  │
│                                                  │
│  Stage: base                                     │
│  ├── apk runtime libs (libpq, icu, oniguruma...) │
│  ├── PHP extensions (pdo_pgsql, mbstring, ...)   │
│  └── composer binary                             │
│                                                  │
│  Stage: dev  (target: dev)                       │
│  ├── ENV APP_ENV=local                           │
│  └── CMD: composer install + artisan serve       │
│                                                  │
│  Stage: production  (chưa có — xem phần 4)       │
│  ├── COPY --from=base                            │
│  ├── composer install --no-dev                   │
│  ├── php artisan optimize                        │
│  └── CMD: php-fpm                                │
└─────────────────────────────────────────────────┘
```

**Tại sao multi-stage?**

- `base` chứa toàn bộ extensions đã compile → tái dùng cho cả dev lẫn prod
- `dev` stage nhẹ, không có bước optimize
- CI/CD sau này chỉ cần build đến stage `production` → image sạch, không có dev tools

---

## Giải thích chi tiết Dockerfile

### 1. Base image

```dockerfile
FROM php:8.3-cli-alpine AS base
```

| Lựa chọn | Size | Lý do chọn |
|---|---|---|
| `php:8.3-apache` | ~450MB | Quá nặng, kéo theo Apache |
| `php:8.3-fpm` | ~140MB | Cần thêm nginx để chạy |
| `php:8.3-cli-alpine` | ~35MB | ✅ Nhẹ nhất, đủ dùng cho dev |

### 2. Pattern `.build-deps` — cách giảm size chính

```dockerfile
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        icu-dev \
    && docker-php-ext-install pdo_pgsql intl \
    && apk del .build-deps    # ← xóa build tools sau khi compile xong
```

**Giải thích:**

- `--virtual .build-deps` gom toàn bộ build tools vào một nhóm tên `.build-deps`
- Sau khi compile extension xong, `apk del .build-deps` xóa sạch gcc, make, headers...
- Runtime libs (`libpq`, `icu`, `oniguruma`) được cài riêng **không** nằm trong `.build-deps` → giữ lại để extension chạy được
- Kết quả: tiết kiệm ~80-100MB so với không dùng pattern này

### 3. Composer layer cache

```dockerfile
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
```

Chỉ copy binary `composer`, không kéo toàn bộ composer image (~200MB).

Trong `docker-compose.yml`, vendor được mount qua named volume:

```yaml
volumes:
  - .:/var/www          # source code → hot-reload
  - vendor:/var/www/vendor  # named volume riêng
```

**Tại sao dùng named volume cho vendor?**

```
Host filesystem:
  /project/vendor/  ← thường rỗng hoặc không đồng bộ

Container:
  /var/www/vendor/  ← composer install vào đây

Nếu dùng bind mount (.:/var/www):
  → host /vendor rỗng sẽ override container /vendor → mất packages!

Giải pháp: named volume "vendor" tách biệt hoàn toàn khỏi host
  → vendor sống trong Docker volume, không bị ảnh hưởng bởi host
```

### 4. PHP Extensions được cài

| Extension | Lý do |
|---|---|
| `pdo_pgsql` | Kết nối PostgreSQL |
| `mbstring` | Xử lý string UTF-8 (Laravel core) |
| `intl` | Internationalization |
| `zip` | Composer cần để unzip packages |
| `opcache` | Cache bytecode PHP → nhanh hơn |
| `pcntl` | Process control → Laravel Queue worker cần |
| `redis` (PECL) | Cache, Queue, Session qua Redis |

---

## Chạy dev

```bash
# Lần đầu
docker compose up -d --build

# Xem log
docker compose logs -f app

# Vào trong container
docker compose exec app bash

# Chạy artisan
docker compose exec app php artisan migrate
docker compose exec app php artisan make:controller UserController

# Dừng
docker compose down

# Dừng + xóa volumes (reset DB)
docker compose down -v
```

**Lưu ý:** Lần đầu chạy sẽ mất 1-2 phút để composer install. Các lần sau nhanh hơn vì vendor đã có trong named volume.

---

## Chuyển sang Production + CI/CD

### Bước 1 — Thêm stage `production` vào Dockerfile

Thêm vào cuối `Dockerfile`:

```dockerfile
# ============================================================
# PRODUCTION STAGE
# ============================================================
FROM base AS production

ENV APP_ENV=production \
    APP_DEBUG=false

WORKDIR /var/www

# Copy source code VÀO image (không mount volume như dev)
COPY . .

# Install dependencies — không có dev packages
RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

# Cache Laravel config/routes/views để tăng performance
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Set quyền đúng
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
```

> ⚠️ Stage production dùng `php-fpm`, cần thêm nginx. Xem Bước 3.

### Bước 2 — Thêm nginx vào docker-compose (production)

Tạo file `docker-compose.prod.yml`:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
      target: production    # ← build đến stage production
    image: your-registry/nhm-app:${TAG:-latest}
    # KHÔNG có volumes mount — code đã nằm trong image

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - app
```

### Bước 3 — Tạo nginx.conf tối giản

```nginx
server {
    listen 80;
    root /var/www/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Bước 4 — CI/CD pipeline (GitHub Actions)

Tạo `.github/workflows/deploy.yml`:

```yaml
name: Build & Deploy

on:
  push:
    branches: [main]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Build production image
        run: |
          docker build \
            --target production \
            --cache-from your-registry/nhm-app:latest \
            -t your-registry/nhm-app:${{ github.sha }} \
            -t your-registry/nhm-app:latest \
            .

      - name: Push image
        run: docker push your-registry/nhm-app --all-tags

      - name: Deploy
        run: |
          # ssh vào server, pull image mới, docker compose up
          ssh user@server "
            docker compose -f docker-compose.prod.yml pull &&
            docker compose -f docker-compose.prod.yml up -d --no-build
          "
```

**`--cache-from`** là key để CI/CD nhanh: pull image cũ trước, dùng làm cache → chỉ build lại layer thay đổi.

---

## So sánh dev vs production

| | Dev | Production |
|---|---|---|
| **Stage** | `dev` | `production` |
| **Source code** | Mount volume (hot-reload) | COPY vào image |
| **vendor** | Named volume + `composer install` khi start | Baked vào image (`--no-dev`) |
| **PHP server** | `artisan serve` | `php-fpm` + nginx |
| **APP_DEBUG** | `true` | `false` |
| **Artisan cache** | Không (vì code thay đổi liên tục) | `config:cache`, `route:cache`, `view:cache` |
| **Image size** | ~120MB | ~90MB (không có dev deps) |

---

## Troubleshooting

**Vendor bị mất sau khi down:**

```bash
# vendor nằm trong named volume, không mất khi down
# Chỉ mất khi chạy: docker compose down -v
# Fix: chạy lại, composer sẽ tự install
docker compose up -d
```

**Lỗi permission storage:**

```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
```

**Postgres chưa kịp ready khi app start:**

Đã xử lý bằng `healthcheck` + `condition: service_healthy` trong compose.

**Xem size image:**

```bash
docker images nhm-app
docker history nhm-app:latest
```
