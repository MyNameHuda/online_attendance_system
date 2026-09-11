<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'division_id',
        'name',
        'latitude',
        'longitude',
        'radius_meters',
        'is_main',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'radius_meters' => 'integer',
        'is_main' => 'boolean',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Ambil kantor utama (singleton — sistem hanya support 1 lokasi).
     * Fallback ke record pertama kalau belum ada yg ditandai main.
     */
    public static function getMain(): ?self
    {
        return self::where('is_main', true)->first()
            ?? self::orderBy('id')->first();
    }

    /**
     * Hitung jarak (meter) antara koordinat user dengan titik kantor
     * menggunakan formula Haversine.
     */
    public function distanceFrom(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // meter

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($lat);
        $lonTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }

    public function isWithinRadius(float $lat, float $lng): bool
    {
        return $this->distanceFrom($lat, $lng) <= $this->radius_meters;
    }
}
