<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_session_id',
        'name',
        'joined_at',
        'finished_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function quizSession(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ParticipantAnswer::class);
    }

    public function correctCount(): int
    {
        return $this->answers->where('is_correct', true)->count();
    }

    public function wrongCount(): int
    {
        return $this->answers->where('is_correct', false)->count();
    }
}
