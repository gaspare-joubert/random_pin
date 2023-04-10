<?php

namespace GaspareJoubert\RandomPin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RandomPin extends Model
{
    use SoftDeletes;

    /**
     * When the pin contains only numerical characters.
     */
    public const TYPE_NUMERICAL = 1;

    /**
     * When the pin contains numerical and alphabetical characters.
     */
    public const TYPE_ALPHANUMERICAL = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pin',
        'type',
        'has_been_emitted',
    ];

    /**
     * Make these dates attributes instances of Carbon
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
