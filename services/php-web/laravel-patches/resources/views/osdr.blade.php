@extends('layouts.app')

@section('content')
<style>
  /* DataTables Custom Styling for OSDR */
  .osdr-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 700;
  }
  
  .source-badge {
    background: linear-gradient(135deg, #11998e, #38ef7d);
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    display: inline-block;
    box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
  }
  
  table.dataTable thead th {
    background: linear-gradient(135deg, #667eea, #764ba2) !important;
    color: white !important;
    font-weight: 600;
    padding: 1rem !important;
    border-bottom: 2px solid #00ffff !important;
  }
  
  table.dataTable tbody tr {
    transition: all 0.3s ease;
  }
  
  table.dataTable tbody tr:hover {
    background: rgba(102, 126, 234, 0.15) !important;
    transform: translateX(3px);
  }
  
  .btn-json {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 0.3rem 0.8rem;
    border-radius: 6px;
    font-size: 0.8rem;
    transition: all 0.3s ease;
  }
  
  .btn-json:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.5);
    color: white;
  }
</style>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex align-items-center justify-content-between">
        <h1 class="mb-0 d-flex align-items-center gap-3">
          <svg width="48" height="48" style="color: #667eea; filter: drop-shadow(0 0 10px rgba(102, 126, 234, 0.6));" fill="currentColor" viewBox="0 0 16 16">
            <path d="M4.318 2.687C5.234 2.271 6.536 2 8 2s2.766.27 3.682.687C12.644 3.125 13 3.627 13 4c0 .374-.356.875-1.318 1.313C10.766 5.729 9.464 6 8 6s-2.766-.27-3.682-.687C3.356 4.875 3 4.373 3 4c0-.374.356-.875 1.318-1.313M13 5.698V7c0 .374-.356.875-1.318 1.313C10.766 8.729 9.464 9 8 9s-2.766-.27-3.682-.687C3.356 7.875 3 7.373 3 7V5.698c.271.202.58.378.904.525C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777A5 5 0 0 0 13 5.698M14 4c0-1.007-.875-1.755-1.904-2.223C11.022 1.289 9.573 1 8 1s-3.022.289-4.096.777C2.875 2.245 2 2.993 2 4v9c0 1.007.875 1.755 1.904 2.223C4.978 15.71 6.427 16 8 16s3.022-.289 4.096-.777C13.125 14.755 14 14.007 14 13zm-1 4.698V10c0 .374-.356.875-1.318 1.313C10.766 11.729 9.464 12 8 12s-2.766-.27-3.682-.687C3.356 10.875 3 10.373 3 10V8.698c.271.202.58.378.904.525C4.978 9.71 6.427 10 8 10s3.022-.289 4.096-.777c.324-.147.633-.323.904-.525M13 11.698V13c0 .374-.356.875-1.318 1.313C10.766 14.729 9.464 15 8 15s-2.766-.27-3.682-.687C3.356 13.875 3 13.373 3 13v-1.302c.271.202.58.378.904.525C4.978 12.71 6.427 13 8 13s3.022-.289 4.096-.777c.324-.147.633-.323.904-.525"/>
          </svg>
          <span class="osdr-header">NASA OSDR Dataset</span>
        </h1>
        <span class="source-badge">📡 Источник: {{ $src }}</span>
      </div>
    </div>
  </div>

  <div class="card shadow-lg" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 15px;">
    <div class="card-body">
      <table id="osdrTable" class="table table-hover w-100">
        <thead>
          <tr>
            <th>ID</th>
            <th>Dataset ID</th>
            <th>Title</th>
            <th>REST URL</th>
            <th>Updated</th>
            <th>Inserted</th>
            <th>JSON</th>
          </tr>
        </thead>
        <tbody>
        @forelse($items as $row)
          <tr>
            <td><strong style="color: #00ffff;">{{ $row['id'] }}</strong></td>
            <td><span class="badge" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">{{ $row['dataset_id'] ?? '—' }}</span></td>
            <td style="max-width:420px;">
              <span style="color: #e0e0e0;">{{ $row['title'] ?? '—' }}</span>
            </td>
            <td>
              @if(!empty($row['rest_url']))
                <a href="{{ $row['rest_url'] }}" target="_blank" rel="noopener" style="color: #00ffff; text-decoration: none;">
                  🔗 Открыть
                </a>
              @else 
                <span style="color: #666;">—</span>
              @endif
            </td>
            <td style="color: #38ef7d;">{{ $row['updated_at'] ?? '—' }}</td>
            <td style="color: #999;">{{ $row['inserted_at'] ?? '—' }}</td>
            <td>
              <button class="btn-json" data-bs-toggle="collapse" data-bs-target="#raw-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">
                📄 JSON
              </button>
            </td>
          </tr>
          <tr class="collapse" id="raw-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">
            <td colspan="7">
              <pre class="mb-0" style="max-height:300px; overflow:auto; background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; color: #00ffff;">{{ json_encode($row['raw'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) }}</pre>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted">Нет данных</td></tr>
        @endforelse
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
  $('#osdrTable').DataTable({
    // Client-side processing
    processing: false,
    serverSide: false,
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Все"]],
    order: [[0, 'desc']], // Сортировка по ID по убыванию
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
        targets: 0, // ID
        width: '60px',
        className: 'text-center'
      },
      {
        targets: 1, // Dataset ID
        width: '150px'
      },
      {
        targets: 2, // Title
        width: '40%',
        render: function(data, type, row) {
          if (type === 'display' && data.length > 60) {
            return '<span title="' + data + '">' + data.substr(0, 60) + '...</span>';
          }
          return data;
        }
      },
      {
        targets: 3, // REST URL
        width: '100px',
        orderable: false
      },
      {
        targets: 4, // Updated
        type: 'date',
        width: '150px'
      },
      {
        targets: 5, // Inserted
        type: 'date',
        width: '150px'
      },
      {
        targets: 6, // JSON button
        orderable: false,
        searchable: false,
        width: '80px',
        className: 'text-center'
      }
    ]
  });
});
</script>
@endpush
@endsection
