<div class="sidebar">

    <div class="logo">
        <div class="brand-badge"></div>
        <div class="brand-copy">
            <h3>Luminash Drive</h3>
            <small>Photobooth Media</small>
        </div>
    </div>

    <ul>
        <li>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a href="{{ route('printed.index') }}" class="{{ request()->routeIs('printed.*') ? 'active' : '' }}">
                <i class="fa-solid fa-print"></i>
                Hasil Print
                @php($printedCount = \App\Models\Session::printed()->count())
                @if ($printedCount > 0)
                    <span class="badge bg-warning text-dark ms-auto">{{ $printedCount > 99 ? '99+' : $printedCount }}</span>
                @endif
            </a>
        </li>

        <li>
            <a href="{{ route('branding') }}" class="{{ request()->routeIs('branding') ? 'active' : '' }}">
                <i class="fa-solid fa-palette"></i>
                Branding
            </a>
        </li>

        <li>
            <a href="{{ route('frames.index') }}" class="{{ request()->routeIs('frames.*') ? 'active' : '' }}">
                <i class="fa-regular fa-image"></i>
                Photo Frames
            </a>
        </li>

        <li>
            <a href="{{ route('frame-backgrounds.index') }}" class="{{ request()->routeIs('frame-backgrounds.*') ? 'active' : '' }}">
                <i class="fa-solid fa-fill-drip"></i>
                Frame Backgrounds
            </a>
        </li>

        <li>
            <a href="{{ route('packages.index') }}" class="{{ request()->routeIs('packages.*') ? 'active' : '' }}">
                <i class="fa-solid fa-tags"></i>
                Paket Foto
            </a>
        </li>

        <li>
            <a href="{{ url('/checkout') }}" target="_blank">
                <i class="fa-solid fa-cash-register"></i>
                Mulai Sesi (Checkout)
            </a>
        </li>

        <li>
            <a href="{{ route('photobooth-test') }}" class="{{ request()->routeIs('photobooth-test') ? 'active' : '' }}">
                <i class="fa-solid fa-flask"></i>
                API Test
            </a>
        </li>
    </ul>

    <div class="logout">
        <form id="logout-form" action="{{ url('/logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-logout" style="background:transparent;border:0;color:rgba(255,255,255,0.85);padding:12px 14px;display:flex;align-items:center;gap:12px;cursor:pointer;">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </button>
        </form>
    </div>

</div>
