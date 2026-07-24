<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    // 4.0-scale letter grade → grade points. Common US convention.
    protected array $gradePoints = [
        'A'  => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B'  => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C'  => 2.0, 'C-' => 1.7,
        'D+' => 1.3, 'D'  => 1.0, 'D-' => 0.7,
        'F'  => 0.0,
    ];

    public string $scale = '4.0'; // '4.0' | 'percentage'
    public int $nextId = 1;

    // Each course: ['id' => int, 'name' => string, 'credits' => float, 'grade' => string|float]
    public array $courses = [];

    public function mount(): void
    {
        $this->courses = [
            ['id' => 1, 'name' => '', 'credits' => 3, 'grade' => 'A'],
            ['id' => 2, 'name' => '', 'credits' => 3, 'grade' => 'B+'],
        ];
        $this->nextId = 3;
    }

    public function addCourse(): void
    {
        $this->courses[] = [
            'id' => $this->nextId++,
            'name' => '',
            'credits' => 3,
            'grade' => $this->scale === '4.0' ? 'A' : 90,
        ];
    }

    public function removeCourse(int $id): void
    {
        $this->courses = array_values(array_filter($this->courses, fn ($c) => $c['id'] !== $id));
    }

    public function updatedScale(): void
    {
        // Reset grade values to sensible defaults when switching scales,
        // since a letter grade and a percentage aren't interchangeable.
        foreach ($this->courses as $i => $course) {
            $this->courses[$i]['grade'] = $this->scale === '4.0' ? 'A' : 90;
        }
    }

    public function getResultProperty(): array
    {
        $totalCredits = 0.0;
        $totalPoints = 0.0;

        foreach ($this->courses as $course) {
            $credits = (float) ($course['credits'] ?? 0);
            if ($credits <= 0) {
                continue;
            }

            $value = $this->scale === '4.0'
                ? ($this->gradePoints[$course['grade']] ?? 0)
                : (float) ($course['grade'] ?? 0);

            $totalCredits += $credits;
            $totalPoints += $credits * $value;
        }

        if ($totalCredits <= 0) {
            return ['value' => null, 'credits' => 0];
        }

        return [
            'value' => round($totalPoints / $totalCredits, $this->scale === '4.0' ? 2 : 1),
            'credits' => $totalCredits,
        ];
    }

    public function letterGrades(): array
    {
        return array_keys($this->gradePoints);
    }
}; ?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="text-center mb-4">
            <div class="eyebrow mb-2">Free tool — no account needed</div>
            <h1 class="h3 fw-bold mb-2">GPA Calculator</h1>
            <p class="text-secondary">Add your courses, pick a grade, and your weighted GPA updates as you type.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label fw-medium mb-0">Grading scale</label>
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="scale" id="scale4" wire:model.live="scale" value="4.0" autocomplete="off">
                                <label class="btn btn-outline-primary" for="scale4">4.0 scale</label>

                                <input type="radio" class="btn-check" name="scale" id="scalePct" wire:model.live="scale" value="percentage" autocomplete="off">
                                <label class="btn btn-outline-primary" for="scalePct">Percentage</label>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr class="text-secondary small text-uppercase">
                                        <th style="width: 45%;">Course</th>
                                        <th style="width: 20%;">Credits</th>
                                        <th style="width: 25%;">Grade</th>
                                        <th style="width: 10%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($courses as $index => $course)
                                        <tr wire:key="course-{{ $course['id'] }}">
                                            <td>
                                                <input type="text"
                                                       class="form-control form-control-sm"
                                                       placeholder="e.g. Calculus II"
                                                       wire:model.live="courses.{{ $index }}.name">
                                            </td>
                                            <td>
                                                <input type="number" min="0" max="12" step="0.5"
                                                       class="form-control form-control-sm"
                                                       wire:model.live="courses.{{ $index }}.credits">
                                            </td>
                                            <td>
                                                @if ($scale === '4.0')
                                                    <select class="form-select form-select-sm" wire:model.live="courses.{{ $index }}.grade">
                                                        @foreach ($this->letterGrades() as $letter)
                                                            <option value="{{ $letter }}">{{ $letter }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input type="number" min="0" max="100" step="0.1"
                                                           class="form-control form-control-sm"
                                                           wire:model.live="courses.{{ $index }}.grade">
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        wire:click="removeCourse({{ $course['id'] }})"
                                                        @disabled(count($courses) <= 1)
                                                        title="Remove course">
                                                    &times;
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" wire:click="addCourse" class="btn btn-outline-secondary btn-sm mt-2">
                            + Add course
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card bg-white" style="position: sticky; top: 1rem;">
                    <div class="card-body p-4 text-center">
                        <div class="eyebrow mb-2">
                            {{ $scale === '4.0' ? 'Weighted GPA' : 'Weighted average' }}
                        </div>

                        @if ($this->result['value'] === null)
                            <div class="display-6 fw-bold text-secondary">—</div>
                            <p class="small text-secondary mb-0">Add at least one course with credits.</p>
                        @else
                            <div class="display-4 fw-bold" style="color: #2E3192; font-family: 'Sora', sans-serif;">
                                {{ $this->result['value'] }}{{ $scale === 'percentage' ? '%' : '' }}
                            </div>
                            <p class="small text-secondary mb-0">
                                across {{ rtrim(rtrim(number_format($this->result['credits'], 1), '0'), '.') }} credit{{ $this->result['credits'] == 1 ? '' : 's' }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
