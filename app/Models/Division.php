<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Division extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function officeLocation(): HasOne
    {
        return $this->hasOne(OfficeLocation::class);
    }

    public function kepalaDivisis(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_KADIV);
    }
}
