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
