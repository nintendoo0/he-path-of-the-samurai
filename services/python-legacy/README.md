# Telemetry Legacy Service (Python)

Переписанный на Python legacy-сервис для генерации телеметрических данных.

## Описание

Сервис периодически генерирует случайные телеметрические данные (напряжение и температура), 
сохраняет их в CSV файл и записывает в PostgreSQL.

## Формат данных

### CSV
- **Поля**: recorded_at, voltage, temp, source_file
- **recorded_at**: временная метка в формате 'YYYY-MM-DD HH:MM:SS'
- **voltage**: напряжение (3.2-12.6В)
- **temp**: температура (-50.0-80.0°C)
- **source_file**: имя CSV файла

### Таблица БД: telemetry_legacy
```sql
CREATE TABLE telemetry_legacy (
    id BIGSERIAL PRIMARY KEY,
    recorded_at TIMESTAMPTZ NOT NULL,
    voltage NUMERIC(6,2) NOT NULL,
    temp NUMERIC(6,2) NOT NULL,
    source_file TEXT NOT NULL
);
```

## Переменные окружения

- `CSV_OUT_DIR` - директория для CSV файлов (по умолчанию: /data/csv)
- `GEN_PERIOD_SEC` - период генерации в секундах (по умолчанию: 300)
- `PGHOST` - хост PostgreSQL (по умолчанию: db)
- `PGPORT` - порт PostgreSQL (по умолчанию: 5432)
- `PGUSER` - пользователь БД (по умолчанию: monouser)
- `PGPASSWORD` - пароль БД (по умолчанию: monopass)
- `PGDATABASE` - имя БД (по умолчанию: monolith)

## Логирование

Все логи выводятся в stdout в структурированном формате.

## Преимущества над Pascal версией

- Структурированное логирование
- Корректная обработка ошибок
- Типизация и документация
- Удобная конфигурация через переменные окружения
- Современный стек Python
