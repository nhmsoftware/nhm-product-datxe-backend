# Docker Setup — Laravel + PostgreSQL + Redis

> Dev-first. Hướng dẫn từ local đến production.

---

## Cấu trúc file

```
project/
├── Dockerfile            # Single-stage, tối giản cho local dev
├── docker-compose.yml    # Dev only: app + postgres + redis
├── data/
│   └── postgres/         # Dữ liệu DB (bind mount, gitignore)
└── DOCKER.md             # File này
```

---

## Kiến trúc hiện tại (Local Dev)

```
┌──────────────────────────────────────────────────────┐
│  FROM php:8.3-cli-alpine  (~35MB)                    │
│                                                      │
│  ├── Runtime libs: libpq, icu-libs, oniguruma,       │
│  │                 libzip, bash                       │
│  │                                                    │
│  ├── PHP Extensions (compile xong xóa build-deps):   │
│  │   pdo_pgsql, mbstring, intl, zip,                 │
│  │   opcache, pcntl, sockets, redis                  │
│  │                                                    │
│  └── CMD: composer install → php artisan serve :8000 │
└──────────────────────────────────────────────────────┘

docker-compose services:
  app       → php:8.3-cli-alpine (Dockerfile trên)
  postgres  → postgres:16-alpine
  redis     → redis:7-alpine
```

**Tại sao single-stage cho local?**

- Local không cần optimize hay strip dev-deps → multi-stage chỉ làm build chậm hơn
- Source code bind-mount từ host → sửa code không cần rebuild hay restart
- Khi lên server sẽ thêm stage `production` với php-fpm + nginx

---

## Giải thích chi tiết Dockerfile

### Base image

```dockerfile
FROM php:8.3-cli-alpine
```

| Image | Size | Ghi chú |
|---|---|---|
| `php:8.3-apache` | ~450MB | Kéo theo Apache, quá nặng |
| `php:8.3-fpm` | ~140MB | Cần nginx, dùng cho production |
| `php:8.3-cli-alpine` | ~35MB | ✅ Nhẹ nhất, đủ cho local dev |

### Pattern `.build-deps` — giảm size chính

```dockerfile
# Runtime libs — cài riêng, KHÔNG nằm trong .build-deps → giữ lại sau build
RUN apk add --no-cache \
        libpq icu-libs oniguruma libzip bash

# Build tools — gom vào .build-deps, xóa ngay sau khi compile xong
RUN apk add --no-cache --virtual .build-deps \
        ${PHPIZE_DEPS} \
        linux-headers \      # ← cần cho sockets extension trên Alpine
        postgresql-dev icu-dev oniguruma-dev libzip-dev \
    && docker-php-ext-install ... \
    && apk del --purge .build-deps   # gcc, make, headers... biến mất
```

Kết quả: tiết kiệm ~80-100MB so với không dùng pattern này.

> **Lưu ý `linux-headers`:** Extension `sockets` cần `linux/sock_diag.h` — header
> này không có sẵn trên Alpine. Thiếu dòng này build sẽ lỗi:
> `fatal error: linux/sock_diag.h: No such file or directory`

### PHP Extensions được cài

| Extension | Lý do |
|---|---|
| `pdo_pgsql` | Kết nối PostgreSQL |
| `mbstring` | Xử lý string UTF-8 (Laravel core) |
| `intl` | Internationalization |
| `zip` | Composer unzip packages |
| `opcache` | Cache bytecode PHP |
| `pcntl` | Process control — Queue worker cần |
| `sockets` | Socket support |
| `redis` (PECL) | Cache, Queue, Session qua Redis |

### CMD — composer install tự động

```dockerfile
CMD ["sh", "-c", "composer install --no-interaction && php artisan serve --host=0.0.0.0 --port=8000"]
```

- `composer install` chạy mỗi lần container start
- `vendor` nằm trong **named volume** riêng → không bị xóa giữa các lần restart
- Nếu `composer.lock` không đổi, install gần như tức thì (đã có cache)

---

## Giải thích docker-compose.yml

### Tại sao vendor dùng named volume, không dùng bind mount?

```
Vấn đề nếu chỉ dùng bind mount (.:/var/www):

  Host:       /project/vendor/  ← thường rỗng
  Container:  /var/www/vendor/  ← composer install vào đây

  → Docker mount đè host lên container → vendor trong container bị xóa!

Giải pháp: tách vendor ra named volume riêng

  volumes:
    - .:/var/www              # host override container (source code)
    - vendor:/var/www/vendor  # named volume, Docker ưu tiên hơn bind mount bên trên
```

### Tại sao DB dùng bind mount, không dùng named volume?

```yaml
postgres:
  volumes:
    - ./data/postgres:/var/lib/postgresql/data  # bind mount ra host
```

- Named volume bị xóa khi chạy `docker compose down -v` → mất DB
- Bind mount `./data/postgres` tồn tại ngay cả khi `down -v`
- Dễ backup: `tar -czf backup.tar.gz ./data/postgres`
- Dễ inspect: xem thẳng file trên host

### Healthcheck — app đợi DB sẵn sàng

```yaml
depends_on:
  postgres:
    condition: service_healthy   # đợi pg_isready pass mới start app
  redis:
    condition: service_healthy   # đợi redis-cli ping pass
```

Không có healthcheck, app có thể start trước DB → lỗi connection khi migrate.

### DB_PASSWORD bắt buộc

```yaml
POSTGRES_PASSWORD: ${DB_PASSWORD:?Thiếu DB_PASSWORD trong .env}
```

Cú pháp `:?` làm `docker compose up` fail ngay với error rõ ràng nếu quên set password trong `.env`.

---

## Chạy local

```bash
# Chuẩn bị lần đầu
mkdir -p data/postgres
cp .env.example .env          # điền APP_KEY và DB_PASSWORD

# Build và start
docker compose up -d --build

# Xem log
docker compose logs -f app

# Chạy artisan
docker compose exec app php artisan migrate
docker compose exec app php artisan make:controller UserController

# Vào shell container
docker compose exec app bash

# Dừng an toàn — GIỮ toàn bộ data
docker compose down

# Reset hoàn toàn — XÓA vendor + data DB
docker compose down -v && rm -rf ./data
```

> **Lần đầu** mất 1-3 phút để build image và composer install.
> Các lần sau `docker compose up -d` (không `--build`) chỉ mất vài giây.

---

## Troubleshooting

**Lỗi `linux/sock_diag.h` khi build:**
```bash
# Đã fix bằng linux-headers trong .build-deps — nếu vẫn lỗi, thử:
docker compose build --no-cache
```

**Vendor bị mất / lỗi class not found:**
```bash
# Named volume có thể bị corrupt, tạo lại:
docker compose down -v
docker compose up -d --build
```

**Lỗi permission storage:**
```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
```

**Postgres chưa kịp sẵn sàng:**
Đã xử lý bằng healthcheck. Nếu vẫn lỗi, tăng `retries` trong compose.

**Xem size image:**
```bash
docker images nhm-app
docker history nhm-app:latest
```

**Rebuild khi đổi Dockerfile hoặc composer.lock:**
```bash
docker compose build --no-cache
docker compose up -d
```

---

## Lên production (roadmap)

Khi sẵn sàng deploy server, sẽ thêm:

| Thành phần | Thay đổi |
|---|---|
| **Dockerfile** | Thêm stage `production`: php-fpm, `--no-dev`, `artisan optimize` |
| **Nginx** | Container riêng, phục vụ static files + forward PHP đến fpm |
| **Source code** | COPY vào image (không mount volume) |
| **Queue + Scheduler** | Service riêng trong compose |
| **Secrets** | Không dùng `.env` file — dùng Docker secrets hoặc env từ CI/CD |

---

## Tham khảo nhanh

```bash
docker compose up -d --build     # build + start
docker compose down              # dừng, giữ data
docker compose logs -f app       # theo dõi log
docker compose exec app bash     # vào shell
docker compose ps                # trạng thái services
docker images nhm-app            # xem size image
```
