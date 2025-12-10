use redis::{aio::ConnectionManager, AsyncCommands, Client, RedisError};
use serde::{de::DeserializeOwned, Serialize};
use tracing::{error, info};

/// Redis Cache Client для кеширования внешних API запросов
#[derive(Clone)]
pub struct RedisCache {
    client: ConnectionManager,
}

impl RedisCache {
    /// Создать новый Redis клиент
    pub async fn new(redis_url: &str) -> Result<Self, RedisError> {
        let client = Client::open(redis_url)?;
        let manager = ConnectionManager::new(client).await?;
        
        info!("Redis connection established");
        
        Ok(Self { client: manager })
    }

    /// Получить значение из кеша
    /// 
    /// # Arguments
    /// * `key` - Ключ кеша
    /// 
    /// # Returns
    /// * `Some(T)` - Если найдено и успешно десериализовано
    /// * `None` - Если не найдено или ошибка
    pub async fn get<T>(&mut self, key: &str) -> Option<T>
    where
        T: DeserializeOwned,
    {
        match self.client.get::<&str, String>(key).await {
            Ok(value) => match serde_json::from_str::<T>(&value) {
                Ok(data) => {
                    info!("Cache HIT for key: {}", key);
                    Some(data)
                }
                Err(e) => {
                    error!("Failed to deserialize cache value for {}: {}", key, e);
                    None
                }
            },
            Err(e) => {
                if e.kind() != redis::ErrorKind::TypeError {
                    error!("Redis GET error for {}: {}", key, e);
                }
                None
            }
        }
    }

    /// Сохранить значение в кеш с TTL
    /// 
    /// # Arguments
    /// * `key` - Ключ кеша
    /// * `value` - Значение для сохранения
    /// * `ttl_seconds` - Время жизни в секундах
    pub async fn set<T>(&mut self, key: &str, value: &T, ttl_seconds: usize) -> Result<(), RedisError>
    where
        T: Serialize,
    {
        let json = serde_json::to_string(value)
            .map_err(|e| RedisError::from((redis::ErrorKind::Serialize, "JSON serialize error", e.to_string())))?;

        self.client
            .set_ex::<&str, String, ()>(key, json, ttl_seconds)
            .await?;

        info!("Cache SET for key: {} (TTL: {}s)", key, ttl_seconds);
        Ok(())
    }

    /// Удалить ключ из кеша
    pub async fn delete(&mut self, key: &str) -> Result<(), RedisError> {
        self.client.del::<&str, ()>(key).await?;
        info!("Cache DEL for key: {}", key);
        Ok(())
    }

    /// Проверить существование ключа
    pub async fn exists(&mut self, key: &str) -> Result<bool, RedisError> {
        self.client.exists(key).await
    }

    /// Получить TTL ключа (в секундах)
    /// Возвращает -1 если ключ не имеет TTL, -2 если ключ не существует
    pub async fn ttl(&mut self, key: &str) -> Result<i64, RedisError> {
        self.client.ttl(key).await
    }

    /// Инкремент счётчика для rate limiting
    /// 
    /// # Arguments
    /// * `key` - Ключ счётчика
    /// * `window_seconds` - Окно времени для счётчика
    /// 
    /// # Returns
    /// Текущее значение счётчика
    pub async fn incr_with_expiry(&mut self, key: &str, window_seconds: usize) -> Result<i64, RedisError> {
        let count: i64 = self.client.incr(key, 1).await?;
        
        // Установить TTL только для первого инкремента
        if count == 1 {
            self.client.expire::<&str, ()>(key, window_seconds).await?;
        }
        
        Ok(count)
    }

    /// Получить все ключи по паттерну (для debugging)
    /// ВАЖНО: Не использовать в production на больших данных!
    pub async fn keys(&mut self, pattern: &str) -> Result<Vec<String>, RedisError> {
        self.client.keys(pattern).await
    }

    /// Очистить весь кеш (для testing)
    pub async fn flush_all(&mut self) -> Result<(), RedisError> {
        redis::cmd("FLUSHALL")
            .query_async(&mut self.client)
            .await?;
        info!("Redis cache flushed");
        Ok(())
    }
}

/// Helper функции для генерации ключей кеша
pub mod cache_keys {
    /// Ключ для ISS последней позиции
    pub fn iss_last() -> String {
        "api:iss:last".to_string()
    }

    /// Ключ для ISS истории
    pub fn iss_history(limit: i64) -> String {
        format!("api:iss:history:{}", limit)
    }

    /// Ключ для OSDR списка
    pub fn osdr_list(limit: i64) -> String {
        format!("api:osdr:list:{}", limit)
    }

    /// Ключ для Space API кеша
    pub fn space_latest(source: &str) -> String {
        format!("api:space:{}:latest", source)
    }

    /// Ключ для APOD
    pub fn apod() -> String {
        "api:nasa:apod:latest".to_string()
    }

    /// Ключ для NEO
    pub fn neo() -> String {
        "api:nasa:neo:latest".to_string()
    }

    /// Ключ для rate limiting
    pub fn rate_limit(ip: &str) -> String {
        format!("rate_limit:{}", ip)
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde::{Deserialize, Serialize};

    #[derive(Debug, Serialize, Deserialize, PartialEq)]
    struct TestData {
        id: i32,
        name: String,
    }

    #[tokio::test]
    #[ignore] // Требует запущенный Redis
    async fn test_redis_set_get() {
        let mut cache = RedisCache::new("redis://127.0.0.1:6379")
            .await
            .expect("Failed to connect to Redis");

        let data = TestData {
            id: 42,
            name: "Test".to_string(),
        };

        // Set
        cache.set("test:key", &data, 60).await.unwrap();

        // Get
        let retrieved: Option<TestData> = cache.get("test:key").await;
        assert_eq!(retrieved, Some(data));

        // Cleanup
        cache.delete("test:key").await.unwrap();
    }

    #[test]
    fn test_cache_keys() {
        assert_eq!(cache_keys::iss_last(), "api:iss:last");
        assert_eq!(cache_keys::iss_history(100), "api:iss:history:100");
        assert_eq!(cache_keys::osdr_list(20), "api:osdr:list:20");
        assert_eq!(cache_keys::space_latest("apod"), "api:space:apod:latest");
    }
}
