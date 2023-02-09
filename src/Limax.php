<?php

namespace Jaulz\Limax;

use Illuminate\Support\Facades\DB;

class Limax
{
  public function getSchema()
  {
    return 'limax';
  }

  public function grant(string $role)
  {
    collect([
      'GRANT USAGE ON SCHEMA %1\$s TO %2\$s',
      'GRANT SELECT ON TABLE %1\$s.definitions TO %2\$s;',
      'GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE %1\$s.slugs TO %2\$s;'
    ])->each(fn (string $statement) => DB::statement(sprintf($statement, Limax::getSchema(), $role)));
  }

  public function ungrant(string $role)
  {
  }
}
