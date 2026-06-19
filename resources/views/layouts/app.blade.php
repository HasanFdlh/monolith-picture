<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>{{ config('app.name') }}</title>

  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: #f5f6fa;
    }

    .sidebar {
      width: 240px;
      height: 100vh;
      position: fixed;
      background: #111827;
      color: white;
    }

    .sidebar a {
      color: #cbd5e1;
      text-decoration: none;
      display: block;
      padding: 10px 15px;
    }

    .sidebar a:hover {
      background: #1f2937;
      color: white;
    }

    .content {
      margin-left: 240px;
      padding: 20px;
    }
  </style>
</head>

<body>

  <div class="sidebar">
    <h5 class="p-3">{{ config('app.name') }}</h5>

    <a href="/dashboard">Dashboard</a>

    {{-- hanya admin --}}
    @if (auth()->user()->hasRole('Admin'))
      <a href="/users">Users</a>
    @endif

    <hr>

    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
      Logout
    </a>

    <form id="logout-form" action="/logout" method="POST" style="display:none;">
      @csrf
    </form>
  </div>

  <div class="content">
    @yield('content')
  </div>

  <!-- ⚠️ IMPORTANT: Bootstrap JS harus di bawah -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- OPTIONAL: stack scripts dari view -->
  @yield('scripts')

</body>

</html>
