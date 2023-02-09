<?php

namespace Jaulz\Limax\Traits;

use Illuminate\Database\Eloquent\Builder;
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
   * @param  string  $column
   * @return void
   */
  public function scopeSlugged(Builder $query, string $value, string $column = 'slug')
  {
    /*$query->where(function (Builder $query) use ($value) {
      $query->where($this->getKeyName(), match ($this->getKeyType() === 'string') {
        'string' => $value,
        default => (int) $value,
      });
      $query->orWhere('slug', $value);
    });*/

    $definition = DB::table(Limax::getSchema() . '.definitions')->where('table_name', $this->getTable())
      ->where('table_schema', 'public')
      ->where('target_name', $column)->first();

    if (!$definition) {
      $query->where(DB::raw('1'), '=', DB::raw('0'));
      return;
    }

    $slug = DB::table(Limax::getSchema() . '.slugs')->where('definition_id', $definition->id)
      ->where('value', $value)->first();
    if (!$slug) {
      $query->where(DB::raw('1'), '=', DB::raw('0'));
      return;
    }

    $query->where($this->getKeyName(), $slug->primary_key);

    /*$slug = DB::table(Limax::getSchema() . '.slugs')
    
    table(Limax::getSchema() . '.definitions')->where('table_name', '=', DB::raw(sprintf("'%s'", $this->getTable())))
    ->where('table_schema', 'public')
    ->where('target_name', $column)

    $query
      ->join(Limax::getSchema() . '.definitions', function ($join) use ($column) {
        $join->on('definitions.table_name', '=', DB::raw(sprintf("'%s'", $this->getTable())))
          ->where('definitions.table_schema', 'public')
          ->where('definitions.target_name', $column);
      })
      ->join(Limax::getSchema() . '.slugs', function ($join) use ($slug) {
        $join->on('definitions.id', '=', 'slugs.definition_id')
          ->where('slugs.value', '=', $slug);
      });*/
  }
}
