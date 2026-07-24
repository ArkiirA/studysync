// Bootstrap's JS bundle (includes Popper) — needed for navbar collapse,
// dropdowns, toasts, modals, etc. driven by data-bs-* attributes.
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Reverb/Echo client — see resources/js/echo.js. Needed by the Study Room
// pages (Livewire's #[On('echo-private:...')] listeners rely on window.Echo
// existing before the Livewire component mounts).
import './echo';

// NOTE: do NOT import/init Alpine.js here. Livewire v3 already bundles and
// starts its own copy of Alpine via @livewireScripts — a second, separately
// initialized Alpine instance will silently fight the Livewire one and cause
// x-data components to misbehave. Just use x-data/x-on/etc. in Blade as
// normal; it's available globally once Livewire's scripts have loaded.
