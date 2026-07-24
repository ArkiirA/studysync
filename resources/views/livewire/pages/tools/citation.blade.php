<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    public string $sourceType = 'manual'; // 'url' | 'doi' | 'manual'
    public string $style = 'apa';         // 'apa' | 'mla' | 'chicago'

    public string $lookupUrl = '';
    public string $lookupDoi = '';
    public string $fetchError = '';
    public bool $fetching = false;

    // Fields that actually build the citation, regardless of source.
    public string $author = '';
    public string $title = '';
    public string $siteName = '';
    public string $publishDate = '';
    public string $sourceUrl = '';
    public string $accessDate = '';

    public function mount(): void
    {
        $this->accessDate = now()->format('F j, Y');
    }

    public function fetchFromUrl(): void
    {
        $this->fetchError = '';
        $url = trim($this->lookupUrl);

        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->fetchError = 'Enter a valid URL first.';
            return;
        }

        $this->fetching = true;

        try {
            $response = Http::timeout(8)
                ->withUserAgent('StudySyncCitationBot/1.0')
                ->get($url);

            if (! $response->successful()) {
                $this->fetchError = "Couldn't reach that page (HTTP {$response->status()}). You can still fill in the fields manually.";
                $this->sourceUrl = $url;
                return;
            }

            $html = $response->body();

            $this->title = $this->extractMeta($html, ['og:title'])
                ?? $this->extractTag($html, 'title')
                ?? '';

            $this->siteName = $this->extractMeta($html, ['og:site_name'])
                ?? parse_url($url, PHP_URL_HOST)
                ?? '';

            $this->author = $this->extractMeta($html, ['author', 'article:author']) ?? '';

            $rawDate = $this->extractMeta($html, ['article:published_time', 'date', 'og:updated_time']);
            $this->publishDate = $rawDate ? $this->formatDate($rawDate) : '';

            $this->sourceUrl = $url;

            if (! $this->title) {
                $this->fetchError = "Fetched the page but couldn't find a title automatically — fill it in below.";
            }
        } catch (\Throwable $e) {
            $this->fetchError = "Couldn't fetch that URL. You can fill in the fields manually instead.";
            $this->sourceUrl = $url;
        } finally {
            $this->fetching = false;
        }
    }

    public function fetchFromDoi(): void
    {
        $this->fetchError = '';
        $doi = trim($this->lookupDoi);
        $doi = preg_replace('#^https?://(dx\.)?doi\.org/#i', '', $doi);

        if (! $doi) {
            $this->fetchError = 'Enter a DOI first.';
            return;
        }

        $this->fetching = true;

        try {
            $response = Http::timeout(8)->get("https://api.crossref.org/works/" . urlencode($doi));

            if (! $response->successful()) {
                $this->fetchError = "Couldn't find that DOI in Crossref. You can fill in the fields manually.";
                return;
            }

            $work = $response->json('message', []);

            $this->title = $work['title'][0] ?? '';
            $this->siteName = $work['container-title'][0] ?? ($work['publisher'] ?? '');
            $this->sourceUrl = $work['URL'] ?? "https://doi.org/{$doi}";

            $authors = collect($work['author'] ?? [])
                ->map(fn ($a) => trim(($a['family'] ?? '') . ($a['given'] ?? '' ? ', ' . $a['given'] : '')))
                ->filter()
                ->values();
            $this->author = $authors->count() > 1
                ? $authors->first() . ' et al.'
                : ($authors->first() ?? '');

            $parts = $work['published']['date-parts'][0] ?? $work['issued']['date-parts'][0] ?? null;
            $this->publishDate = $parts ? $this->dateFromParts($parts) : '';

            if (! $this->title) {
                $this->fetchError = "Found the DOI but couldn't read a title — fill it in below.";
            }
        } catch (\Throwable $e) {
            $this->fetchError = "Couldn't reach Crossref right now. You can fill in the fields manually.";
        } finally {
            $this->fetching = false;
        }
    }

    public function getCitationProperty(): string
    {
        $title = trim($this->title) ?: '[Untitled source]';
        $author = trim($this->author);
        $site = trim($this->siteName);
        $url = trim($this->sourceUrl);
        $date = trim($this->publishDate);
        $access = trim($this->accessDate);

        return match ($this->style) {
            'mla' => $this->buildMla($author, $title, $site, $date, $url, $access),
            'chicago' => $this->buildChicago($author, $title, $site, $date, $url, $access),
            default => $this->buildApa($author, $title, $site, $date, $url, $access),
        };
    }

    protected function buildApa(string $author, string $title, string $site, string $date, string $url, string $access): string
    {
        $year = $date ? '(' . $this->yearFrom($date) . '). ' : '(n.d.). ';
        $authorPart = $author ? rtrim($author, '.') . '. ' : '';
        $sitePart = $site ? $site . '. ' : '';
        $urlPart = $url ? $url : '';

        return trim("{$authorPart}{$year}{$title}. {$sitePart}{$urlPart}");
    }

    protected function buildMla(string $author, string $title, string $site, string $date, string $url, string $access): string
    {
        $authorPart = $author ? rtrim($author, '.') . '. ' : '';
        $datePart = $date ? $date . ', ' : '';
        $accessPart = $url ? "Accessed {$access}." : '';

        return trim("{$authorPart}\"{$title}.\" {$site}, {$datePart}{$url}. {$accessPart}");
    }

    protected function buildChicago(string $author, string $title, string $site, string $date, string $url, string $access): string
    {
        $authorPart = $author ? rtrim($author, '.') . '. ' : '';
        $datePart = $date ? $date . '. ' : '';

        return trim("{$authorPart}\"{$title}.\" {$site}. {$datePart}{$url}.");
    }

    protected function yearFrom(string $date): string
    {
        if (preg_match('/\b(\d{4})\b/', $date, $m)) {
            return $m[1];
        }
        return 'n.d.';
    }

    protected function formatDate(string $raw): string
    {
        try {
            return \Carbon\Carbon::parse($raw)->format('F j, Y');
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    protected function dateFromParts(array $parts): string
    {
        $year = $parts[0] ?? null;
        $month = $parts[1] ?? 1;
        $day = $parts[2] ?? 1;

        if (! $year) {
            return '';
        }

        try {
            return \Carbon\Carbon::create($year, $month, $day)->format('F j, Y');
        } catch (\Throwable $e) {
            return (string) $year;
        }
    }

    protected function extractTag(string $html, string $tag): ?string
    {
        if (preg_match("#<{$tag}[^>]*>(.*?)</{$tag}>#is", $html, $m)) {
            return trim(html_entity_decode(strip_tags($m[1])));
        }
        return null;
    }

    protected function extractMeta(string $html, array $names): ?string
    {
        foreach ($names as $name) {
            $pattern = '#<meta[^>]+(?:name|property)=["\']' . preg_quote($name, '#') . '["\'][^>]+content=["\']([^"\']+)["\']#i';
            if (preg_match($pattern, $html, $m)) {
                return trim(html_entity_decode($m[1]));
            }
            // content attribute can come before name/property
            $pattern2 = '#<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:name|property)=["\']' . preg_quote($name, '#') . '["\']#i';
            if (preg_match($pattern2, $html, $m)) {
                return trim(html_entity_decode($m[1]));
            }
        }
        return null;
    }
}; ?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="text-center mb-4">
            <div class="eyebrow mb-2">Free tool — no account needed</div>
            <h1 class="h3 fw-bold mb-2">Citation Generator</h1>
            <p class="text-secondary">Pull details from a URL or DOI, or enter them yourself — then copy the formatted citation.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <ul class="nav nav-pills nav-fill mb-3 small">
                            <li class="nav-item">
                                <button type="button" class="nav-link {{ $sourceType === 'url' ? 'active' : '' }}" wire:click="$set('sourceType', 'url')">From URL</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link {{ $sourceType === 'doi' ? 'active' : '' }}" wire:click="$set('sourceType', 'doi')">From DOI</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link {{ $sourceType === 'manual' ? 'active' : '' }}" wire:click="$set('sourceType', 'manual')">Manual entry</button>
                            </li>
                        </ul>

                        @if ($fetchError)
                            <div class="alert alert-warning py-2 small">{{ $fetchError }}</div>
                        @endif

                        @if ($sourceType === 'url')
                            <div class="input-group mb-3">
                                <input type="url" class="form-control" placeholder="https://example.com/article" wire:model="lookupUrl" wire:keydown.enter="fetchFromUrl">
                                <button class="btn btn-primary" wire:click="fetchFromUrl" wire:loading.attr="disabled" wire:target="fetchFromUrl">
                                    <span wire:loading.remove wire:target="fetchFromUrl">Fetch</span>
                                    <span wire:loading wire:target="fetchFromUrl">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                    </span>
                                </button>
                            </div>
                            <p class="small text-secondary">Auto-fill is best-effort — always double-check the fields below.</p>
                        @elseif ($sourceType === 'doi')
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="10.1000/xyz123" wire:model="lookupDoi" wire:keydown.enter="fetchFromDoi">
                                <button class="btn btn-primary" wire:click="fetchFromDoi" wire:loading.attr="disabled" wire:target="fetchFromDoi">
                                    <span wire:loading.remove wire:target="fetchFromDoi">Fetch</span>
                                    <span wire:loading wire:target="fetchFromDoi">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                    </span>
                                </button>
                            </div>
                            <p class="small text-secondary">Looked up via Crossref — works for most journal articles.</p>
                        @endif

                        <hr class="my-3">

                        <div class="mb-3">
                            <x-input-label value="Author" />
                            <x-text-input wire:model.live="author" type="text" placeholder="Last, First (or organization name)" />
                        </div>
                        <div class="mb-3">
                            <x-input-label value="Title" />
                            <x-text-input wire:model.live="title" type="text" />
                        </div>
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <x-input-label value="Site / publisher" />
                                <x-text-input wire:model.live="siteName" type="text" />
                            </div>
                            <div class="col-sm-6 mb-3">
                                <x-input-label value="Publish date" />
                                <x-text-input wire:model.live="publishDate" type="text" placeholder="e.g. March 4, 2024" />
                            </div>
                        </div>
                        <div class="mb-3">
                            <x-input-label value="URL" />
                            <x-text-input wire:model.live="sourceUrl" type="text" />
                        </div>
                        <div class="mb-1">
                            <x-input-label value="Access date (MLA)" />
                            <x-text-input wire:model.live="accessDate" type="text" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card" style="position: sticky; top: 1rem;">
                    <div class="card-body p-4">
                        <div class="btn-group btn-group-sm w-100 mb-3" role="group">
                            <input type="radio" class="btn-check" name="style" id="styleApa" wire:model.live="style" value="apa" autocomplete="off">
                            <label class="btn btn-outline-primary" for="styleApa">APA</label>

                            <input type="radio" class="btn-check" name="style" id="styleMla" wire:model.live="style" value="mla" autocomplete="off">
                            <label class="btn btn-outline-primary" for="styleMla">MLA</label>

                            <input type="radio" class="btn-check" name="style" id="styleChicago" wire:model.live="style" value="chicago" autocomplete="off">
                            <label class="btn btn-outline-primary" for="styleChicago">Chicago</label>
                        </div>

                        <div x-data="{
                                copy() {
                                    navigator.clipboard.writeText($refs.citationText.innerText);
                                    const toastEl = $refs.copiedToast;
                                    const toast = window.bootstrap.Toast.getOrCreateInstance(toastEl);
                                    toast.show();
                                }
                             }">
                            <div class="border rounded-3 p-3 bg-light small" style="min-height: 110px;" x-ref="citationText">
                                {{ $this->citation }}
                            </div>

                            <button type="button" class="btn btn-accent btn-sm w-100 mt-3" x-on:click="copy()">
                                Copy citation
                            </button>

                            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                                <div x-ref="copiedToast" class="toast align-items-center text-bg-dark border-0" role="status">
                                    <div class="d-flex">
                                        <div class="toast-body small">Copied to clipboard</div>
                                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
