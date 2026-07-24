# StudySync — Phase 1 setup

I couldn't run `composer` here (this sandbox can only reach npm/PyPI/GitHub,
not Packagist), so these files are written by hand to drop into a fresh
Laravel install. Run the commands below locally, then copy these files over
the generated ones.

## 1. Scaffold the app

```bash
composer create-project laravel/laravel studysync
cd studysync

composer require laravel/breeze --dev
php artisan breeze:install livewire   # choose "Yes" when asked about Volt — these files assume Volt syntax

npm install
```

## 2. Add Bootstrap + Sass, remove Tailwind's build deps

```bash
npm install bootstrap sass --save-dev
npm uninstall tailwindcss @tailwindcss/forms autoprefixer postcss
rm resources/css/app.css tailwind.config.js postcss.config.js 2>/dev/null
```

Breeze's Volt install scaffolds Tailwind by default — the `npm uninstall`
above removes it so nothing fights with Bootstrap in the build.

## 3. Copy these files into place

Copy everything in this bundle into your `studysync/` project root,
overwriting the matching Breeze-generated files:

```
vite.config.js
resources/sass/app.scss                                  (new)
resources/js/app.js                                       (overwrite)
resources/views/layouts/guest.blade.php                   (overwrite)
resources/views/layouts/app.blade.php                     (overwrite)
resources/views/layouts/public.blade.php                  (new)
resources/views/livewire/navigation-menu.blade.php        (overwrite)
resources/views/livewire/pages/auth/*.blade.php           (overwrite all 6)
resources/views/livewire/pages/dashboard.blade.php         (new — see step 4)
resources/views/livewire/pages/profile.blade.php          (overwrite)
resources/views/livewire/pages/tools/*.blade.php          (new)
resources/views/components/*.blade.php                    (overwrite all 7)
resources/views/welcome.blade.php                          (overwrite)
app/Livewire/NavigationMenu.php                            (overwrite)
routes/web.php                                              (overwrite)
routes/auth.php                                              (overwrite)
database/migrations/2026_01_01_000000_add_avatar_url_to_users_table.php (new — see note below)
```

**Migration timestamp:** rename the avatar migration so its timestamp is
*after* Breeze's `create_users_table` migration (Laravel runs migrations in
filename order). Check your `database/migrations/` folder and bump the
prefix if needed, e.g. `2026_01_01_000001_...`.

**Dashboard file location:** Breeze's Volt install generates
`resources/views/dashboard.blade.php` — delete that one, since this bundle's
dashboard now lives at `resources/views/livewire/pages/dashboard.blade.php`
and is routed via `Volt::route()` (needed so it can share the same
`#[Layout('layouts.app')]` + header-slot pattern as the other pages).

## 4. Public disk for avatars

```bash
php artisan storage:link
```

This makes `storage/app/public/avatars/...` reachable at `/storage/avatars/...`,
which is what `Storage::url()` returns in the profile page's avatar upload.

## 5. Migrate and run

```bash
php artisan migrate
npm run dev      # in one terminal
php artisan serve  # in another
```

Visit `/` for the landing page, `/register` to create an account, and
`/dashboard` once logged in.

## What's left for later phases

- Reverb, Study Rooms, and AI features aren't touched yet — that's Phase 3+.
- Google OAuth (Socialite) mentioned in the brief as optional isn't wired up
  here — say the word if you want it added to the login/register pages.

## Phase 3 notes (Study Rooms + Reverb realtime)

### Install Reverb

```bash
composer require laravel/reverb
php artisan install:broadcasting
```

When prompted, confirm you want Reverb as the broadcaster (it's already
required). This publishes `config/reverb.php`, sets `BROADCAST_CONNECTION`
in `.env`, adds the `REVERB_*` env vars, and — in recent Laravel versions —
registers `routes/channels.php` for you automatically.

**After running it**, overwrite the `routes/channels.php` it generated with
the one in this bundle (it contains the actual room-membership check).

**If channel subscriptions come back as 403s later:** open
`bootstrap/app.php` and confirm `channels: __DIR__.'/../routes/channels.php'`
is passed into `->withRouting(...)`. Some Laravel versions need this added
by hand if `install:broadcasting` didn't wire it up for your setup.

### Env vars

Check `.env` has (values from `install:broadcasting`, but confirm these four
since the frontend needs the `VITE_` versions too):

```
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Frontend deps

```bash
npm install laravel-echo pusher-js
```

`resources/js/echo.js` (in this bundle) sets up `window.Echo`; `app.js`
already imports it. Reverb speaks the Pusher protocol, which is why
`pusher-js` is a dependency even though you're not using Pusher itself.

### Copy these files in

```
app/Models/User.php                   (overwrite — adds avatar_url + room relations)
app/Models/Room.php                   (new)
app/Models/Task.php                   (new)
app/Models/PomodoroSession.php        (new)
app/Policies/RoomPolicy.php           (new)
app/Events/TaskAdded.php              (new)
app/Events/TaskUpdated.php            (new)
app/Events/TaskDeleted.php            (new)
app/Events/PomodoroStateChanged.php   (new)
database/migrations/2026_02_01_*.php  (new — 4 files)
routes/channels.php                   (overwrite, see above)
routes/web.php                        (overwrite)
resources/js/echo.js                  (new)
resources/js/app.js                   (overwrite — adds the echo.js import)
resources/views/livewire/pages/rooms/index.blade.php  (new)
resources/views/livewire/pages/rooms/show.blade.php   (new)
resources/views/livewire/pages/dashboard.blade.php    (overwrite)
resources/views/welcome.blade.php                     (overwrite)
```

Then:

```bash
php artisan migrate
```

### Running it locally — three processes now

```bash
php artisan serve       # terminal 1
npm run dev              # terminal 2
php artisan reverb:start # terminal 3
```

Open two different browsers (or one normal + one incognito window), log in
as two different users, create a room in one, join it with the code in the
other, and check that adding a task or starting the timer shows up live in
both.

### On `ShouldBroadcastNow`

The four events dispatch **synchronously** (no queue worker required) —
that's a deliberate simplification for demo reliability, called out in a
comment at the top of `TaskAdded.php`. If you ever push this past a small
demo, switch them to `ShouldBroadcast` and run `php artisan queue:work`
alongside the three processes above, so a slow broadcast can't block the
HTTP request that triggered it.

### One schema addition beyond the brief's spec

`pomodoro_sessions` has an extra `elapsed_before_pause` column (int,
seconds) beyond the `status` / `started_at` / `duration_seconds` in the
brief's data model. It's needed to pause-and-resume correctly without
restarting the countdown from zero — without it there's no way to know how
much time was already spent before a pause. Everything else matches the
brief's migrations as written.

### Deployment (Railway/Render)

Reverb is a **long-running process**, not a request/response endpoint — it
needs its own persistent process alongside `php-fpm`/`artisan serve` and the
queue (if you switch to one later). On Railway or Render this typically
means either:
- a second service in the same project running `php artisan reverb:start`, or
- a `Procfile`/start-command that runs both under a process manager.

Also: Reverb needs its port (`8080` by default) reachable from the browser —
on these platforms that usually means exposing it on its own public
domain/port rather than reusing the main app's. Check your host's docs for
"WebSocket support" or "additional process types" — the exact setup differs
enough between Railway and Render that it's worth confirming against
current docs rather than guessing here.

## Phase 4 notes (AI summarizer + flashcard generator)

### Install the PDF text-extraction package

```bash
composer require smalot/pdfparser
```
Used by the Notes Summarizer to pull text out of an uploaded PDF before
sending it to Gemini.

### Get a Gemini API key and add env vars

Grab a key from [Google AI Studio](https://aistudio.google.com/apikey), then
add to `.env`:

```
GEMINI_API_KEY=your-key-here
GEMINI_MODEL=gemini-2.5-flash
GEMINI_DAILY_LIMIT=15
```

`GEMINI_MODEL` and `GEMINI_DAILY_LIMIT` are optional — they default to
`gemini-2.5-flash` and `15` respectively if omitted. Model names change
fairly often; check [ai.google.dev/gemini-api/docs/models](https://ai.google.dev/gemini-api/docs/models)
if you want to try a newer/cheaper one (e.g. `gemini-3.6-flash` was GA as of
mid-2026).

### Copy these files in

```
app/Models/User.php                       (overwrite — adds flashcardSets relation)
app/Models/FlashcardSet.php               (new)
app/Policies/FlashcardSetPolicy.php       (new)
app/Services/GeminiService.php            (new)
config/services.php                       (overwrite — adds the gemini config block)
database/migrations/2026_03_01_*.php      (new)
routes/web.php                             (overwrite)
resources/sass/app.scss                    (overwrite — adds flip-card CSS)
resources/views/components/flashcard.blade.php        (new)
resources/views/livewire/pages/tools/summarizer.blade.php  (new)
resources/views/livewire/pages/tools/flashcards.blade.php  (new)
resources/views/livewire/pages/flashcards/index.blade.php  (new)
resources/views/livewire/pages/flashcards/show.blade.php   (new)
resources/views/livewire/pages/dashboard.blade.php    (overwrite)
resources/views/welcome.blade.php                      (overwrite)
```

Then:
```bash
php artisan migrate
```

### How rate limiting actually works here

The brief says "use Laravel's built-in rate limiter," but there's a subtlety
worth knowing: **route-level `throttle` middleware won't work for this**,
because every Livewire action (clicking "Summarize", "Generate flashcards",
etc.) POSTs to the same shared `/livewire/update` endpoint — not to
`/tools/summarizer` itself. Throttling that route would only limit page
*loads*, not repeated button clicks.

Instead, both AI components call the `RateLimiter` facade directly inside
their action methods (`RateLimiter::tooManyAttempts()` / `::hit()`), keyed
per-user per-tool (`ai-summarize:{userId}`, `ai-flashcards:{userId}`) with a
24-hour decay window. Still "Laravel's built-in rate limiter" — just applied
at the point that actually matters instead of at the route.

### Structured output, not prompt-and-hope

Rather than asking Gemini to "respond in JSON only" and hoping it listens,
`GeminiService` uses the API's `generationConfig.responseSchema` field to
force the shape of the response (an array of `{question, answer}` for
flashcards, `{bullets: [...]}` for summaries). This is meaningfully more
reliable than prompt-only JSON formatting — worth knowing if you extend this
service for other AI features later, since it's the right pattern to reuse.

### Testing without burning API calls

Gemini API usage costs money past the free tier. While testing the UI/flow
repeatedly, consider temporarily setting `GEMINI_DAILY_LIMIT=2` in `.env` so
you don't accidentally loop through dozens of calls while debugging a
Blade/CSS issue that had nothing to do with the AI response itself.

## Phase 2 notes (GPA calculator + citation generator)

Both tools are Volt components under `resources/views/livewire/pages/tools/`,
routed publicly (no auth middleware) at `/tools/gpa` and `/tools/citation`.

- **GPA calculator** recomputes on every keystroke via `wire:model.live` and
  a `getResultProperty()` computed property — no extra setup needed.
- **Citation generator**'s URL/DOI lookup uses Laravel's `Http` client
  (already available, no package to install) and the free
  [Crossref API](https://api.crossref.org) for DOIs — no API key required.
  URL metadata scraping is regex-based best-effort (reads `<title>` and a
  few `<meta>` tags); it won't work on JS-rendered pages, which is why every
  field stays manually editable.
- Copy-to-clipboard uses `navigator.clipboard`, which requires a **secure
  context** — it works on `localhost` during development, but once deployed
  you'll need HTTPS (Railway/Render give you this by default) for the copy
  button to function.

## Design tokens used (see comments in `app.scss` for the full rationale)

| Token | Value | Use |
|---|---|---|
| Primary | `#2E3192` | brand, links, primary buttons |
| Accent | `#F5A623` | the one warm accent — hero CTA, `.btn-accent` |
| Success | `#12A594` | "done"/active states — task checkmarks, room presence pulse |
| Background | `#F6F7FB` | app background (cool neutral, not cream) |
| Display font | Sora | headings only |
| Body font | Inter | everything else |

The landing page's hero deliberately shows a live-looking Study Room task
list instead of a stat block — it's the one feature that makes this app
different from a pile of separate tools, so it's what greets people first.
