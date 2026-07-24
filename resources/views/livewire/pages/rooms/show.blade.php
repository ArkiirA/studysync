<?php

use App\Events\PomodoroStateChanged;
use App\Events\TaskAdded;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use App\Models\PomodoroSession;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public Room $room;

    // Tasks are kept as a plain array (not a Collection of models) so both
    // local updates and incoming Echo payloads can merge into it the same way.
    public array $tasks = [];
    public string $newTaskContent = '';

    public string $pomodoroStatus = 'idle';
    public ?string $pomodoroStartedAt = null;
    public int $pomodoroDuration = 1500; // 25 minutes
    public int $pomodoroElapsedBeforePause = 0;

    public function mount(Room $room): void
    {
        $this->authorize('view', $room);
        $this->room = $room;

        $this->tasks = $room->tasks()->with('creator')->oldest()->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'content' => $t->content,
                'is_done' => $t->is_done,
                'creator_name' => $t->creator->name,
            ])->toArray();

        $session = $room->pomodoroSession ?? PomodoroSession::create(['room_id' => $room->id, 'status' => 'idle', 'duration_seconds' => 1500, 'elapsed_before_pause' => 0]);
        $this->applySession($session);
    }

    // ---------------- Tasks ----------------

    public function addTask(): void
    {
        $this->authorize('update', $this->room);
        $this->validate(['newTaskContent' => ['required', 'string', 'max:255']]);

        $task = $this->room->tasks()->create([
            'created_by' => Auth::id(),
            'content' => $this->newTaskContent,
        ]);
        $task->load('creator');

        $this->tasks[] = [
            'id' => $task->id,
            'content' => $task->content,
            'is_done' => false,
            'creator_name' => $task->creator->name,
        ];
        $this->newTaskContent = '';

        broadcast(new TaskAdded($task));
    }

    public function toggleTask(int $taskId): void
    {
        $this->authorize('update', $this->room);

        $task = $this->room->tasks()->findOrFail($taskId);
        $task->is_done = ! $task->is_done;
        $task->save();

        foreach ($this->tasks as $i => $t) {
            if ($t['id'] === $taskId) {
                $this->tasks[$i]['is_done'] = $task->is_done;
            }
        }

        broadcast(new TaskUpdated($task));
    }

    public function deleteTask(int $taskId): void
    {
        $this->authorize('update', $this->room);

        $this->room->tasks()->where('id', $taskId)->delete();
        $this->tasks = array_values(array_filter($this->tasks, fn ($t) => $t['id'] !== $taskId));

        broadcast(new TaskDeleted($this->room->id, $taskId));
    }

    #[On('echo-private:room.{room.id},TaskAdded')]
    public function onTaskAdded(array $event): void
    {
        if (collect($this->tasks)->contains('id', $event['id'])) {
            return; // already added optimistically by the tab that created it
        }
        $this->tasks[] = $event;
    }

    #[On('echo-private:room.{room.id},TaskUpdated')]
    public function onTaskUpdated(array $event): void
    {
        foreach ($this->tasks as $i => $t) {
            if ($t['id'] === $event['id']) {
                $this->tasks[$i]['is_done'] = $event['is_done'];
            }
        }
    }

    #[On('echo-private:room.{room.id},TaskDeleted')]
    public function onTaskDeleted(array $event): void
    {
        $this->tasks = array_values(array_filter($this->tasks, fn ($t) => $t['id'] !== $event['id']));
    }

    // ---------------- Pomodoro ----------------
    // The server never ticks a clock. It only ever stores: status, the
    // instant the current run started, the configured duration, and how
    // many seconds were already used up before the last pause. Every client
    // (including the one that pressed the button) computes the remaining
    // time locally from those four numbers — see the Alpine block below.

    public function startTimer(): void
    {
        $this->authorize('update', $this->room);

        $session = $this->room->pomodoroSession ?? PomodoroSession::create(['room_id' => $this->room->id, 'status' => 'idle', 'duration_seconds' => 1500, 'elapsed_before_pause' => 0]);
        $session->status = 'running';
        $session->started_at = now();
        $session->save();

        $this->applySession($session);
        broadcast(new PomodoroStateChanged($session));
    }

    public function pauseTimer(): void
    {
        $this->authorize('update', $this->room);

        $session = $this->room->pomodoroSession;
        if (! $session || $session->status !== 'running') {
            return;
        }

        // Explicit timestamp subtraction rather than diffInSeconds() — its
        // sign convention differs between Carbon versions, and a negative
        // value here would overflow the unsigned elapsed_before_pause column.
        $elapsedThisRun = max(0, now()->getTimestamp() - $session->started_at->getTimestamp());
        $session->elapsed_before_pause = min(
            $session->duration_seconds,
            $session->elapsed_before_pause + $elapsedThisRun
        );
        $session->status = 'paused';
        $session->started_at = null;
        $session->save();

        $this->applySession($session);
        broadcast(new PomodoroStateChanged($session));
    }

    public function resetTimer(): void
    {
        $this->authorize('update', $this->room);

        $session = $this->room->pomodoroSession;
        if (! $session) {
            return;
        }

        $session->status = 'idle';
        $session->started_at = null;
        $session->elapsed_before_pause = 0;
        $session->save();

        $this->applySession($session);
        broadcast(new PomodoroStateChanged($session));
    }

    #[On('echo-private:room.{room.id},PomodoroStateChanged')]
    public function onPomodoroStateChanged(array $event): void
    {
        $this->pomodoroStatus = $event['status'];
        $this->pomodoroStartedAt = $event['started_at'];
        $this->pomodoroDuration = $event['duration_seconds'];
        $this->pomodoroElapsedBeforePause = $event['elapsed_before_pause'];
    }

    protected function applySession(PomodoroSession $session): void
    {
        $this->pomodoroStatus = $session->status;
        $this->pomodoroStartedAt = $session->started_at?->toIso8601String();
        $this->pomodoroDuration = $session->duration_seconds;
        $this->pomodoroElapsedBeforePause = $session->elapsed_before_pause;
    }
}; ?>

<div>
    <x-slot:header>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h4 fw-bold mb-0">{{ $room->name }}</h1>
                <span class="text-secondary small">
                    Room code:
                    <span class="font-monospace fw-semibold">{{ $room->code }}</span>
                </span>
            </div>
            <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary btn-sm" wire:navigate>Back to rooms</a>
        </div>
    </x-slot:header>

    <div class="row g-4">
        {{-- Shared task list --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Shared tasks</h2>

                    <form wire:submit="addTask" class="d-flex gap-2 mb-3">
                        <input type="text" class="form-control" placeholder="Add a task…" wire:model="newTaskContent">
                        <button type="submit" class="btn btn-primary flex-shrink-0">Add</button>
                    </form>
                    @error('newTaskContent') <div class="text-danger small mb-3">{{ $message }}</div> @enderror

                    @if (empty($tasks))
                        <p class="text-secondary small mb-0">No tasks yet — add the first one above.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($tasks as $task)
                                <li wire:key="task-{{ $task['id'] }}" class="list-group-item d-flex align-items-center gap-2 px-0">
                                    <input class="form-check-input mt-0 flex-shrink-0"
                                           type="checkbox"
                                           @checked($task['is_done'])
                                           wire:click="toggleTask({{ $task['id'] }})">
                                    <span class="flex-grow-1 {{ $task['is_done'] ? 'text-decoration-line-through text-secondary' : '' }}">
                                        {{ $task['content'] }}
                                    </span>
                                    <span class="small text-secondary d-none d-sm-inline">{{ $task['creator_name'] }}</span>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0" wire:click="deleteTask({{ $task['id'] }})" title="Remove">
                                        &times;
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Shared Pomodoro timer --}}
        <div class="col-lg-5">
            <div class="card h-100"
                 wire:key="pomodoro-{{ $pomodoroStatus }}-{{ $pomodoroStartedAt }}-{{ $pomodoroElapsedBeforePause }}-{{ $pomodoroDuration }}"
                 x-data="{
                     duration: {{ $pomodoroDuration }},
                     elapsedBeforePause: {{ $pomodoroElapsedBeforePause }},
                     startedAt: @js($pomodoroStartedAt),
                     status: @js($pomodoroStatus),
                     remaining: 0,
                     timer: null,
                     init() {
                         this.tick();
                         this.timer = setInterval(() => this.tick(), 250);
                     },
                     destroy() {
                         clearInterval(this.timer);
                     },
                     tick() {
                         let elapsed = this.elapsedBeforePause;
                         if (this.status === 'running' && this.startedAt) {
                             elapsed += (Date.now() - new Date(this.startedAt).getTime()) / 1000;
                         }
                         this.remaining = Math.max(0, Math.round(this.duration - elapsed));
                     },
                     get display() {
                         const m = Math.floor(this.remaining / 60).toString().padStart(2, '0');
                         const s = (this.remaining % 60).toString().padStart(2, '0');
                         return m + ':' + s;
                     },
                     get progressPct() {
                         return this.duration > 0 ? Math.max(0, Math.min(100, 100 - (this.remaining / this.duration) * 100)) : 0;
                     }
                 }">
                <div class="card-body p-4 text-center d-flex flex-column">
                    <h2 class="h6 fw-bold mb-3">Pomodoro timer</h2>

                    <div class="display-3 fw-bold mb-3" style="font-family: 'Sora', sans-serif; color: #2E3192;" x-text="display"></div>

                    <div class="progress mb-4" style="height: 8px;" role="progressbar">
                        <div class="progress-bar bg-success" :style="`width: ${progressPct}%`"></div>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mt-auto">
                        @if ($pomodoroStatus === 'running')
                            <button type="button" class="btn btn-outline-secondary" wire:click="pauseTimer">Pause</button>
                        @else
                            <button type="button" class="btn btn-primary" wire:click="startTimer">
                                {{ $pomodoroStatus === 'paused' ? 'Resume' : 'Start' }}
                            </button>
                        @endif
                        <button type="button" class="btn btn-outline-danger" wire:click="resetTimer">Reset</button>
                    </div>

                    <p class="small text-secondary mt-3 mb-0">
                        Synced for everyone in this room — starting or pausing
                        updates it for all members instantly.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
