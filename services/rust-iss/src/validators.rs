use serde::Deserialize;
use validator::Validate;

/// Query параметры для ISS history endpoint
#[derive(Debug, Deserialize, Validate)]
pub struct IssHistoryQuery {
    /// Максимальное количество точек для возврата (1-1000)
    #[validate(range(min = 1, max = 1000))]
    pub limit: Option<i64>,
}

impl IssHistoryQuery {
    /// Получить значение limit с дефолтом 100
    pub fn get_limit_or_default(&self) -> i64 {
        self.limit.unwrap_or(100)
    }
}

/// Query параметры для OSDR list endpoint
#[derive(Debug, Deserialize, Validate)]
pub struct OsdrListQuery {
    /// Лимит элементов (1-100)
    #[validate(range(min = 1, max = 100))]
    pub limit: Option<i64>,
}

impl OsdrListQuery {
    pub fn get_limit_or_default(&self) -> i64 {
        self.limit.unwrap_or(20)
    }
}

/// Query параметры для Space endpoints
#[derive(Debug, Deserialize, Validate)]
pub struct SpaceSourceQuery {
    /// Источник данных (apod, neo, donki, spacex)
    #[validate(length(min = 1, max = 20))]
    pub src: String,
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_iss_history_query_valid() {
        let query = IssHistoryQuery { limit: Some(50) };
        assert!(query.validate().is_ok());
        assert_eq!(query.get_limit_or_default(), 50);
    }

    #[test]
    fn test_iss_history_query_default() {
        let query = IssHistoryQuery { limit: None };
        assert!(query.validate().is_ok());
        assert_eq!(query.get_limit_or_default(), 100);
    }

    #[test]
    fn test_iss_history_query_invalid() {
        let query = IssHistoryQuery { limit: Some(2000) };
        assert!(query.validate().is_err());
    }

    #[test]
    fn test_osdr_list_query_valid() {
        let query = OsdrListQuery { limit: Some(50) };
        assert!(query.validate().is_ok());
    }
}
