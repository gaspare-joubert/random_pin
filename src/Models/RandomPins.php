<?php

namespace GaspareJoubert\RandomPin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RandomPins extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pin',
        'permitted_characters',
        'has_been_emitted',
    ];

    /**
     * The attributes that are searchable.
     *
     * @var array
     */
    protected $searchableColumns = [
        'pin',
        'permitted_characters',
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
