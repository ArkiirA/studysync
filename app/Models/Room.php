<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'created_by'];

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            $room->code ??= static::generateUniqueCode();
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            // Uppercase, no ambiguous chars (0/O, 1/I) — easier to read out loud or type.
            $code = Str::upper(Str::random(6, 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_members')
            ->withPivot('joined_at');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->latest();
    }

    public function pomodoroSession(): HasOne
    {
        return $this->hasOne(PomodoroSession::class);
    }
}
