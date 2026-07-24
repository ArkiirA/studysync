@props(['question', 'answer'])

<div class="flip-card" x-data="{ flipped: false }" :class="{ 'is-flipped': flipped }" @click="flipped = !flipped">
    <div class="flip-card-inner">
        <div class="flip-card-face flip-card-front">
            <div class="eyebrow mb-2">Question</div>
            <div class="flex-grow-1 fw-medium">{{ $question }}</div>
            <div class="small text-secondary mt-2">Tap to flip</div>
        </div>
        <div class="flip-card-face flip-card-back">
            <div class="eyebrow mb-2" style="color: rgba(255,255,255,.75);">Answer</div>
            <div class="flex-grow-1">{{ $answer }}</div>
            <div class="small mt-2" style="color: rgba(255,255,255,.65);">Tap to flip back</div>
        </div>
    </div>
</div>
