<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'StudySync') }} — study tools that work together</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
                <x-application-logo style="width: 26px; height: 26px;" />
                StudySync
            </a>
            <div class="d-flex gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign up free</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <header class="py-5 py-lg-6">
        <div class="container py-lg-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="eyebrow mb-3">Built for the semester grind</div>
                    <h1 class="display-5 fw-bold mb-3" style="letter-spacing: -0.02em;">
                        Every study tool, in one tab you already have open.
                    </h1>
                    <p class="fs-5 text-secondary mb-4">
                        Calculate your GPA, generate citations, and run a shared study
                        room with live tasks and a synced Pomodoro timer — no separate
                        logins, no ten browser tabs.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('register') }}" class="btn btn-accent btn-lg px-4">Get started free</a>
                        <a href="{{ route('tools.gpa') }}" class="btn btn-outline-secondary btn-lg px-4">Try the GPA calculator</a>
                    </div>
                    <p class="small text-secondary mt-3 mb-0">
                        No account needed for the GPA calculator or citation generator.
                    </p>
                </div>

                <div class="col-lg-6">
                    <div class="d-flex justify-content-center justify-content-lg-end">
                        {{-- Signature element: a live-looking preview of a Study Room task list --}}
                        <div class="hero-mock">
                            <div class="hero-mock-header">
                                <div>
                                    <div class="fw-semibold small">Midterm Review — Room #4F2A</div>
                                    <div class="text-secondary" style="font-size: .78rem;">
                                        <span class="pulse-dot"></span>3 people studying now
                                    </div>
                                </div>
                                <span class="badge bg-light text-secondary border">24:58</span>
                            </div>
                            <div class="task-row done">
                                <input class="form-check-input" type="checkbox" checked disabled>
                                <span>Review Chapter 7 slides</span>
                            </div>
                            <div class="task-row done">
                                <input class="form-check-input" type="checkbox" checked disabled>
                                <span>Summarize lecture notes</span>
                            </div>
                            <div class="task-row">
                                <input class="form-check-input" type="checkbox" disabled>
                                <span>Practice problem set 3</span>
                            </div>
                            <div class="task-row">
                                <input class="form-check-input" type="checkbox" disabled>
                                <span>Make flashcards for exam</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Tools grid --}}
    <section class="py-5 bg-white border-top">
        <div class="container py-4">
            <div class="text-center mb-5">
                <div class="eyebrow mb-2">The toolkit</div>
                <h2 class="fw-bold">Five tools. One place to work.</h2>
            </div>

            <div class="row g-4">
                <div class="col-sm-6 col-lg-4">
                    <div class="card tool-card p-1">
                        <div class="card-body">
                            <div class="tool-icon mb-3" style="background: rgba(46,49,146,.08); color: #2E3192;">🧮</div>
                            <h3 class="h6 fw-bold">GPA Calculator</h3>
                            <p class="text-secondary small mb-3">Add courses and grades, see your weighted GPA update live. Supports 4.0 and percentage scales.</p>
                            <a href="{{ route('tools.gpa') }}" class="small fw-semibold">Open tool →</a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="card tool-card p-1">
                        <div class="card-body">
                            <div class="tool-icon mb-3" style="background: rgba(58,175,217,.12); color: #218CB0;">📎</div>
                            <h3 class="h6 fw-bold">Citation Generator</h3>
                            <p class="text-secondary small mb-3">Paste a URL or DOI, get an APA, MLA, or Chicago citation with one click to copy.</p>
                            <a href="{{ route('tools.citation') }}" class="small fw-semibold">Open tool →</a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="card tool-card p-1">
                        <div class="card-body">
                            <div class="tool-icon mb-3" style="background: rgba(18,165,148,.10); color: #12A594;">👥</div>
                            <h3 class="h6 fw-bold">Study Rooms</h3>
                            <p class="text-secondary small mb-3">Share a room code, keep a live task list, and run one synced Pomodoro timer for the group.</p>
                            @auth
                                <a href="{{ route('rooms.index') }}" class="small fw-semibold">Open tool →</a>
                            @else
                                <a href="{{ route('register') }}" class="small fw-semibold">Sign up to use →</a>
                            @endauth
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="card tool-card p-1">
                        <div class="card-body">
                            <div class="tool-icon mb-3" style="background: rgba(245,166,35,.14); color: #B87200;">📝</div>
                            <h3 class="h6 fw-bold">Notes Summarizer</h3>
                            <p class="text-secondary small mb-3">Upload a PDF or paste your notes and get a clean bullet-point summary.</p>
                            @auth
                                <a href="{{ route('tools.summarizer') }}" class="small fw-semibold">Open tool →</a>
                            @else
                                <a href="{{ route('register') }}" class="small fw-semibold">Sign up to use →</a>
                            @endauth
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="card tool-card p-1">
                        <div class="card-body">
                            <div class="tool-icon mb-3" style="background: rgba(228,87,46,.10); color: #E4572E;">🗂️</div>
                            <h3 class="h6 fw-bold">Flashcard Generator</h3>
                            <p class="text-secondary small mb-3">Turn your notes into flippable Q&amp;A flashcards, saved to revisit before the exam.</p>
                            @auth
                                <a href="{{ route('tools.flashcards') }}" class="small fw-semibold">Open tool →</a>
                            @else
                                <a href="{{ route('register') }}" class="small fw-semibold">Sign up to use →</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="border-top py-4">
        <div class="container d-flex justify-content-between align-items-center small text-secondary">
            <span>&copy; {{ date('Y') }} StudySync</span>
            <span>Built for the campus hackathon</span>
        </div>
    </footer>

</body>
</html>
