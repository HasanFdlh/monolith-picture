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

        <li>
            <a href="#">
                <i class="fa-solid fa-camera"></i>
                Booth
            </a>
        </li>

        <li>
            <a href="#">
                <i class="fa-solid fa-folder-open"></i>
                Sessions
            </a>
        </li>
    </ul>

    <div class="logout">
        <a href="#">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>

</div>
