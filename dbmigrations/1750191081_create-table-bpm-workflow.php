<?php

namespace BPM\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class CreateTableBpmWorkflow extends Migration
{
  public function apply()
  {
    $this->Table('BPM_WORKFLOW', 'BPM Workflow')
      ->id('id_bpm_workflow')
      ->string('ds_key', 17)
      ->string('do_active', 1)->setDefaultValue('Y')
      ->datetime('dt_created')->setDefaultValue(DbVocab::SQL_CURTIMESTAMP())
      ->datetime('dt_updated')->nullable()->setDefaultValue(null)
      ->string('ds_tag', 15)->nullable()->setDefaultValue(null)
      ->string('ds_title', 60)
      ->string('ds_reference_entity_name', 60)
      ->Index('KEY', DbVocab::IDX_UNIQUE)->onColumn('ds_key')
      ->Index('TAG', DbVocab::IDX_UNIQUE)->onColumn('ds_tag');
  }
}
