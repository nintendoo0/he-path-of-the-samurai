# 🧪 Тестирование He Path of the Samurai

Этот документ описывает, как запускать тесты для проекта.

## 📋 Оглавление

- [Быстрый старт](#быстрый-старт)
- [Типы тестов](#типы-тестов)
- [Запуск тестов](#запуск-тестов)
- [Тестирование отдельных сервисов](#тестирование-отдельных-сервисов)

---

## 🚀 Быстрый старт

### Запуск всех тестов

```powershell
.\run-tests.ps1
```

### Запуск конкретных тестов

```powershell
# Только API эндпоинты
.\run-tests.ps1 api

# Только Rust сервис
.\run-tests.ps1 rust

# Только PHP сервис
.\run-tests.ps1 php

# Интеграционные тесты
.\run-tests.ps1 integration
```

---

## 📊 Типы тестов

### 1. **Unit тесты**
- Rust: `cargo test` внутри контейнера
- PHP: `php artisan test` (если настроено)

### 2. **API тесты**
- Проверка доступности эндпоинтов
- Проверка HTTP статус кодов
- Базовая валидация ответов

### 3. **Интеграционные тесты**
- Проверка связи между сервисами
- Проверка потока данных (Rust → DB, Python → CSV → PHP)
- Проверка работы базы данных

### 4. **Health checks**
- Проверка доступности всех контейнеров
- Проверка логов на наличие ошибок

---

## 🎯 Запуск тестов

### Все тесты

```powershell
.\run-tests.ps1 all
```

Запускает:
- ✅ Проверку базы данных
- ✅ Rust unit тесты и API
- ✅ PHP syntax check и тесты
- ✅ Python/Pascal service checks
- ✅ API endpoint тесты
- ✅ Интеграционные тесты

### Только тесты API

```powershell
.\run-tests.ps1 api
```

Проверяет доступность:
- `http://localhost:8080/` - Dashboard
- `http://localhost:8081/iss` - ISS Tracker (Rust)
- `http://localhost:8080/telemetry` - Telemetry (PHP + Python/Pascal)
- `http://localhost:8080/astronomy` - Astronomy API
- `http://localhost:8080/osdr` - OSDR API

---

## 🔧 Тестирование отдельных сервисов

### Rust ISS Tracker

```powershell
# Unit тесты
.\run-tests.ps1 rust

# Или напрямую
docker exec rust_iss cargo test

# Health check
curl http://localhost:8081/health

# ISS position
curl http://localhost:8081/iss
```

### PHP Web Service

```powershell
# Все PHP тесты
.\run-tests.ps1 php

# Проверка синтаксиса
docker exec php_web find /opt/laravel-patches -name "*.php" -exec php -l {} \;

# Laravel тесты (если настроены)
docker exec php_web bash -c "cd /opt/laravel-patches && php artisan test"

# Проверка веб-интерфейса
curl http://localhost:8080
```

### Python Legacy Service

```powershell
# Проверка логов
.\run-tests.ps1 python

# Или напрямую
docker logs python_legacy --tail 50

# Проверка генерации данных
docker exec iss_db psql -U monouser -d monolith -c "SELECT * FROM telemetry_legacy ORDER BY id DESC LIMIT 5;"
```

### Pascal Legacy Service

```powershell
# Проверка логов
.\run-tests.ps1 pascal

# Или напрямую
docker logs pascal_legacy --tail 50
```

### База данных

```powershell
# Проверка подключения
docker exec iss_db pg_isready -U monouser -d monolith

# Список таблиц
docker exec iss_db psql -U monouser -d monolith -c "\dt"

# Проверка данных ISS
docker exec iss_db psql -U monouser -d monolith -c "SELECT COUNT(*) FROM iss_fetch_log;"

# Проверка данных телеметрии
docker exec iss_db psql -U monouser -d monolith -c "SELECT COUNT(*) FROM telemetry_legacy;"
```

---

## 🐛 Отладка неудачных тестов

### Если тесты не проходят:

1. **Проверьте, что все контейнеры запущены:**
   ```powershell
   docker-compose ps
   ```

2. **Проверьте логи проблемного сервиса:**
   ```powershell
   docker logs <container_name> --tail 50
   ```

3. **Перезапустите проблемный сервис:**
   ```powershell
   docker-compose restart <service_name>
   ```

4. **Пересоберите контейнеры (если изменился код):**
   ```powershell
   docker-compose up -d --build <service_name>
   ```

---

## 📝 Добавление новых тестов

### Для Rust

Добавьте тесты в `services/rust-iss/src/`:

```rust
#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_example() {
        assert_eq!(2 + 2, 4);
    }
}
```

### Для PHP

Создайте тесты в `services/php-web/laravel-patches/tests/`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_homepage_loads()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
```

---

## 🔍 Мониторинг во время разработки

### Следите за логами в реальном времени:

```powershell
# Все сервисы
docker-compose logs -f

# Конкретный сервис
docker-compose logs -f rust_iss
docker-compose logs -f python_legacy
docker-compose logs -f php_web
```

### Проверка здоровья системы:

```powershell
# Краткий статус
docker-compose ps

# Детальная информация
docker stats
```

---

## 📚 Дополнительные ресурсы

- [QUICKSTART.md](QUICKSTART.md) - Быстрый старт проекта
- [ARCHITECTURE_AUDIT.md](ARCHITECTURE_AUDIT.md) - Архитектура проекта
- [INSTALL.md](INSTALL.md) - Полная инструкция по установке

---

**Создано:** 17 декабря 2025  
**Проект:** He Path of the Samurai - Учебный полиглотный монолит
