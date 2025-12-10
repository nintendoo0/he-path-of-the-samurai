@extends('layouts.app')

@section('content')
<style>
  /* Glow Icons for Metrics */
  .metric-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
    animation: glow-pulse 2s infinite;
  }
  
  @keyframes glow-pulse {
    0%, 100% { box-shadow: 0 0 20px rgba(102, 126, 234, 0.5); }
    50% { box-shadow: 0 0 30px rgba(102, 126, 234, 0.8); }
  }
  
  .stat-value {
    background: linear-gradient(135deg, #667eea, #00ffff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
</style>

<div class="container-fluid py-4 px-4">
  <!-- Заголовок с приветствием -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1" style="font-size: 2.5rem; font-weight: 700; background: linear-gradient(135deg, #667eea, #00ffff); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            🌌 Космический Dashboard
          </h1>
          <p class="text-muted mb-0">Мониторинг космических данных в реальном времени</p>
        </div>
        <div class="text-end">
          <div class="badge bg-success bg-opacity-25 text-success px-3 py-2" style="box-shadow: 0 0 15px rgba(25, 135, 84, 0.4);">
            <i class="bi bi-circle-fill" style="font-size: 8px;"></i> LIVE
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Метрики МКС с Hybrid стилем -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-body text-center">
          <div class="metric-icon">⚡</div>
          <div class="small text-muted mb-2">Скорость МКС</div>
          <div class="fs-3 fw-bold stat-value">
            {{ isset(($iss['payload'] ?? [])['velocity']) ? number_format($iss['payload']['velocity'],0,'',' ') : '—' }}
          </div>
          <div class="small text-muted">км/ч</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-body text-center">
          <div class="metric-icon">📏</div>
          <div class="small text-muted mb-2">Высота МКС</div>
          <div class="fs-3 fw-bold stat-value">
            {{ isset(($iss['payload'] ?? [])['altitude']) ? number_format($iss['payload']['altitude'],0,'',' ') : '—' }}
          </div>
          <div class="small text-muted">км</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-body text-center">
          <div class="metric-icon">🌐</div>
          <div class="small text-muted mb-2">Широта</div>
          <div class="fs-3 fw-bold stat-value">
            {{ isset(($iss['payload'] ?? [])['latitude']) ? number_format($iss['payload']['latitude'],4) : '—' }}°
          </div>
          <div class="small text-muted">Север/Юг</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-body text-center">
          <div class="metric-icon">🧭</div>
          <div class="small text-muted mb-2">Долгота</div>
          <div class="fs-3 fw-bold stat-value">
            {{ isset(($iss['payload'] ?? [])['longitude']) ? number_format($iss['payload']['longitude'],4) : '—' }}°
          </div>
          <div class="small text-muted">Восток/Запад</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Основная секция: Карта и Графики -->
  <div class="row g-3 mb-4">
    <!-- Карта МКС -->
    <div class="col-lg-8">
      <div class="card shadow-sm h-100">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">🛰️ Положение МКС на карте</h5>
            <a href="/iss" class="btn btn-sm btn-outline-primary">Подробнее →</a>
          </div>
        </div>
        <div class="card-body">
          <div id="map" class="rounded border" style="height:450px"></div>
        </div>
      </div>
    </div>

    <!-- Графики движения -->
    <div class="col-lg-4">
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h6 class="card-title mb-0">📊 Скорость</h6>
        </div>
        <div class="card-body">
          <canvas id="issSpeedChart" height="180"></canvas>
        </div>
      </div>
      <div class="card shadow-sm">
        <div class="card-header">
          <h6 class="card-title mb-0">📈 Высота</h6>
        </div>
        <div class="card-body">
          <canvas id="issAltChart" height="180"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Галерея JWST -->
  <!-- Галерея JWST -->
  <div class="row g-3">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h5 class="card-title mb-0">🔭 JWST — Галерея телескопа Джеймса Уэбба</h5>
            <form id="jwstFilter" class="d-flex gap-2 align-items-center flex-wrap">
              <select class="form-select form-select-sm" name="source" id="srcSel" style="width:auto;">
                <option value="jpg" selected>Все JPG</option>
                <option value="suffix">По суффиксу</option>
                <option value="program">По программе</option>
              </select>
              <input type="text" class="form-control form-control-sm" name="suffix" id="suffixInp" placeholder="_cal / _thumb" style="width:140px;display:none">
              <input type="text" class="form-control form-control-sm" name="program" id="progInp" placeholder="2734" style="width:110px;display:none">
              <select class="form-select form-select-sm" name="instrument" style="width:auto;">
                <option value="">Любой инструмент</option>
                <option>NIRCam</option><option>MIRI</option><option>NIRISS</option><option>NIRSpec</option><option>FGS</option>
              </select>
              <select class="form-select form-select-sm" name="perPage" style="width:auto;">
                <option>12</option><option selected>24</option><option>36</option><option>48</option>
              </select>
              <button class="btn btn-sm btn-primary" type="submit">Показать</button>
            </form>
          </div>
        </div>
        <div class="card-body">
          <style>
            .jwst-slider{position:relative; padding: 0 40px;}
            .jwst-track{
              display:flex; gap:1rem; overflow:auto; scroll-snap-type:x mandatory; padding:.5rem;
              scrollbar-width: thin;
            }
            .jwst-item{flex:0 0 200px; scroll-snap-align:start;}
            .jwst-item img{
              width:100%; height:200px; object-fit:cover; border-radius:10px;
              transition: transform 0.3s ease, box-shadow 0.3s ease;
              border: 2px solid rgba(255,255,255,0.2);
            }
            .jwst-item img:hover{
              transform: scale(1.05);
              box-shadow: 0 8px 20px rgba(0,0,0,0.3);
              border-color: rgba(255,255,255,0.4);
            }
            .jwst-cap{font-size:.85rem; margin-top:.5rem; color: rgba(255,255,255,0.9);}
            .jwst-nav{
              position:absolute; top:50%; transform:translateY(-50%); z-index:2;
              width: 35px; height: 35px; border-radius: 50%;
              background: rgba(255,255,255,0.2) !important;
              border: 1px solid rgba(255,255,255,0.3) !important;
              backdrop-filter: blur(10px);
              color: #fff !important;
              font-size: 1.5rem;
              display: flex;
              align-items: center;
              justify-content: center;
              padding: 0;
            }
            .jwst-nav:hover{
              background: rgba(255,255,255,0.3) !important;
            }
            .jwst-prev{left:5px;} 
            .jwst-next{right:5px;}
          </style>

          <div class="jwst-slider">
            <button class="btn jwst-nav jwst-prev" type="button" aria-label="Prev">‹</button>
            <div id="jwstTrack" class="jwst-track"></div>
            <button class="btn jwst-nav jwst-next" type="button" aria-label="Next">›</button>
          </div>

          <div id="jwstInfo" class="small text-muted mt-3 text-center"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function () {
  // ====== карта и графики МКС ======
  if (typeof L !== 'undefined' && typeof Chart !== 'undefined') {
    const last = @json(($iss['payload'] ?? []));
    let lat0 = Number(last.latitude || 0), lon0 = Number(last.longitude || 0);
    
    const map = L.map('map').setView([lat0||0, lon0||0], lat0?3:2);
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
    
    const marker = L.marker([lat0||0, lon0||0], {icon: issIcon}).addTo(map);
    const trail  = L.polyline([], {color:'#667eea', weight:3}).addTo(map);
    
    // Круг видимости МКС
    const visibilityCircle = L.circle([lat0||0, lon0||0], {
      color: '#667eea',
      fillColor: '#764ba2',
      fillOpacity: 0.1,
      radius: 2000000
    }).addTo(map);
    
    marker.bindPopup(`
      <strong>🛰️ МКС</strong><br>
      Широта: ${lat0.toFixed(4)}°<br>
      Долгота: ${lon0.toFixed(4)}°<br>
      Высота: ${Number(last.altitude || 0).toFixed(0)} км<br>
      Скорость: ${Number(last.velocity || 0).toFixed(0)} км/ч
    `).openPopup();

    const speedChart = new Chart(document.getElementById('issSpeedChart'), {
      type: 'line', data: { labels: [], datasets: [{ label: 'Скорость', data: [] }] },
      options: { responsive: true, scales: { x: { display: false } } }
    });
    const altChart = new Chart(document.getElementById('issAltChart'), {
      type: 'line', data: { labels: [], datasets: [{ label: 'Высота', data: [] }] },
      options: { responsive: true, scales: { x: { display: false } } }
    });

    async function loadTrend() {
      try {
        const r = await fetch('/api/iss/history?limit=100');
        const js = await r.json();
        const pts = Array.isArray(js.points) ? js.points : [];
        if (pts.length) {
          const coords = pts.map(p => [p.lat, p.lon]);
          trail.setLatLngs(coords);
          const lastPt = coords[coords.length-1];
          marker.setLatLng(lastPt);
          visibilityCircle.setLatLng(lastPt);
          
          const t = pts.map(p => new Date(p.at).toLocaleTimeString());
          speedChart.data.labels = t;
          speedChart.data.datasets[0].data = pts.map(p => p.velocity);
          speedChart.data.datasets[0].borderColor = 'rgba(252, 165, 165, 1)';
          speedChart.data.datasets[0].backgroundColor = 'rgba(252, 165, 165, 0.1)';
          speedChart.update('none');
          
          altChart.data.labels = t;
          altChart.data.datasets[0].data = pts.map(p => p.altitude);
          altChart.data.datasets[0].borderColor = 'rgba(253, 230, 138, 1)';
          altChart.data.datasets[0].backgroundColor = 'rgba(253, 230, 138, 0.1)';
          altChart.update('none');
        }
      } catch(e) {
        console.error('Error loading trend:', e);
      }
    }
    loadTrend();
    setInterval(loadTrend, 15000);
  }

  // ====== JWST ГАЛЕРЕЯ ======
  const track = document.getElementById('jwstTrack');
  const info  = document.getElementById('jwstInfo');
  const form  = document.getElementById('jwstFilter');
  const srcSel = document.getElementById('srcSel');
  const sfxInp = document.getElementById('suffixInp');
  const progInp= document.getElementById('progInp');

  function toggleInputs(){
    sfxInp.style.display  = (srcSel.value==='suffix')  ? '' : 'none';
    progInp.style.display = (srcSel.value==='program') ? '' : 'none';
  }
  srcSel.addEventListener('change', toggleInputs); toggleInputs();

  async function loadFeed(qs){
    track.innerHTML = '<div class="p-3 text-muted">Загрузка…</div>';
    info.textContent= '';
    try{
      const url = '/api/jwst/feed?'+new URLSearchParams(qs).toString();
      const r = await fetch(url);
      const js = await r.json();
      track.innerHTML = '';
      (js.items||[]).forEach(it=>{
        const fig = document.createElement('figure');
        fig.className = 'jwst-item m-0';
        fig.innerHTML = `
          <a href="${it.link||it.url}" target="_blank" rel="noreferrer">
            <img loading="lazy" src="${it.url}" alt="JWST">
          </a>
          <figcaption class="jwst-cap">${(it.caption||'').replaceAll('<','&lt;')}</figcaption>`;
        track.appendChild(fig);
      });
      info.textContent = `Источник: ${js.source} · Показано ${js.count||0}`;
    }catch(e){
      track.innerHTML = '<div class="p-3 text-danger">Ошибка загрузки</div>';
    }
  }

  form.addEventListener('submit', function(ev){
    ev.preventDefault();
    const fd = new FormData(form);
    const q = Object.fromEntries(fd.entries());
    loadFeed(q);
  });

  // навигация
  document.querySelector('.jwst-prev').addEventListener('click', ()=> track.scrollBy({left:-600, behavior:'smooth'}));
  document.querySelector('.jwst-next').addEventListener('click', ()=> track.scrollBy({left: 600, behavior:'smooth'}));

  // стартовые данные
  loadFeed({source:'jpg', perPage:24});
});
</script>
@endsection