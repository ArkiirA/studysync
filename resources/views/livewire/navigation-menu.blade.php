<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-2">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}" wire:navigate>
            <x-application-logo style="width: 26px; height: 26px;" />
            StudySync
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('dashboard') }}" wire:navigate>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tools.gpa') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('tools.gpa') }}" wire:navigate>
                        GPA Calculator
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tools.citation') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('tools.citation') }}" wire:navigate>
                        Citations
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=2E3192&color=fff' }}"
                             class="rounded-circle" width="28" height="28" alt="{{ auth()->user()->name }}'s avatar">
                        <span class="d-none d-lg-inline small fw-medium">{{ auth()->user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="{{ route('profile') }}" wire:navigate>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button wire:click="logout" class="dropdown-item">Log out</button>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
