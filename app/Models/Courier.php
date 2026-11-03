<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\CourierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $vehicle_type
 * @property string $license_plate
 * @property int $level
 * @property bool $is_active
 * @property Carbon $registered_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Courier extends Model
{
    /** @use HasFactory<CourierFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'couriers';

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'level' => 1,
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (Courier $courier) {
            if (empty($courier->registered_at)) {
                $courier->registered_at = now();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'vehicle_type',
        'license_plate',
        'level',
        'is_active',
        'registered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_active' => 'boolean',
            'registered_at' => 'datetime',
        ];
    }

    /**
     * Local scope: multi-word tokenized search using AND logic across tokens on courier name.
     * Splitting search by space (?search=budi+agung becomes "budi agung").
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        // Split by one or more whitespace characters
        $tokens = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($tokens)) {
            return $query;
        }

        return $query->where(function (Builder $nestedQuery) use ($tokens) {
            foreach ($tokens as $token) {
                $nestedQuery->where('name', 'like', '%'.$token.'%');
            }
        });
    }

    /**
     * Local scope: filter by comma-separated level string (e.g. "2,3" -> [2, 3]).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeByLevels(Builder $query, ?string $levels): Builder
    {
        if (blank($levels)) {
            return $query;
        }

        $levelArray = array_values(array_filter(
            array_map(fn ($item) => filter_var(trim($item), FILTER_VALIDATE_INT), explode(',', $levels)),
            fn ($val) => $val !== false && $val !== null
        ));

        if (empty($levelArray)) {
            return $query;
        }

        return $query->whereIn('level', $levelArray);
    }

    /**
     * Local scope: sorting by whitelisted column and direction.
     * Default sort by name ASC.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSort(Builder $query, ?string $sortBy = 'name', ?string $sortDirection = 'asc'): Builder
    {
        $allowedSortBy = ['name', 'registered_at', 'created_at', 'level', 'code'];
        $column = in_array(strtolower((string) $sortBy), $allowedSortBy, true)
            ? strtolower((string) $sortBy)
            : 'name';

        $direction = strtolower((string) $sortDirection) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($column, $direction);
    }
}
