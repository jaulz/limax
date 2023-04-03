<?php

namespace Jaulz\Limax;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Fluent;
use Jaulz\Limax\Traits\IsSluggableTrait;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Jaulz\Limax\Facades\Limax;
use Tpetry\PostgresqlEnhanced\Query\Builder as PostgresBuilder;

class LimaxServiceProvider extends PackageServiceProvider
{
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    parent::boot();

    $this->extendBlueprint();
    $this->extendBuilder();
  }

  public function configurePackage(Package $package): void
  {
    $package
      ->name('limax')
      ->hasConfigFile('limax')
      ->hasMigration('create_limax_extension')
      ->hasMigration('grant_usage_on_limax_extension')
      ->hasInstallCommand(function (InstallCommand $command) {
        $command
          ->publishMigrations()
          ->publishConfigFile()
          ->askToRunMigrations()
          ->askToStarRepoOnGitHub('jaulz/limax');
      });
  }

  public function extendBlueprint()
  {
    Blueprint::macro('limax', function (
      string $targetName = 'slug',
      string $sourceName = 'title',
      array $groupBy = [],
      bool $forever = true,
      ?string $schema = null
    ) {
      /** @var \Illuminate\Database\Schema\Blueprint $this */
      $prefix = $this->prefix;
      $tableName = $this->table;
      $schema = $schema ?? config('limax.table_schema') ?? 'public';

      $command = $this->addCommand(
        'limax',
        compact(
          'schema',
          'prefix',
          'tableName',
          'targetName',
          'sourceName',
          'groupBy',
          'forever'
        )
      );
    });

    PostgresGrammar::macro('compileLimax', function (
      Blueprint $blueprint,
      Fluent $command
    ) {
      /** @var \Illuminate\Database\Schema\Grammars\PostgresGrammar $this */
      $schema = $command->schema;
      $prefix = $command->prefix;
      $tableName = $command->tableName;
      $targetName = $command->targetName;
      $sourceName = $command->sourceName;
      $groupBy = $command->groupBy;
      $forever = $command->forever;

      return [
        sprintf(
          <<<SQL
            SELECT 
              limax.create(
                %s, 
                %s, 
                %s, 
                (SELECT ARRAY(SELECT jsonb_array_elements_text(%s::jsonb))), 
                %s::boolean, 
                %s
              );
          SQL,
          $this->quoteString($schema),
          $this->quoteString($prefix . $tableName),
          $this->quoteString($sourceName),
          $this->quoteString(json_encode($groupBy)),
          $forever ? 1 : 0,
          $this->quoteString($targetName)
        ),
      ];
    });
  }

  public function extendBuilder()
  {
    Builder::macro('findBySlug', function (string $value, string $attribute = 'slug', string $tableSchema = null) {
      /** @var \Illuminate\Database\Eloquent\Builder $this */

      $model = $this->getModel();

      if (!in_array(IsSluggableTrait::class, class_uses_recursive($model))) {
        throw new \Exception('If you want to use the findBySlug helper, the model must have the IsSluggable trait.');
      }

      return $this->where(function (Builder $query) use ($value, $tableSchema, $model, $attribute) {
        $query->whereIn($model->getKeyName(),  function (PostgresBuilder $query) use ($model, $value, $tableSchema, $attribute) {
          $query->select(DB::raw(match ($model->getKeyType()) {
            'string' => 'primary_key',
            default => 'primary_key::int',
          }))
            ->from(Limax::getSchema() . '.slugs')
            ->join(Limax::getSchema() . '.definitions', 'definitions.id', '=', 'slugs.definition_id')
            ->where('definitions.table_schema', $tableSchema ?? config('limax.table_schema'))
            ->where('definitions.target_name', $attribute)
            ->where('slugs.value', $value);
        });

        if ($model->getKeyType() === 'int' && is_numeric($value)) {
          $query->orWhere($model->getKeyName(), $value);
        } else if ($model->getKeyType() === 'string') {
          $query->orWhere($model->getKeyName(), $value);
        }
      })->first();
    });

    Builder::macro('findBySlugOrFail', function (string $value, string $attribute = 'slug', string $tableSchema = null) {
      /** @var \Illuminate\Database\Eloquent\Builder $this */

      $model = $this->findBySlug($value, $attribute, $tableSchema);

      if (!is_null($model)) {
        return $model;
      }

      throw (new ModelNotFoundException())->setModel(get_class($this->model));
    });
  }
}
