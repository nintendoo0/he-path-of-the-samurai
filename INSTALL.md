# Инструкция по запуску проекта

## Предварительные требования

- Docker и Docker Compose
- Минимум 4GB RAM для контейнеров

## Запуск

1. **Запустите Docker Desktop** (если используете Windows/Mac)

2. **Запустите все сервисы:**
```powershell
cd d:\he-path-of-the-samurai
docker-compose up --build
```

3. **Проверьте логи:**
```powershell
docker-compose logs -f
```

## Доступ к сервисам

- **Web Dashboard**: http://localhost:8080/dashboard
- **ISS View**: http://localhost:8080/iss
- **OSDR View**: http://localhost:8080/osdr
- **Rust API**: http://localhost:8081/health
- **PostgreSQL**: localhost:5432 (monouser/monopass)

## API Endpoints (Rust)

### Health Check
```bash
curl http://localhost:8081/health
```

### ISS Data
```bash
curl http://localhost:8081/last
curl http://localhost:8081/iss/trend
```

### OSDR
```bash
curl http://localhost:8081/osdr/list
curl http://localhost:8081/osdr/sync
```

### Space Cache
```bash
curl http://localhost:8081/space/apod/latest
curl http://localhost:8081/space/summary
curl "http://localhost:8081/space/refresh?src=apod,neo,spacex"
```

## API Endpoints (Laravel через Nginx)

### ISS (прокси к Rust)
```bash
curl http://localhost:8080/api/iss/last
curl http://localhost:8080/api/iss/trend
```

### JWST Gallery
```bash
curl "http://localhost:8080/api/jwst/feed?source=jpg&page=1&perPage=12"
```

### Astronomy Events
```bash
curl "http://localhost:8080/api/astro/events?lat=55.7558&lon=37.6176&days=7"
```

## Проверка здоровья сервисов

```powershell
# Rust API
curl http://localhost:8081/health

# База данных (из контейнера)
docker-compose exec db psql -U monouser -d monolith -c "SELECT count(*) FROM iss_fetch_log;"

# PHP (проверка Laravel)
docker-compose exec php php /var/www/html/artisan --version

# Python Legacy (логи)
docker-compose logs python_legacy
```

## Остановка

```powershell
# Остановить с сохранением данных
docker-compose stop

# Остановить и удалить контейнеры
docker-compose down

# Остановить и удалить всё (включая volumes)
docker-compose down -v
```

## Решение проблем

### Ошибки компиляции Rust
```powershell
cd services/rust-iss
cargo check
cargo build
```

### Проблемы с PHP/Laravel
```powershell
docker-compose exec php composer install --working-dir=/var/www/html
docker-compose exec php php /var/www/html/artisan config:cache
```

### Проблемы с БД
```powershell
# Пересоздать базу
docker-compose down -v
docker-compose up db
```

### Просмотр логов конкретного сервиса
```powershell
docker-compose logs -f rust_iss
docker-compose logs -f php
docker-compose logs -f python_legacy
docker-compose logs -f db
```

## Переменные окружения

Основные переменные находятся в `.env`:
- `NASA_API_KEY` - ключ NASA API
- `JWST_API_KEY`, `JWST_EMAIL` - доступ к JWST API
- `ASTRO_APP_ID`, `ASTRO_APP_SECRET` - доступ к Astronomy API
- Интервалы обновления данных (`*_EVERY_SECONDS`)

## Мониторинг

### Использование ресурсов
```powershell
docker stats
```

### Состояние контейнеров
```powershell
docker-compose ps
```

### Подключение к контейнеру
```powershell
docker-compose exec rust_iss sh
docker-compose exec php bash
docker-compose exec db psql -U monouser -d monolith
```

## Тестирование

### Проверка всех endpoint'ов
```powershell
# Rust health
curl http://localhost:8081/health

# Laravel dashboard
curl http://localhost:8080/dashboard

# API responses
curl http://localhost:8081/last
curl http://localhost:8080/api/iss/last
```
