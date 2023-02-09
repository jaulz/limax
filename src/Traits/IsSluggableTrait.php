<?php

namespace Jaulz\Limax\Traits;

use Illuminate\Database\Eloquent\Builder;

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
   * @return void
   */
  public function scopeSlugged(Builder $query, string $slug)
  {
    $query->where('slug', $slug);
  }
}
