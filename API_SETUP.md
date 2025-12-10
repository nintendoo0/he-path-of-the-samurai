# Настройка внешних API

## Astronomy API (AstronomyAPI.com)

Для работы блока "Астрономические события" требуется бесплатная регистрация на https://astronomyapi.com

### Шаги:

1. **Регистрация**
   - Перейдите на https://astronomyapi.com
   - Создайте бесплатный аккаунт
   - Бесплатный план: 1000 запросов/месяц

2. **Получение ключей**
   - После регистрации получите:
     - Application ID
     - Application Secret

3. **Настройка проекта**
   - Откройте файл `.env` в корне проекта
   - Найдите строки:
     ```env
     ASTRO_APP_ID=your_app_id_here
     ASTRO_APP_SECRET=your_app_secret_here
     ```
   - Замените на ваши реальные ключи:
     ```env
     ASTRO_APP_ID=abc-123-def-456
     ASTRO_APP_SECRET=xyz789секретный_ключ
     ```

4. **Перезапуск**
   ```powershell
   docker-compose restart php nginx
   ```

5. **Проверка**
   - Откройте http://localhost:8080/dashboard
   - Блок "Астрономические события" должен показывать реальные данные

### Альтернатива (без регистрации)

Если не хотите регистрироваться, блок будет показывать заглушку с сообщением об отсутствии ключей.

## NASA API

NASA API ключ уже настроен в `.env`:
```env
NASA_API_KEY=EbF3smROMxhjP1xX9mXxoNTwHyHdlgbQ48YGAebz
```

Это демо-ключ с ограниченным лимитом. Для production:
1. Зарегистрируйтесь на https://api.nasa.gov
2. Получите свой ключ
3. Обновите `NASA_API_KEY` в `.env`

## JWST API

Работает без регистрации! Данные загружаются автоматически.

## ISS Tracking

Работает без регистрации! Использует https://wheretheiss.at

---

**Обновлено:** 10 декабря 2025 г.
