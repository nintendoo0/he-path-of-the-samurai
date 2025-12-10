@extends('layouts.app')

@section('content')
<style>
    .example-section {
      margin-bottom: 60px;
      padding: 40px;
      border-radius: 20px;
      position: relative;
      overflow: hidden;
    }
    
    .example-title {
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 30px;
      text-align: center;
    }
    
    /* ============================================
       ВАРИАНТ A: КОСМИЧЕСКАЯ ТЁМНАЯ ТЕМА
       ============================================ */
    #theme-a {
      background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
      position: relative;
    }
    
    #theme-a::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: 
        radial-gradient(2px 2px at 20px 30px, white, transparent),
        radial-gradient(2px 2px at 60px 70px, white, transparent),
        radial-gradient(1px 1px at 50px 50px, white, transparent),
        radial-gradient(1px 1px at 130px 80px, white, transparent),
        radial-gradient(2px 2px at 90px 10px, white, transparent);
      background-size: 200px 200px;
      animation: stars 50s linear infinite;
      opacity: 0.5;
    }
    
    @keyframes stars {
      from { background-position: 0 0; }
      to { background-position: -200px 200px; }
    }
    
    .space-card {
      background: rgba(30, 30, 60, 0.8);
      border: 1px solid rgba(138, 43, 226, 0.3);
      border-radius: 15px;
      padding: 25px;
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px rgba(138, 43, 226, 0.2);
      transition: all 0.3s ease;
      position: relative;
      z-index: 1;
    }
    
    .space-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 40px rgba(138, 43, 226, 0.4);
      border-color: rgba(138, 43, 226, 0.6);
    }
    
    .neon-text {
      color: #00ffff;
      text-shadow: 0 0 10px #00ffff, 0 0 20px #00ffff, 0 0 30px #00ffff;
    }
    
    .neon-badge {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: 1px solid #00ffff;
      box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }
    
    /* ============================================
       ВАРИАНТ B: GLASS-MORPHISM
       ============================================ */
    #theme-b {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .glass-card {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 30px;
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    
    .glass-card:hover {
      background: rgba(255, 255, 255, 0.15);
      transform: translateY(-3px);
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
    }
    
    .glass-nav {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(30px);
      border-radius: 50px;
      padding: 15px 30px;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    /* ============================================
       ВАРИАНТ C: НЕОМОРФИЗМ
       ============================================ */
    #theme-c {
      background: #e0e5ec;
    }
    
    .neomorph-card {
      background: #e0e5ec;
      border-radius: 20px;
      padding: 30px;
      box-shadow: 
        20px 20px 60px #bebebe,
        -20px -20px 60px #ffffff;
      transition: all 0.3s ease;
    }
    
    .neomorph-card:hover {
      box-shadow: 
        inset 20px 20px 60px #bebebe,
        inset -20px -20px 60px #ffffff;
    }
    
    .neomorph-btn {
      background: #e0e5ec;
      border: none;
      border-radius: 15px;
      padding: 12px 30px;
      box-shadow: 
        5px 5px 10px #bebebe,
        -5px -5px 10px #ffffff;
      color: #667eea;
      font-weight: 600;
      transition: all 0.2s ease;
    }
    
    .neomorph-btn:hover {
      box-shadow: 
        inset 5px 5px 10px #bebebe,
        inset -5px -5px 10px #ffffff;
    }
    
    /* ============================================
       ВАРИАНТ D: МИНИМАЛИЗМ
       ============================================ */
    #theme-d {
      background: #ffffff;
      color: #333;
    }
    
    .minimal-card {
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      padding: 25px;
      transition: all 0.2s ease;
    }
    
    .minimal-card:hover {
      border-color: #667eea;
      box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }
    
    .minimal-text {
      color: #333;
      font-size: 0.95rem;
      line-height: 1.6;
    }
    
    .minimal-accent {
      color: #667eea;
      font-weight: 600;
    }
    
    /* ============================================
       ВАРИАНТ E: КОМБИНИРОВАННЫЙ
       ============================================ */
    #theme-e {
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    }
    
    .hybrid-card {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 20px;
      padding: 30px;
      backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.3),
        inset 0 0 0 1px rgba(255, 255, 255, 0.05);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .hybrid-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      transition: left 0.5s;
    }
    
    .hybrid-card:hover::before {
      left: 100%;
    }
    
    .hybrid-card:hover {
      transform: translateY(-5px);
      border-color: rgba(102, 126, 234, 0.5);
      box-shadow: 
        0 12px 40px rgba(102, 126, 234, 0.3),
        inset 0 0 0 1px rgba(255, 255, 255, 0.1);
    }
    
    .glow-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
    }
    
    .stat-value {
      font-size: 2.5rem;
      font-weight: bold;
      background: linear-gradient(135deg, #667eea, #00ffff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    /* Общие стили */
    .select-btn {
      margin-top: 20px;
      padding: 12px 30px;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    #theme-a .select-btn {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    #theme-a .select-btn:hover {
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
      transform: translateY(-2px);
    }
    
    #theme-b .select-btn {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    #theme-c .select-btn {
      background: #e0e5ec;
      color: #667eea;
      box-shadow: 5px 5px 10px #bebebe, -5px -5px 10px #ffffff;
    }
    
    #theme-d .select-btn {
      background: #667eea;
      color: white;
    }
    
    #theme-e .select-btn {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
  </style>

<div class="container" style="margin-top: -20px;">
  <div class="text-center mb-5">
    <h1 style="font-size: 3rem; background: linear-gradient(135deg, #667eea, #00ffff); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
      🎨 Примеры дизайна для Кассиопеи
    </h1>
    <p class="text-muted">Выберите стиль, который вам больше нравится</p>
  </div>

  <!-- ВАРИАНТ A: Космическая тёмная тема -->
  <div id="theme-a" class="example-section">
    <h2 class="example-title neon-text">A. Космическая тёмная тема ✨</h2>
    
    <div class="row g-4">
      <div class="col-md-6">
        <div class="space-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="neon-text mb-0">🛰️ МКС Tracker</h4>
            <span class="badge neon-badge">LIVE</span>
          </div>
          <p style="color: #ccc;">Отслеживание Международной космической станции в реальном времени</p>
          <div class="row mt-3">
            <div class="col-6">
              <div class="text-muted small">Широта</div>
              <div class="neon-text fs-4 fw-bold">51.21°</div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Скорость</div>
              <div class="neon-text fs-4 fw-bold">27,581 км/ч</div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="space-card">
          <h4 style="color: #00ffff;">🌟 Astronomy API</h4>
          <p style="color: #ccc;">Позиции небесных тел и астрономические события</p>
          <div class="mt-3">
            <div class="d-flex justify-content-between mb-2">
              <span style="color: #aaa;">Солнце</span>
              <span class="neon-text">Ophiuchus</span>
            </div>
            <div class="d-flex justify-content-between">
              <span style="color: #aaa;">Луна</span>
              <span class="neon-text">Virgo</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="text-center">
      <button class="select-btn" onclick="alert('Космическая тёмная тема выбрана!')">
        Выбрать этот стиль
      </button>
    </div>
  </div>

  <!-- ВАРИАНТ B: Glass-morphism -->
  <div id="theme-b" class="example-section">
    <h2 class="example-title">B. Glass-morphism стиль 🔮</h2>
    
    <div class="glass-nav mb-4 text-center">
      <span class="mx-3">🏠 Dashboard</span>
      <span class="mx-3">🛰️ ISS</span>
      <span class="mx-3">⭐ Astronomy</span>
      <span class="mx-3">📊 OSDR</span>
    </div>
    
    <div class="row g-4">
      <div class="col-md-4">
        <div class="glass-card text-center">
          <div style="font-size: 3rem;">🌍</div>
          <h5 class="mt-3">Положение МКС</h5>
          <p class="mb-0">Высота: 425 км</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="glass-card text-center">
          <div style="font-size: 3rem;">🌙</div>
          <h5 class="mt-3">Фазы Луны</h5>
          <p class="mb-0">Растущая луна</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="glass-card text-center">
          <div style="font-size: 3rem;">☀️</div>
          <h5 class="mt-3">Солнечная активность</h5>
          <p class="mb-0">Нормальная</p>
        </div>
      </div>
    </div>
    
    <div class="text-center">
      <button class="select-btn" onclick="alert('Glass-morphism стиль выбран!')">
        Выбрать этот стиль
      </button>
    </div>
  </div>

  <!-- ВАРИАНТ C: Неоморфизм -->
  <div id="theme-c" class="example-section">
    <h2 class="example-title" style="color: #667eea;">C. Неоморфизм 🎭</h2>
    
    <div class="row g-4">
      <div class="col-md-6">
        <div class="neomorph-card">
          <h4 style="color: #667eea;">🚀 Запуски SpaceX</h4>
          <p style="color: #666;">Следующий запуск через 3 дня</p>
          <button class="neomorph-btn mt-3">Подробнее</button>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="neomorph-card">
          <h4 style="color: #667eea;">📡 JWST Gallery</h4>
          <p style="color: #666;">Новые изображения телескопа Джеймса Уэбба</p>
          <button class="neomorph-btn mt-3">Смотреть</button>
        </div>
      </div>
    </div>
    
    <div class="text-center">
      <button class="select-btn" onclick="alert('Неоморфизм выбран!')">
        Выбрать этот стиль
      </button>
    </div>
  </div>

  <!-- ВАРИАНТ D: Минимализм -->
  <div id="theme-d" class="example-section">
    <h2 class="example-title minimal-accent">D. Минималистичная тема 📐</h2>
    
    <div class="row g-4">
      <div class="col-md-12">
        <div class="minimal-card">
          <div class="d-flex align-items-center mb-3">
            <div style="width: 50px; height: 50px; background: #f5f5f5; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
              🛰️
            </div>
            <div class="ms-3">
              <h5 class="mb-0 minimal-accent">ISS Position Tracker</h5>
              <p class="minimal-text mb-0">Real-time tracking of International Space Station</p>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-3">
              <div class="minimal-text small">Latitude</div>
              <div class="minimal-accent fs-5 fw-bold">51.21°</div>
            </div>
            <div class="col-3">
              <div class="minimal-text small">Longitude</div>
              <div class="minimal-accent fs-5 fw-bold">162.81°</div>
            </div>
            <div class="col-3">
              <div class="minimal-text small">Altitude</div>
              <div class="minimal-accent fs-5 fw-bold">425 km</div>
            </div>
            <div class="col-3">
              <div class="minimal-text small">Velocity</div>
              <div class="minimal-accent fs-5 fw-bold">27,581 km/h</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="text-center">
      <button class="select-btn" onclick="alert('Минимализм выбран!')">
        Выбрать этот стиль
      </button>
    </div>
  </div>

  <!-- ВАРИАНТ E: Комбинированный -->
  <div id="theme-e" class="example-section">
    <h2 class="example-title" style="background: linear-gradient(135deg, #667eea, #00ffff); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
      E. Комбинированный стиль 🌟
    </h2>
    
    <div class="row g-4">
      <div class="col-md-3">
        <div class="hybrid-card text-center">
          <div class="glow-icon mx-auto mb-3">🌍</div>
          <div class="stat-value">51.21°</div>
          <div class="text-muted mt-2">Широта МКС</div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="hybrid-card text-center">
          <div class="glow-icon mx-auto mb-3">🚀</div>
          <div class="stat-value">425</div>
          <div class="text-muted mt-2">Высота (км)</div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="hybrid-card text-center">
          <div class="glow-icon mx-auto mb-3">⚡</div>
          <div class="stat-value">27.5k</div>
          <div class="text-muted mt-2">Скорость (км/ч)</div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="hybrid-card text-center">
          <div class="glow-icon mx-auto mb-3">⭐</div>
          <div class="stat-value">120</div>
          <div class="text-muted mt-2">События</div>
        </div>
      </div>
    </div>
    
    <div class="row g-4 mt-3">
      <div class="col-md-12">
        <div class="hybrid-card">
          <h4 style="color: #00ffff;">📊 Position History</h4>
          <p style="color: #aaa;">График движения МКС за последние 2 часа</p>
          <div style="height: 200px; background: rgba(0,0,0,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <span style="color: #666;">📈 График Chart.js</span>
          </div>
        </div>
      </div>
    </div>
    
    <div class="text-center">
      <button class="select-btn" onclick="alert('Комбинированный стиль выбран!')">
        Выбрать этот стиль
      </button>
    </div>
  </div>

  <div class="text-center mt-5 mb-5">
    <a href="/dashboard" style="color: #667eea; text-decoration: none; font-size: 1.2rem;">
      ← Вернуться на главную
    </a>
  </div>
</div>
@endsection
