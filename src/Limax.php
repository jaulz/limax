<?php

namespace Jaulz\Limax;

use Illuminate\Support\Facades\DB;

class Limax
{
  public function getSchema() {
    return 'limax';
  }

  public function grant(string $role) {
    DB::statement(
      sprintf(
        <<<PLPGSQL
GRANT SELECT ON TABLE %1\$s.definitions TO %2\$s;
PLPGSQL
        ,
        Limax::getSchema(),
        $role
      )
    );
    
    DB::statement(
      sprintf(
        <<<PLPGSQL
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE %1\$s.slugs TO %2\$s;
PLPGSQL
        ,
        Limax::getSchema(),
        $role
      )
    );
  }
}