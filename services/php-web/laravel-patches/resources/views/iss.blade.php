@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>🛰️ МКС — Международная космическая станция</h2>
  </div>

  <div class="row g-3 mb-3">
    <!-- Метрики -->
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-primary">
        <div class="card-body text-center">
          <div class="small text-muted mb-1">Широта</div>
          <div class="fs-5 fw-bold text-primary">{{ number_format($last['payload']['latitude'] ?? 0, 4) }}°</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-success">
        <div class="card-body text-center">
          <div class="small text-muted mb-1">Долгота</div>
          <div class="fs-5 fw-bold text-success">{{ number_format($last['payload']['longitude'] ?? 0, 4) }}°</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-warning">
        <div class="card-body text-center">
          <div class="small text-muted mb-1">Высота</div>
          <div class="fs-5 fw-bold text-warning">{{ number_format($last['payload']['altitude'] ?? 0, 0) }} км</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-danger">
        <div class="card-body text-center">
          <div class="small text-muted mb-1">Скорость</div>
          <div class="fs-5 fw-bold text-danger">{{ number_format($last['payload']['velocity'] ?? 0, 0) }} км/ч</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Карта МКС -->
    <div class="col-lg-8">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title mb-3">🌍 Текущее положение на карте</h5>
          <div id="issMap" class="rounded border" style="height:400px"></div>
          <div class="mt-2 text-muted small">
            <strong>Последнее обновление:</strong> {{ $last['fetched_at'] ?? 'Загрузка...' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Тренд движения -->
    <div class="col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title mb-3">📊 Тренд движения</h5>
          @if(!empty($trend))
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Статус движения</span>
                @if($trend['movement'] ?? false)
                  <span class="badge bg-success">Движется</span>
                @else
                  <span class="badge bg-secondary">Статичная</span>
                @endif
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Смещение</span>
                <strong>{{ number_format($trend['delta_km'] ?? 0, 3) }} км</strong>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Интервал</span>
                <strong>{{ $trend['dt_sec'] ?? 0 }} сек</strong>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Скорость</span>
                <strong class="text-danger">{{ number_format($trend['velocity_kmh'] ?? 0, 0) }} км/ч</strong>
              </div>
            </div>
          @else
            <div class="alert alert-warning">
              <small>Данные о тренде временно недоступны</small>
            </div>
          @endif

          <hr>

          <div class="d-grid gap-2">
            <a href="/osdr" class="btn btn-outline-primary">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-database me-1" viewBox="0 0 16 16">
                <path d="M4.318 2.687C5.234 2.271 6.536 2 8 2s2.766.27 3.682.687C12.644 3.125 13 3.627 13 4c0 .374-.356.875-1.318 1.313C10.766 5.729 9.464 6 8 6s-2.766-.27-3.682-.687C3.356 4.875 3 4.373 3 4c0-.374.356-.875 1.318-1.313M13 5.698V7c0 .374-.356.875-1.318 1.313C10.766 8.729 9.464 9 8 9s-2.766-.27-3.682-.687C3.356 7.875 3 7.373 3 7V5.698c.271.202.58.378.904.525C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777A5 5 0 0 0 13 5.698M14 4c0-1.007-.875-1.755-1.904-2.223C11.022 1.289 9.573 1 8 1s-3.022.289-4.096.777C2.875 2.245 2 2.993 2 4v9c0 1.007.875 1.755 1.904 2.223C4.978 15.71 6.427 16 8 16s3.022-.289 4.096-.777C13.125 14.755 14 14.007 14 13zm-1 4.698V10c0 .374-.356.875-1.318 1.313C10.766 11.729 9.464 12 8 12s-2.766-.27-3.682-.687C3.356 10.875 3 10.373 3 10V8.698c.271.202.58.378.904.525C4.978 9.71 6.427 10 8 10s3.022-.289 4.096-.777A5 5 0 0 0 13 8.698m0 3V13c0 .374-.356.875-1.318 1.313C10.766 14.729 9.464 15 8 15s-2.766-.27-3.682-.687C3.356 13.875 3 13.373 3 13v-1.302c.271.202.58.378.904.525C4.978 12.71 6.427 13 8 13s3.022-.289 4.096-.777c.324-.147.633-.323.904-.525"/>
              </svg>
              Перейти к базе OSDR
            </a>
            <a href="/dashboard" class="btn btn-outline-secondary">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-speedometer2 me-1" viewBox="0 0 16 16">
                <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.39.39 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.39.39 0 0 0-.029-.518z"/>
                <path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A8 8 0 0 1 0 10m8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3"/>
              </svg>
              Вернуться на Dashboard
            </a>
          </div>

          <div class="mt-3 p-2 bg-light rounded">
            <small class="text-muted d-block mb-1"><strong>API Endpoints:</strong></small>
            <small class="d-block text-break"><code>{{ $base }}/last</code></small>
            <small class="d-block text-break"><code>{{ $base }}/iss/trend</code></small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Информационная панель -->
  <div class="row g-3 mt-2">
    <!-- График Position History -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">📈 Position History</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <canvas id="latLonChart" height="120"></canvas>
            </div>
            <div class="col-md-6">
              <canvas id="altVelChart" height="120"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card shadow-sm border-info">
        <div class="card-body">
          <h5 class="card-title text-info">ℹ️ О Международной космической станции</h5>
          <p class="mb-1">
            <strong>МКС</strong> — самый большой космический объект, созданный человеком. Станция движется со скоростью около 27 500 км/ч на высоте примерно 400 км над поверхностью Земли.
          </p>
          <p class="mb-0 small text-muted">
            Данные обновляются автоматически каждые 2 минуты через API <code>wheretheiss.at</code>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  @if(!empty($last['payload']))
    const lat = {{ $last['payload']['latitude'] ?? 0 }};
    const lon = {{ $last['payload']['longitude'] ?? 0 }};
    
    // Инициализация карты
    const map = L.map('issMap').setView([lat, lon], 3);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Иконка МКС
    const issIcon = L.divIcon({
      className: 'iss-marker',
      html: '<div style="font-size:32px;">🛰️</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16]
    });
    
    // Маркер МКС и трейл
    const marker = L.marker([lat, lon], {icon: issIcon}).addTo(map);
    const trail = L.polyline([], {color:'#667eea', weight:3}).addTo(map);
    
    marker.bindPopup(`
      <strong>🛰️ МКС</strong><br>
      Широта: ${lat.toFixed(4)}°<br>
      Долгота: ${lon.toFixed(4)}°<br>
      Высота: {{ number_format($last['payload']['altitude'] ?? 0, 0) }} км<br>
      Скорость: {{ number_format($last['payload']['velocity'] ?? 0, 0) }} км/ч
    `).openPopup();
    
    // Круг видимости МКС
    const visibilityCircle = L.circle([lat, lon], {
      color: '#667eea',
      fillColor: '#764ba2',
      fillOpacity: 0.1,
      radius: 2000000
    }).addTo(map);
    
    // Графики Position History
    const latLonChart = new Chart(document.getElementById('latLonChart'), {
      type: 'line',
      data: {
        labels: [],
        datasets: [
          {
            label: 'Широта',
            data: [],
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.1
          },
          {
            label: 'Долгота',
            data: [],
            borderColor: '#198754',
            backgroundColor: 'rgba(25, 135, 84, 0.1)',
            tension: 0.1
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          title: {
            display: true,
            text: 'Широта и Долгота'
          },
          legend: {
            position: 'bottom'
          }
        },
        scales: {
          x: {
            display: false
          }
        }
      }
    });
    
    const altVelChart = new Chart(document.getElementById('altVelChart'), {
      type: 'line',
      data: {
        labels: [],
        datasets: [
          {
            label: 'Высота (км)',
            data: [],
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.1,
            yAxisID: 'y'
          },
          {
            label: 'Скорость (км/ч)',
            data: [],
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.1,
            yAxisID: 'y1'
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          title: {
            display: true,
            text: 'Высота и Скорость'
          },
          legend: {
            position: 'bottom'
          }
        },
        scales: {
          x: {
            display: false
          },
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            title: {
              display: true,
              text: 'Высота (км)'
            }
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            title: {
              display: true,
              text: 'Скорость (км/ч)'
            },
            grid: {
              drawOnChartArea: false
            }
          }
        }
      }
    });
    
    // Загрузка тренда
    async function loadTrend() {
      try {
        const r = await fetch('/api/iss/history?limit=100');
        const js = await r.json();
        const pts = Array.isArray(js.points) ? js.points : [];
        
        if (pts.length > 0) {
          // Обновить карту
          const coords = pts.map(p => [p.lat, p.lon]);
          trail.setLatLngs(coords);
          const lastPt = coords[coords.length - 1];
          marker.setLatLng(lastPt);
          visibilityCircle.setLatLng(lastPt);
          
          // Обновить графики
          const times = pts.map(p => new Date(p.at).toLocaleTimeString());
          
          latLonChart.data.labels = times;
          latLonChart.data.datasets[0].data = pts.map(p => p.lat);
          latLonChart.data.datasets[1].data = pts.map(p => p.lon);
          latLonChart.update('none');
          
          altVelChart.data.labels = times;
          altVelChart.data.datasets[0].data = pts.map(p => p.altitude);
          altVelChart.data.datasets[1].data = pts.map(p => p.velocity);
          altVelChart.update('none');
        }
      } catch(e) {
        console.error('Error loading trend:', e);
      }
    }
    
    loadTrend();
    setInterval(loadTrend, 15000); // Обновление каждые 15 секунд
  @endif
});
</script>
@endsection
