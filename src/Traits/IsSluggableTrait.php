<?php

namespace Jaulz\Limax\Traits;

use Illuminate\Database\Eloquent\Builder;
use Tpetry\PostgresqlEnhanced\Query\Builder as PostgresBuilder;
use Illuminate\Support\Facades\DB;
use Jaulz\Limax\Facades\Limax;

trait IsSluggableTrait
{
  /**
   * Boot the trait.
   */
  public static function bootIsSluggableTrait()
  {
  }

  /**
   * Initialize the trait
   *
   * @return void
   */
  public function initializeIsSluggableTrait()
  {
  }

  /**
   * Scope a query to filter by slug.
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $query
   * @param  string  $slug
   * @param  string  $attribute
   * @param  ?string  $tableSchema
   * @return void
   */
  public function scopeSlugged(Builder $query, string $value, string $attribute = 'slug', string $tableSchema = null)
  {
    $query->where(function (Builder $query) use ($value, $tableSchema, $attribute) {
      $query->whereIn($this->getKeyName(),  function (PostgresBuilder $query) use ($value, $tableSchema, $attribute) {
        $query->select(DB::raw(match ($this->getKeyType()) {
          'string' => 'primary_key',
          default => 'primary_key::int',
        }))
          ->from(Limax::getSchema() . '.slugs')
          ->join(Limax::getSchema() . '.definitions', 'definitions.id', '=', 'slugs.definition_id')
          ->where('definitions.table_schema', $tableSchema ?? config('limax.table_schema'))
          ->where('definitions.target_name', $attribute)
          ->where('slugs.value', $value);
      });

      if ($this->getKeyType() === 'int' && is_numeric($value)) {
        $query->orWhere($this->getKeyName(), $value);
      } else if ($this->getKeyType() === 'string') {
        $query->orWhere($this->getKeyName(), $value);
      }
    });
  }
}
