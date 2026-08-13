<nav class="navbar-custom">

  <div>

    <h2>{{ $title }}</h2>

    <p>{{ $subtitle }}</p>

  </div>

  <div class="storage-card">

    <div>

      <strong>{{ $storageStats['usedDisplay'] ?? '0.00' }} / {{ $storageStats['maxGb'] ?? 4 }} GB</strong>

      <small>Storage Used</small>

    </div>

    <span>{{ $storageStats['percent'] ?? 0 }}%</span>

  </div>

</nav>
