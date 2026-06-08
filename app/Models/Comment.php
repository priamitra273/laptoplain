<?php

namespace App\Models;

use App\Facades\Sqids;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comments';

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'body',
        'reaction',
        'owned_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'parent_id',
    ];

    /**
     * Resolve route binding for comment
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (is_string($value) && ! ctype_digit($value)) {
            try {
                $value = Sqids::decode($value);
            } catch (\Throwable $e) {
                throw (new ModelNotFoundException)->setModel(static::class);
            }
        }

        return $this->where('id', $value)->firstOrFail();
    }

    /**
     * Relasi morph (polymorphic) ke model lain
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Relasi ke user yang menulis komentar
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke user pemilik (owner)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owned_id');
    }

    /**
     * Relasi ke user pembuat
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user pengubah
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->with('user', 'replies');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function reaction_group_count(): HasMany
    {
        return $this->reactions()->select('comment_id', 'reaction', DB::raw('COUNT(*) as count'))
            ->groupBy('comment_id', 'reaction')
            ->orderBy('count', 'desc');
    }

    protected function currentUserReaction(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! Auth::check()) {
                return null;
            }

            return $this->reactions()->where('user_id', Auth::id())->first()?->reaction;
        });
    }

    protected $casts = [
        'reaction' => 'array',
    ];
}
