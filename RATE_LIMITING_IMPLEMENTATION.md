# 🚦 Rate Limiting & Validation Implementation

## ✅ Реализовано

### 1. Request Validation (validator crate)

Добавлены структуры валидации для всех endpoint'ов:

#### `validators.rs`

```rust
use serde::Deserialize;
use validator::Validate;

#[derive(Debug, Deserialize, Validate)]
pub struct IssHistoryQuery {
    #[validate(range(min = 1, max = 1000))]
    pub limit: Option<i64>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct OsdrListQuery {
    #[validate(range(min = 1, max = 100))]
    pub limit: Option<i64>,
}
```

**Преимущества:**
- ✅ Проверка диапазонов (1-1000 для ISS, 1-100 для OSDR)
- ✅ Type-safe валидация на уровне компиляции
- ✅ Автоматическая конвертация `Option<T>`
- ✅ Понятные сообщения об ошибках

**Использование в handlers:**

```rust
pub async fn iss_history(
    Query(query): Query<IssHistoryQuery>,
    State(state): State<AppState>,
) -> Result<Json<Value>, ApiError> {
    query.validate()
        .map_err(|e| ApiError::Internal(format!("Validation error: {}", e)))?;
    
    let limit = query.get_limit_or_default();
    let points = state.iss_service.get_history(limit).await?;
    Ok(Json(serde_json::json!({ "points": points })))
}
```

**Тестирование:**

```rust
#[test]
fn test_iss_history_query_invalid() {
    let query = IssHistoryQuery { limit: Some(2000) };
    assert!(query.validate().is_err());  // ❌ Слишком большой лимит
}
```

---

### 2. Middleware Layer (tower + tower-http)

Добавлены в `main.rs`:

```rust
use tower::ServiceBuilder;
use tower_http::{trace::TraceLayer, cors::{CorsLayer, Any}};
use std::time::Duration;

let app = Router::new()
    // ... routes ...
    .layer(
        ServiceBuilder::new()
            .layer(TraceLayer::new_for_http())  // HTTP tracing
            .layer(cors)                         // CORS
            .timeout(Duration::from_secs(30))    // Global timeout
    );
```

**Что добавлено:**

1. **TraceLayer** - HTTP request/response логирование
   - Автоматически логирует все запросы
   - Записывает latency, status codes, errors
   - Интегрируется с `tracing` crate

2. **CORS Layer** - Cross-Origin Resource Sharing
   - Разрешает запросы с любых доменов
   - Production: настроить конкретные домены
   - Поддержка preflight requests

3. **Global Timeout** - 30 секунд для всех endpoint'ов
   - Защита от hanging requests
   - Автоматический возврат 504 Gateway Timeout
   - Предотвращает зависание workers

---

### 3. Rate Limiting Options

#### Вариант А: tower::limit::RateLimitLayer (Простой)

**Добавить в `Cargo.toml`:**
```toml
tower = { version = "0.4", features = ["limit"] }
```

**Использование:**
```rust
use tower::limit::RateLimitLayer;

let app = Router::new()
    .route("/api/iss/last", get(iss_last))
    .layer(RateLimitLayer::new(
        10,                              // requests
        Duration::from_secs(1)           // per second
    ));
```

**Плюсы:**
- ✅ Простая настройка
- ✅ Встроено в tower

**Минусы:**
- ❌ Глобальный лимит для всех клиентов
- ❌ Нет индивидуальных лимитов по IP

---

#### Вариант Б: tower-governor (Рекомендуется)

**Добавить в `Cargo.toml`:**
```toml
tower-governor = "0.3"
```

**Пример реализации:**

```rust
use tower_governor::{GovernorConfigBuilder, governor::GovernorConfig};
use std::net::IpAddr;

// В main.rs
let governor_conf = Box::new(
    GovernorConfigBuilder::default()
        .per_second(10)           // 10 req/sec per IP
        .burst_size(20)           // Burst до 20 запросов
        .finish()
        .unwrap(),
);

let app = Router::new()
    .route("/api/iss/last", get(iss_last))
    .layer(GovernorLayer {
        config: Box::leak(governor_conf),
    });
```

**Плюсы:**
- ✅ Индивидуальный лимит по IP адресу
- ✅ Burst support (пачки запросов)
- ✅ Настраиваемые окна времени
- ✅ Автоматический возврат 429 Too Many Requests

**Минусы:**
- ⚠️ Требует дополнительную зависимость
- ⚠️ Больше настроек

---

#### Вариант В: Redis-based Rate Limiting (Production)

Для production с несколькими серверами нужен распределённый rate limiting:

**Добавить в `Cargo.toml`:**
```toml
redis = { version = "0.24", features = ["tokio-comp", "connection-manager"] }
```

**Реализация:**

```rust
use redis::{Client, AsyncCommands};

pub struct RedisRateLimiter {
    client: Client,
}

impl RedisRateLimiter {
    pub async fn check_rate_limit(&self, ip: &str, limit: u32, window: u64) -> bool {
        let mut conn = self.client.get_async_connection().await.unwrap();
        let key = format!("rate_limit:{}", ip);
        
        // Increment counter with expiration
        let count: u32 = conn.incr(&key, 1).await.unwrap();
        if count == 1 {
            conn.expire(&key, window).await.unwrap();
        }
        
        count <= limit
    }
}
```

**Плюсы:**
- ✅ Работает с multiple instances
- ✅ Shared state между серверами
- ✅ Точный контроль

**Минусы:**
- ❌ Требует Redis
- ❌ Дополнительный network hop

---

## 🎯 Рекомендуемая Конфигурация

### Для текущего проекта (Single Instance):

```rust
use tower_governor::{governor::GovernorConfigBuilder, GovernorLayer};

// В main.rs после создания Router
let governor_conf = Box::new(
    GovernorConfigBuilder::default()
        .per_second(10)    // 10 requests per second
        .burst_size(20)    // Allow bursts up to 20
        .finish()
        .unwrap(),
);

let app = Router::new()
    .route("/health", get(health))
    // ISS endpoints
    .route("/last", get(iss_last))
    .route("/iss/history", get(iss_history))
    // ... остальные routes ...
    .with_state(state)
    .layer(
        ServiceBuilder::new()
            .layer(TraceLayer::new_for_http())
            .layer(GovernorLayer { 
                config: Box::leak(governor_conf) 
            })
            .layer(cors)
            .timeout(Duration::from_secs(30))
    );
```

### Разные лимиты для разных endpoint'ов:

```rust
let public_routes = Router::new()
    .route("/health", get(health))
    .route("/last", get(iss_last))
    .layer(GovernorLayer { config: public_limit });  // 100 req/sec

let admin_routes = Router::new()
    .route("/fetch", get(iss_trigger))
    .route("/osdr/sync", get(osdr_sync))
    .layer(GovernorLayer { config: admin_limit });   // 10 req/sec

let app = Router::new()
    .merge(public_routes)
    .merge(admin_routes)
    .with_state(state);
```

---

## 📊 Тестирование Rate Limiting

### Ручное тестирование с curl:

```bash
# Быстрые запросы для проверки лимита
for i in {1..30}; do
  curl http://localhost:3000/last
  echo "Request $i"
done

# Ожидаемый результат: первые 20 успешны, остальные 429 Too Many Requests
```

### Автоматизированный тест:

```rust
#[tokio::test]
async fn test_rate_limiting() {
    let app = create_test_app();
    
    // Делаем 30 запросов
    for i in 0..30 {
        let response = app
            .oneshot(Request::builder()
                .uri("/last")
                .body(Body::empty())
                .unwrap())
            .await
            .unwrap();
        
        if i < 20 {
            assert_eq!(response.status(), StatusCode::OK);
        } else {
            assert_eq!(response.status(), StatusCode::TOO_MANY_REQUESTS);
        }
    }
}
```

---

## 🔒 Security Best Practices

### 1. Различные лимиты для разных уровней доступа

```rust
enum RateLimit {
    Public,   // 10 req/sec
    Auth,     // 100 req/sec
    Admin,    // 1000 req/sec
}
```

### 2. IP Whitelist для внутренних сервисов

```rust
fn is_internal_ip(ip: &IpAddr) -> bool {
    match ip {
        IpAddr::V4(v4) => v4.is_loopback() || v4.is_private(),
        IpAddr::V6(v6) => v6.is_loopback(),
    }
}

// Skip rate limiting for internal IPs
if !is_internal_ip(&client_ip) {
    check_rate_limit(&client_ip).await?;
}
```

### 3. Graceful Error Messages

```rust
impl IntoResponse for ApiError {
    fn into_response(self) -> Response {
        match self {
            ApiError::RateLimit => (
                StatusCode::TOO_MANY_REQUESTS,
                Json(json!({
                    "error": "Rate limit exceeded",
                    "message": "Too many requests, please try again later",
                    "retry_after": 60
                }))
            ).into_response(),
            // ...
        }
    }
}
```

---

## 📈 Monitoring

### Prometheus Metrics (Опционально)

```rust
use prometheus::{IntCounter, register_int_counter};

lazy_static! {
    static ref RATE_LIMIT_HITS: IntCounter = 
        register_int_counter!("rate_limit_hits_total", "Rate limit hits").unwrap();
}

// В middleware
if rate_limited {
    RATE_LIMIT_HITS.inc();
    return Err(ApiError::RateLimit);
}
```

---

## ✅ Чек-лист Реализации

- [x] Добавить `tower` и `tower-http` в dependencies
- [x] Добавить `validator` в dependencies
- [x] Создать модуль `validators.rs`
- [x] Реализовать структуры валидации
- [x] Добавить TraceLayer для HTTP logging
- [x] Добавить CORS layer
- [x] Добавить global timeout
- [x] Обновить handlers с валидацией
- [ ] Добавить tower-governor для per-IP rate limiting
- [ ] Настроить разные лимиты для endpoint'ов
- [ ] Добавить тесты для rate limiting
- [ ] Добавить metrics для monitoring

---

## 🚀 Следующие Шаги

1. **Сейчас работает:**
   - ✅ Request validation с validator crate
   - ✅ HTTP tracing с TraceLayer
   - ✅ CORS настроен
   - ✅ Global timeout 30 секунд

2. **Нужно добавить tower-governor:**
   ```bash
   # В services/rust-iss/
   cargo add tower-governor
   ```

3. **Обновить main.rs с GovernorLayer**

4. **Протестировать с нагрузкой**

---

**Статус:** ⚠️ В процессе (70% готово)  
**Приоритет:** Высокий  
**ETA:** 1 день для полной реализации
