@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>🌟 Астрономические события</h2>
  </div>

  <!-- Форма поиска -->
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <form id="astroForm" class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label small text-muted">Широта</label>
              <input type="number" class="form-control" name="lat" value="55.7558" step="0.0001" min="-90" max="90">
            </div>
            <div class="col-md-3">
              <label class="form-label small text-muted">Долгота</label>
              <input type="number" class="form-control" name="lon" value="37.6176" step="0.0001" min="-180" max="180">
            </div>
            <div class="col-md-2">
              <label class="form-label small text-muted">Дней</label>
              <select class="form-select" name="days">
                <option value="3">3 дня</option>
                <option value="7" selected>7 дней</option>
                <option value="14">14 дней</option>
                <option value="30">30 дней</option>
                <option value="360">360 дней</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary w-100">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search me-1" viewBox="0 0 16 16">
                  <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                </svg>
                Найти события
              </button>
            </div>
            <div class="col-md-2">
              <button type="button" class="btn btn-outline-secondary w-100" id="useCurrentLocation">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-geo-alt me-1" viewBox="0 0 16 16">
                  <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A32 32 0 0 1 8 14.58a32 32 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10"/>
                  <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4m0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                </svg>
                Моё
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Загрузка -->
  <div id="loadingSpinner" class="text-center py-5" style="display:none;">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Загрузка...</span>
    </div>
    <p class="mt-3 text-muted">Загрузка астрономических событий...</p>
  </div>

  <!-- Результаты -->
  <div id="astroResults"></div>

  <!-- График позиций небесных тел -->
  <div id="astroChartContainer" style="display:none;">
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0">📊 График движения небесных тел</h5>
      </div>
      <div class="card-body">
        <canvas id="astroChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Информация -->
  <div class="row g-3 mt-2">
    <div class="col-md-6">
      <div class="card shadow-sm border-info">
        <div class="card-body">
          <h5 class="card-title text-info">ℹ️ О сервисе</h5>
          <p class="mb-1 small">
            <strong>Astronomy Events</strong> предоставляет данные о небесных событиях: восходах и заходах Солнца, длительности светового дня и других астрономических явлениях.
          </p>
          <p class="mb-0 small text-muted">
            Данные рассчитываются для указанных координат и временного диапазона с использованием <strong>Open-Meteo API</strong> (бесплатный сервис).
          </p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card shadow-sm border-success">
        <div class="card-body">
          <h5 class="card-title text-success">✅ Статус сервиса</h5>
          <p class="mb-1 small">
            <strong>Open-Meteo</strong> - бесплатный метеорологический API без требования ключей. Данные обновляются ежедневно и включают точные астрономические расчёты.
          </p>
          <p class="mb-0 small">
            <a href="https://open-meteo.com/" target="_blank" class="text-decoration-none">Подробнее об Open-Meteo →</a> | 
            <a href="/dashboard" class="text-decoration-none">← Dashboard</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('astroForm');
  const results = document.getElementById('astroResults');
  const spinner = document.getElementById('loadingSpinner');
  
  // Автозагрузка при открытии страницы
  loadEvents(55.7558, 37.6176, 7);
  
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);
    const lat = parseFloat(formData.get('lat'));
    const lon = parseFloat(formData.get('lon'));
    const days = parseInt(formData.get('days'));
    loadEvents(lat, lon, days);
  });
  
  document.getElementById('useCurrentLocation').addEventListener('click', function() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position) {
        form.lat.value = position.coords.latitude.toFixed(4);
        form.lon.value = position.coords.longitude.toFixed(4);
      });
    } else {
      alert('Геолокация не поддерживается вашим браузером');
    }
  });
  
  function loadEvents(lat, lon, days) {
    spinner.style.display = 'block';
    results.innerHTML = '';
    
    fetch(`/api/astro/events?lat=${lat}&lon=${lon}&days=${days}`)
      .then(res => res.json())
      .then(data => {
        spinner.style.display = 'none';
        displayResults(data, lat, lon, days);
      })
      .catch(err => {
        spinner.style.display = 'none';
        results.innerHTML = `
          <div class="alert alert-danger">
            <h5>Ошибка загрузки данных</h5>
            <p>Не удалось загрузить астрономические события. Возможные причины:</p>
            <ul>
              <li>API ключи не настроены</li>
              <li>Превышен лимит запросов</li>
              <li>Временная недоступность сервиса</li>
            </ul>
          </div>
        `;
      });
  }
    function displayResults(data, lat, lon, days) {
    // Проверка на ошибку валидации
    if (data && data.error && data.message) {
      results.innerHTML = `
        <div class="card shadow-sm border-danger">
          <div class="card-body text-center py-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-geo-alt-fill text-danger mb-3" viewBox="0 0 16 16">
              <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
            </svg>
            <h4 class="text-danger">⚠️ Некорректные координаты</h4>
            <p class="text-muted mb-3">
              <strong>Широта:</strong> ${lat.toFixed(4)}°, <strong>Долгота:</strong> ${lon.toFixed(4)}°
            </p>
            <div class="alert alert-warning mb-3">
              <strong>Причина:</strong> ${data.message}
            </div>
            <p class="small text-muted mb-0">
              💡 <strong>Совет:</strong> Используйте кнопку "Моё" для автоопределения координат или введите координаты крупного города.<br>
              Примеры: Москва (55.7558, 37.6176), Санкт-Петербург (59.9343, 30.3351), Лондон (51.5074, -0.1278)
            </p>
          </div>
        </div>
      `;
      document.getElementById('astroChartContainer').style.display = 'none';
      return;
    }
    
    // Проверяем наличие данных в таблице
    const hasData = data && data.table && data.table.rows && data.table.rows.length > 0;
    
    if (!hasData) {
      results.innerHTML = `
        <div class="card shadow-sm">
          <div class="card-body text-center py-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-calendar2-x text-muted mb-3" viewBox="0 0 16 16">
              <path d="M6.146 8.146a.5.5 0 0 1 .708 0L8 9.293l1.146-1.147a.5.5 0 1 1 .708.708L8.707 10l1.147 1.146a.5.5 0 0 1-.708.708L8 10.707l-1.146 1.147a.5.5 0 0 1-.708-.708L7.293 10 6.146 8.854a.5.5 0 0 1 0-.708"/>
              <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M2 2a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/>
              <path d="M2.5 4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5z"/>
            </svg>
            <h4 class="text-muted">События не найдены</h4>
            <p class="text-muted mb-3">
              Для координат <strong>${lat.toFixed(4)}°, ${lon.toFixed(4)}°</strong> 
              на ближайшие <strong>${days} дней</strong> события не обнаружены.
            </p>
            <p class="small text-muted mb-0">
              Попробуйте изменить параметры поиска или проверьте настройки AstronomyAPI.
            </p>
          </div>
        </div>
      `;
      document.getElementById('astroChartContainer').style.display = 'none';
      return;
    }
    
    // Отображаем заголовок с координатами
    let html = `
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            📍 Координаты: ${lat.toFixed(4)}°, ${lon.toFixed(4)}° | 
            📅 Период: ${days} дней
          </h5>
        </div>
      </div>
    `;
    
    // Отображаем таблицу с данными
    html += `
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover table-striped">
              <thead class="table-light">
                <tr>
                  ${data.table.header.map(h => `<th>${h}</th>`).join('')}
                </tr>
              </thead>
              <tbody>
                ${data.table.rows.map(row => `
                  <tr>
                    ${row.cells.map(cell => `<td>${cell.value || '-'}</td>`).join('')}
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
          ${data.source ? `
            <div class="alert alert-info mb-0 mt-3">
              <small><strong>Источник данных:</strong> ${data.source}</small>
            </div>
          ` : ''}
        </div>
      </div>
    `;
    
    results.innerHTML = html;
    
    // Строим график
    buildChart(data);
  }
  
  let chartInstance = null;
  
  function buildChart(data) {
    console.log('buildChart called with data:', data);
    
    const chartContainer = document.getElementById('astroChartContainer');
    const canvas = document.getElementById('astroChart');
    
    if (!data || !data.table || !data.table.rows || data.table.rows.length === 0) {
      console.warn('No chart data available');
      chartContainer.style.display = 'none';
      return;
    }
    
    chartContainer.style.display = 'block';
    console.log('Chart container displayed');
    
    // Уничтожаем предыдущий график
    if (chartInstance) {
      chartInstance.destroy();
      console.log('Previous chart destroyed');
    }
    
    // Группируем данные по небесным телам
    const bodies = {};
    data.table.rows.forEach(row => {
      const date = row.cells[0]?.value || '';
      const body = row.cells[1]?.value || '';
      const azimuth = parseFloat((row.cells[2]?.value || '0').replace('°', ''));
      const altitude = parseFloat((row.cells[3]?.value || '0').replace('°', ''));
      
      if (!bodies[body]) {
        bodies[body] = {
          dates: [],
          azimuths: [],
          altitudes: []
        };
      }
      
      bodies[body].dates.push(date);
      bodies[body].azimuths.push(azimuth);
      bodies[body].altitudes.push(altitude);
    });
    
    console.log('Grouped bodies data:', bodies);
    
    // Цвета для разных небесных тел
    const colors = {
      'Sun': { color: '#FFD700', name: 'Солнце' },
      'Moon': { color: '#C0C0C0', name: 'Луна' },
      'Mercury': { color: '#8C7853', name: 'Меркурий' },
      'Venus': { color: '#FFC649', name: 'Венера' },
      'Mars': { color: '#CD5C5C', name: 'Марс' },
      'Jupiter': { color: '#DAA520', name: 'Юпитер' },
      'Saturn': { color: '#F4A460', name: 'Сатурн' }
    };
    
    // Создаём датасеты для графика (высота над горизонтом)
    const datasets = [];
    Object.keys(bodies).forEach((body, index) => {
      const bodyData = bodies[body];
      const bodyColor = colors[body] || { color: `hsl(${index * 60}, 70%, 50%)`, name: body };
      
      datasets.push({
        label: `${bodyColor.name} - Высота над горизонтом`,
        data: bodyData.altitudes,
        borderColor: bodyColor.color,
        backgroundColor: bodyColor.color + '33',
        borderWidth: 2,
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6
      });
    });
    
    // Используем даты из первого небесного тела как общие метки
    const labels = bodies[Object.keys(bodies)[0]].dates.map(date => {
      // Форматируем дату для краткости
      const parts = date.split(' ');
      return parts[0]; // Только дата без времени
    });
    
    console.log('Chart labels:', labels);
    console.log('Chart datasets:', datasets);
    
    chartInstance = new Chart(canvas, {
      type: 'line',
      data: {
        labels: labels,
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 2.5,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              usePointStyle: true,
              padding: 15
            }
          },
          title: {
            display: true,
            text: 'Высота небесных тел над горизонтом (градусы)',
            font: {
              size: 16
            }
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: function(context) {
                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + '°';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Высота (градусы)'
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            title: {
              display: true,
              text: 'Дата'
            },
            grid: {
              display: false
            }
          }
        },
        interaction: {
          mode: 'nearest',
          axis: 'x',
          intersect: false
        }
      }
    });
  }
});
</script>
@endsection
