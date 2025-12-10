<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Кассиопея - Space Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    /* Glass-morphism Theme */
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      background-attachment: fixed;
    }
    
    #map {
      height: 340px;
      border-radius: 15px;
    }
    
    /* Navbar Glass Effect */
    .navbar-brand {
      font-weight: bold;
      font-size: 1.5rem;
    }
    
    .bg-space {
      background: rgba(255, 255, 255, 0.15) !important;
      backdrop-filter: blur(30px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .navbar-dark .navbar-nav .nav-link {
      color: rgba(255, 255, 255, 0.9) !important;
      transition: all 0.3s ease;
    }
    
    .navbar-dark .navbar-nav .nav-link:hover,
    .navbar-dark .navbar-nav .nav-link.active {
      color: #fff !important;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 10px;
      backdrop-filter: blur(10px);
    }
    
    /* Glass Cards */
    .card {
      background: rgba(255, 255, 255, 0.1) !important;
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
      backdrop-filter: blur(20px);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    
    .card:hover {
      background: rgba(255, 255, 255, 0.15) !important;
      transform: translateY(-3px);
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
    }
    
    .card-header {
      background: rgba(255, 255, 255, 0.1) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
      backdrop-filter: blur(10px);
    }
    
    .card-body {
      color: #fff;
    }
    
    .card-title {
      color: #fff !important;
      font-weight: 600;
    }
    
    /* Buttons */
    .btn-primary {
      background: rgba(255, 255, 255, 0.2) !important;
      border: 1px solid rgba(255, 255, 255, 0.3) !important;
      color: #fff !important;
      backdrop-filter: blur(10px);
      transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
      background: rgba(255, 255, 255, 0.3) !important;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    .btn-outline-primary,
    .btn-outline-secondary {
      background: rgba(255, 255, 255, 0.1) !important;
      border: 1px solid rgba(255, 255, 255, 0.3) !important;
      color: #fff !important;
      backdrop-filter: blur(10px);
    }
    
    .btn-outline-primary:hover,
    .btn-outline-secondary:hover {
      background: rgba(255, 255, 255, 0.2) !important;
      border-color: rgba(255, 255, 255, 0.4) !important;
    }
    
    /* Form Controls */
    .form-control,
    .form-select {
      background: rgba(255, 255, 255, 0.15) !important;
      border: 1px solid rgba(255, 255, 255, 0.3) !important;
      color: #fff !important;
      backdrop-filter: blur(10px);
    }
    
    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.6) !important;
    }
    
    .form-control:focus,
    .form-select:focus {
      background: rgba(255, 255, 255, 0.2) !important;
      border-color: rgba(255, 255, 255, 0.5) !important;
      color: #fff !important;
      box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
    }
    
    .form-label {
      color: rgba(255, 255, 255, 0.9);
      font-weight: 500;
    }
    
    /* Tables */
    .table {
      color: #fff !important;
    }
    
    .table thead {
      background: rgba(255, 255, 255, 0.1) !important;
      backdrop-filter: blur(10px);
    }
    
    .table-hover tbody tr:hover {
      background: rgba(255, 255, 255, 0.1) !important;
    }
    
    .table-striped tbody tr:nth-of-type(odd) {
      background: rgba(255, 255, 255, 0.05) !important;
    }
    
    /* Badges */
    .badge {
      background: rgba(255, 255, 255, 0.2) !important;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    /* Alerts */
    .alert {
      background: rgba(255, 255, 255, 0.15) !important;
      border: 1px solid rgba(255, 255, 255, 0.3) !important;
      color: #fff !important;
      backdrop-filter: blur(20px);
    }
    
    .alert-info {
      background: rgba(13, 202, 240, 0.2) !important;
      border-color: rgba(13, 202, 240, 0.4) !important;
    }
    
    .alert-success {
      background: rgba(25, 135, 84, 0.2) !important;
      border-color: rgba(25, 135, 84, 0.4) !important;
    }
    
    .alert-warning {
      background: rgba(255, 193, 7, 0.2) !important;
      border-color: rgba(255, 193, 7, 0.4) !important;
    }
    
    .alert-danger {
      background: rgba(220, 53, 69, 0.2) !important;
      border-color: rgba(220, 53, 69, 0.4) !important;
    }
    
    /* Text colors */
    .text-muted {
      color: rgba(255, 255, 255, 0.7) !important;
    }
    
    .text-primary {
      color: #e0e7ff !important;
    }
    
    .text-success {
      color: #86efac !important;
    }
    
    .text-warning {
      color: #fde68a !important;
    }
    
    .text-danger {
      color: #fca5a5 !important;
    }
    
    .text-info {
      color: #a5f3fc !important;
    }
    
    /* Border colors */
    .border-primary {
      border-color: rgba(102, 126, 234, 0.5) !important;
    }
    
    .border-success {
      border-color: rgba(25, 135, 84, 0.5) !important;
    }
    
    .border-warning {
      border-color: rgba(255, 193, 7, 0.5) !important;
    }
    
    .border-danger {
      border-color: rgba(220, 53, 69, 0.5) !important;
    }
    
    .border-info {
      border-color: rgba(13, 202, 240, 0.5) !important;
    }
    
    /* Footer */
    footer {
      background: rgba(255, 255, 255, 0.1) !important;
      backdrop-filter: blur(20px);
      border-top: 1px solid rgba(255, 255, 255, 0.2) !important;
      color: rgba(255, 255, 255, 0.9) !important;
    }
    
    footer .text-muted {
      color: rgba(255, 255, 255, 0.7) !important;
    }
    
    /* Spinner */
    .spinner-border {
      border-color: rgba(255, 255, 255, 0.3);
      border-right-color: #fff;
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
      width: 10px;
    }
    
    ::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.1);
    }
    
    ::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.3);
      border-radius: 5px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.5);
    }
  </style>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-space shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand" href="/dashboard">
      🌌 Кассиопея
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="/dashboard">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-speedometer2 me-1" viewBox="0 0 16 16">
              <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.39.39 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.39.39 0 0 0-.029-.518z"/>
              <path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A8 8 0 0 1 0 10m8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3"/>
            </svg>
            Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->is('iss') ? 'active' : '' }}" href="/iss">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-rocket-takeoff me-1" viewBox="0 0 16 16">
              <path d="M9.752 6.193c.599.6 1.73.437 2.528-.362s.96-1.932.362-2.531c-.599-.6-1.73-.438-2.528.361-.798.8-.96 1.933-.362 2.532"/>
              <path d="M15.811 3.312c-.363 1.534-1.334 3.626-3.64 6.218l-.24 2.408a2.56 2.56 0 0 1-.732 1.526L8.817 15.85a.51.51 0 0 1-.867-.434l.27-1.899c.04-.28-.013-.593-.131-.956a9 9 0 0 0-.249-.657l-.082-.202c-.815-.197-1.578-.662-2.191-1.277-.614-.615-1.079-1.379-1.275-2.195l-.203-.083a10 10 0 0 0-.655-.248c-.363-.119-.675-.172-.955-.132l-1.896.27A.51.51 0 0 1 .15 7.17l2.382-2.386c.41-.41.947-.67 1.524-.734h.006l2.4-.238C9.005 1.55 11.087.582 12.623.208c.89-.217 1.59-.232 2.08-.188.244.023.435.06.57.093q.1.026.16.045c.184.06.279.13.351.295l.029.073a3.5 3.5 0 0 1 .157.721c.055.485.051 1.178-.159 2.065m-4.828 7.475.04-.04-.107 1.081a1.54 1.54 0 0 1-.44.913l-1.298 1.3.054-.38c.072-.506-.034-.993-.172-1.418a9 9 0 0 0-.164-.45c.738-.065 1.462-.38 2.087-1.006M5.205 5c-.625.626-.94 1.351-1.004 2.09a9 9 0 0 0-.45-.164c-.424-.138-.91-.244-1.416-.172l-.38.054 1.3-1.3c.245-.246.566-.401.91-.44l1.08-.107zm9.406-3.961c-.38-.034-.967-.027-1.746.163-1.558.38-3.917 1.496-6.937 4.521-.62.62-.799 1.34-.687 2.051.107.676.483 1.362 1.048 1.928.564.565 1.25.941 1.924 1.049.71.112 1.429-.067 2.048-.688 3.079-3.083 4.192-5.444 4.556-6.987.183-.771.18-1.345.138-1.713a2.3 2.3 0 0 0-.045-.283 3 3 0 0 0-.3-.041Z"/>
            </svg>
            ISS Tracker
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->is('astronomy') ? 'active' : '' }}" href="/astronomy">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-stars me-1" viewBox="0 0 16 16">
              <path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.73 1.73 0 0 0 4.593 5.69l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.69A1.73 1.73 0 0 0 2.31 4.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.73 1.73 0 0 0 3.407 2.31zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732L9.1 2.137a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z"/>
            </svg>
            Astronomy
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->is('osdr') ? 'active' : '' }}" href="/osdr">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-database me-1" viewBox="0 0 16 16">
              <path d="M4.318 2.687C5.234 2.271 6.536 2 8 2s2.766.27 3.682.687C12.644 3.125 13 3.627 13 4c0 .374-.356.875-1.318 1.313C10.766 5.729 9.464 6 8 6s-2.766-.27-3.682-.687C3.356 4.875 3 4.373 3 4c0-.374.356-.875 1.318-1.313M13 5.698V7c0 .374-.356.875-1.318 1.313C10.766 8.729 9.464 9 8 9s-2.766-.27-3.682-.687C3.356 7.875 3 7.373 3 7V5.698c.271.202.58.378.904.525C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777A5 5 0 0 0 13 5.698M14 4c0-1.007-.875-1.755-1.904-2.223C11.022 1.289 9.573 1 8 1s-3.022.289-4.096.777C2.875 2.245 2 2.993 2 4v9c0 1.007.875 1.755 1.904 2.223C4.978 15.71 6.427 16 8 16s3.022-.289 4.096-.777C13.125 14.755 14 14.007 14 13zm-1 4.698V10c0 .374-.356.875-1.318 1.313C10.766 11.729 9.464 12 8 12s-2.766-.27-3.682-.687C3.356 10.875 3 10.373 3 10V8.698c.271.202.58.378.904.525C4.978 9.71 6.427 10 8 10s3.022-.289 4.096-.777A5 5 0 0 0 13 8.698m0 3V13c0 .374-.356.875-1.318 1.313C10.766 14.729 9.464 15 8 15s-2.766-.27-3.682-.687C3.356 13.875 3 13.373 3 13v-1.302c.271.202.58.378.904.525C4.978 12.71 6.427 13 8 13s3.022-.289 4.096-.777c.324-.147.633-.323.904-.525"/>
            </svg>
            OSDR
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
@yield('content')
<footer class="mt-5 py-4 bg-light border-top">
  <div class="container text-center text-muted">
    <p class="mb-1">🌌 Проект "Кассиопея" - Космический мониторинг в реальном времени</p>
    <p class="mb-0 small">NASA API • JWST • ISS Tracking • OSDR</p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
