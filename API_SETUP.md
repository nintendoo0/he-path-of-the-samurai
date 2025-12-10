# Настройка внешних API

## AstronomyAPI (опционально)

⚠️ **Важно**: AstronomyAPI является **опциональной** функцией. Сайт полностью работает без неё.

AstronomyAPI предоставляет данные о астрономических событиях (восходы/закаты солнца, луны, фазы луны и т.д.).

### Текущий статус

**Приложение настроено на graceful degradation**: если AstronomyAPI не отвечает или возвращает ошибку, секция астрономических событий просто показывает пустой результат, но **сайт продолжает работать без проблем**.

### ⚠️ Известная проблема с ключами

Если вы видите ошибку `Invalid key=value pair (missing equal-sign) in Authorization header`, это означает что:

1. **Application Secret содержит недопустимые символы** (знак `=`, `/`, `+`)
2. Вы **скопировали неправильное значение** из панели AstronomyAPI

**Решение:** См. подробную инструкцию в файле [ASTRONOMY_API_SETUP.md](ASTRONOMY_API_SETUP.md)

### Правильный формат ключей

- **Application ID**: UUID формат (36 символов)
  - Пример: `4e238a51-a5db-42ba-b850-1909dcb74ce5`
  
- **Application Secret**: HEX строка (только 0-9, a-f)
  - Пример: `f123cff6a029b264b2805dd8b720812376e0584c`
  - **НЕ ДОЛЖНО содержать**: `=`, `/`, `+`, пробелов

### Быстрая настройка

1. Перейдите на https://astronomyapi.com
2. Войдите в Dashboard → Applications
3. Создайте новое приложение (или используйте существующее)
4. **Аккуратно скопируйте** Application ID и Application Secret
5. Откройте `.env` и обновите:

```env
ASTRO_APP_ID=ваш_application_id
ASTRO_APP_SECRET=ваш_application_secret
ASTRO_TIMEOUT=25
```

6. Перезапустите сервисы:

```bash
docker-compose restart php nginx
```

7. Проверьте работу на http://localhost:8080/astronomy

**Подробная инструкция с примерами:** [ASTRONOMY_API_SETUP.md](ASTRONOMY_API_SETUP.md)

**Примечание**: Даже если AstronomyAPI не работает, это не повлияет на функциональность остального сайта.

## NASA API

NASA API уже настроен с демонстрационным ключом:
- **API Key**: `EbF3smROMxhjP1xX9mXxoNTwHyHdlgbQ48YGAebz`
- **Endpoints**: APOD, NEO, DONKI

Для увеличения лимитов запросов можно получить персональный ключ на https://api.nasa.gov

## JWST API

JWST API не требует регистрации:
- **Base URL**: `https://api.jwstapi.com`
- **Status**: Полностью работает

## ISS Tracking

ISS Tracking API не требует регистрации:
- **API**: `https://api.wheretheiss.at/v1/satellites/25544`
- **Status**: Полностью работает

## Статус API

| API | Требуется ключ | Статус | Влияние на сайт |
|-----|----------------|--------|-----------------|
| NASA | Есть (demo) | ✅ Работает | Основные данные |
| JWST | Нет | ✅ Работает | Галерея изображений |
| ISS Tracking | Нет | ✅ Работает | Позиция МКС |
| AstronomyAPI | Требуется | ⚠️ Опционально | Астрономические события |

**Примечание**: Сайт работает полностью даже без AstronomyAPI. Секция астрономических событий просто будет показывать пустой результат.
