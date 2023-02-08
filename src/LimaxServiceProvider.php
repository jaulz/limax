<?php

namespace Jaulz\Limax;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Fluent;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

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
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('limax')
            ->hasConfigFile('limax')
            ->publishesServiceProvider('LimaxServiceProvider')
            ->hasMigration('2013_01_09_141531_create_limax_extension')
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishMigrations()
                    ->publishConfigFile()
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('jaulz/limax');
            });
    }

    public function extendBlueprint()
    {
      Blueprint::macro('limax', function (
        string $targetName = 'slug',
        string $sourceName = 'title',
        array $groupBy = [],
        ?string $schema = null
      ) {
        /** @var \Illuminate\Database\Schema\Blueprint $this */
        $prefix = $this->prefix;
        $tableName = $this->table;
        $targetName = $targetName;
        $sourceName = $sourceName;
        $schema = config('limax.schema') ?? 'public';
  
        $command = $this->addCommand(
          'limax',
          compact(
            'schema',
            'prefix',
            'tableName',
            'targetName',
            'sourceName',
            'groupBy'
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
  
        return [
          sprintf(
            <<<SQL
    SELECT limax.create(%s, %s, %s, (SELECT ARRAY(SELECT jsonb_array_elements_text(%s::jsonb))), %s);
  SQL
            ,
            $this->quoteString($schema),
            $this->quoteString($prefix . $tableName),
            $this->quoteString($sourceName),
            $this->quoteString(json_encode($groupBy)),
            $this->quoteString($targetName)
          ),
        ];
      });
    }
}