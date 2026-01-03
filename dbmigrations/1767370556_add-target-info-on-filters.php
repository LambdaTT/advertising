<?php

namespace Advertising\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class AddTargetInfoOnFilters extends Migration
{
  public function apply()
  {
    $this->Table('ADV_TARGETCUSTOMFILTER')
      ->string('ds_entity_name', 100);

    $this->Table('ADV_TARGETFILTER')
      ->string('ds_entity_name', 100);
  }
}
