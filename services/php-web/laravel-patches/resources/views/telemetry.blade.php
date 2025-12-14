@extends('layouts.app')

@section('content')
<style>
  /* DataTables Custom Styling */
  .dataTables_wrapper {
    color: #e0e0e0;
  }
  
  .dataTables_wrapper .dataTables_length,
  .dataTables_wrapper .dataTables_filter,
  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate {
    color: #e0e0e0;
  }
  
  .dataTables_wrapper .dataTables_filter input {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 8px;
    padding: 0.5rem 1rem;
    color: #e0e0e0;
  }
  
  .dataTables_wrapper .dataTables_filter input:focus {
    outline: none;
    border-color: #00ffff;
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
  }
  
  table.dataTable {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
  }
  
  table.dataTable thead th {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-weight: 600;
    padding: 1rem;
    border-bottom: 2px solid #00ffff;
  }
  
  table.dataTable tbody tr {
    background: rgba(255, 255, 255, 0.02);
    transition: all 0.3s ease;
  }
  
  table.dataTable tbody tr:hover {
    background: rgba(102, 126, 234, 0.15);
    transform: translateX(5px);
  }
  
  table.dataTable tbody td {
    color: #e0e0e0;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  }
  
  .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-color: #00ffff;
  }
  
  .page-link {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(102, 126, 234, 0.3);
    color: #00ffff;
  }
  
  .page-link:hover {
    background: rgba(102, 126, 234, 0.2);
    color: #00ffff;
  }
  
  .badge-boolean-true {
    background: linear-gradient(135deg, #11998e, #38ef7d);
    color: white;
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    font-size: 0.85rem;
  }
  
  .badge-boolean-false {
    background: linear-gradient(135deg, #ee0979, #ff6a00);
    color: white;
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    font-size: 0.85rem;
  }
  
  .export-btn {
    background: linear-gradient(135deg, #11998e, #38ef7d);
    border: none;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
  }
  
  .export-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(17, 153, 142, 0.5);
    background: linear-gradient(135deg, #38ef7d, #11998e);
  }
  
  .csv-info-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    border: 1px solid rgba(102, 126, 234, 0.3);
    padding: 1.5rem;
    margin-bottom: 2rem;
  }
  
  .csv-info-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-right: 1rem;
  }
</style>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex align-items-center justify-content-between">
        <h1 class="mb-0 d-flex align-items-center gap-3">
          <svg width="48" height="48" style="color: #667eea; filter: drop-shadow(0 0 10px rgba(102, 126, 234, 0.6));" fill="currentColor" viewBox="0 0 16 16">
            <path d="M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5L14 4.5ZM3.517 14.841a1.13 1.13 0 0 0 .401.823c.13.108.289.192.478.252.19.061.411.091.665.091.338 0 .624-.053.859-.158.236-.105.416-.252.539-.44.125-.189.187-.408.187-.656 0-.224-.045-.41-.134-.56a1.001 1.001 0 0 0-.375-.357 2.027 2.027 0 0 0-.566-.21l-.621-.144a.97.97 0 0 1-.404-.176.37.37 0 0 1-.144-.299c0-.156.062-.284.185-.384.125-.101.296-.152.512-.152.143 0 .266.023.37.068a.624.624 0 0 1 .246.181.56.56 0 0 1 .12.258h.75a1.092 1.092 0 0 0-.2-.566 1.21 1.21 0 0 0-.5-.41 1.813 1.813 0 0 0-.78-.152c-.293 0-.551.05-.776.15-.225.099-.4.24-.527.421-.127.182-.19.395-.19.639 0 .201.04.376.122.524.082.149.2.27.352.367.152.095.332.167.539.213l.618.144c.207.049.361.113.463.193a.387.387 0 0 1 .152.326.505.505 0 0 1-.085.29.559.559 0 0 1-.255.193c-.111.047-.249.07-.413.07-.117 0-.223-.013-.32-.04a.838.838 0 0 1-.248-.115.578.578 0 0 1-.255-.384h-.765ZM.806 13.693c0-.248.034-.46.102-.633a.868.868 0 0 1 .302-.399.814.814 0 0 1 .475-.137c.15 0 .283.032.398.097a.7.7 0 0 1 .272.26.85.85 0 0 1 .12.381h.765v-.072a1.33 1.33 0 0 0-.466-.964 1.441 1.441 0 0 0-.489-.272 1.838 1.838 0 0 0-.606-.097c-.356 0-.66.074-.911.223-.25.148-.44.359-.572.632-.13.274-.196.6-.196.979v.498c0 .379.064.704.193.976.131.271.322.48.572.626.25.145.554.217.914.217.293 0 .554-.055.785-.164.23-.11.414-.26.55-.454a1.27 1.27 0 0 0 .226-.674v-.076h-.764a.799.799 0 0 1-.118.363.7.7 0 0 1-.272.25.874.874 0 0 1-.401.087.845.845 0 0 1-.478-.132.833.833 0 0 1-.299-.392 1.699 1.699 0 0 1-.102-.627v-.495Zm8.239 2.238h-.953l-1.338-3.999h.917l.896 3.138h.038l.888-3.138h.879l-1.327 3.999Z"/>
          </svg>
          <span style="background: linear-gradient(135deg, #667eea, #00ffff); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            Телеметрия Pascal Legacy
          </span>
        </h1>
        <a href="{{ route('telemetry.export') }}" class="export-btn">
          📊 Экспорт в XLSX
        </a>
      </div>
    </div>
  </div>
  
  <!-- CSV Info Card -->
  <div class="csv-info-card">
    <div class="d-flex align-items-center">
      <div class="csv-info-icon">📄</div>
      <div>
        <h5 class="mb-1" style="color: #00ffff;">{{ $filename }}</h5>
        <p class="mb-0 text-muted">
          Последнее обновление: {{ $timestamp ? date('d.m.Y H:i:s', $timestamp) : 'N/A' }} 
          • Записей: {{ count($telemetry) }}
        </p>
      </div>
    </div>
  </div>
  
  <!-- DataTable -->
  <div class="card shadow-lg" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 15px;">
    <div class="card-body">
      <table id="telemetryTable" class="table table-hover w-100">
        <thead>
          <tr>
            @foreach($headers as $header)
              <th>{{ ucfirst(str_replace('_', ' ', $header)) }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($telemetry as $row)
            <tr>
              @foreach($row as $idx => $cell)
                <td>
                  @if($headers[$idx] === 'sensor_active')
                    <span class="badge-boolean-{{ strtolower($cell) }}">{{ $cell }}</span>
                  @elseif(in_array($headers[$idx], ['voltage', 'temp']))
                    <strong style="color: #00ffff;">{{ $cell }}</strong>
                  @elseif($headers[$idx] === 'recorded_at')
                    <span style="color: #38ef7d;">{{ $cell }}</span>
                  @else
                    {{ $cell }}
                  @endif
                </td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
  $('#telemetryTable').DataTable({
    // Client-side processing (данные уже в HTML)
    processing: false,
    serverSide: false,
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Все"]],
    order: [[0, 'desc']], // Сортировка по timestamp (первая колонка) по убыванию
    language: {
      search: "🔍 Поиск:",
      lengthMenu: "Показать _MENU_ записей",
      info: "Показано _START_-_END_ из _TOTAL_ записей",
      infoEmpty: "Нет данных",
      infoFiltered: "(отфильтровано из _MAX_ записей)",
      zeroRecords: "Записи не найдены",
      emptyTable: "Нет данных в таблице",
      paginate: {
        first: "⏮ Первая",
        previous: "◀ Назад",
        next: "Вперёд ▶",
        last: "Последняя ⏭"
      }
    },
    columnDefs: [
      {
        targets: 0, // recorded_at
        type: 'date',
        render: function(data, type, row) {
          if (type === 'sort' || type === 'type') {
            return data; // Используем ISO8601 для сортировки
          }
          return '<span style="color: #38ef7d;">' + data + '</span>';
        }
      },
      {
        targets: [1, 2], // voltage, temp
        render: function(data, type, row) {
          return '<strong style="color: #00ffff;">' + data + '</strong>';
        }
      },
      {
        targets: 3, // sensor_active
        render: function(data, type, row) {
          var badgeClass = data === 'TRUE' ? 'badge-boolean-true' : 'badge-boolean-false';
          return '<span class="' + badgeClass + '">' + data + '</span>';
        }
      }
    ]
  });
});
</script>
@endpush
@endsection
