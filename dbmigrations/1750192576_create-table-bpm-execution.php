<?php

namespace BPM\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class CreateTableBpmExecution extends Migration{
  public function apply(){
    $this->Table('BPM_EXECUTION', 'BPM Execution')
      ->id('id_bpm_execution')
      ->string('ds_key', 17)
      ->string('do_active', 1)->setDefaultValue('Y')
      ->datetime('dt_created')->setDefaultValue(DbVocab::SQL_CURTIMESTAMP())
      ->datetime('dt_updated')->nullable()->setDefaultValue(null)
      ->int('id_bpm_workflow')
      ->int('id_bpm_step_current')->nullable()->setDefaultValue(null)
      ->string('ds_reference_entity_name', 60)
      ->int('id_reference_entity_id')
      ->Index('KEY', DbVocab::IDX_UNIQUE)->onColumn('ds_key')
      ->Foreign('id_bpm_workflow')->references('id_bpm_workflow')->atTable('BPM_WORKFLOW')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE)
      ->Foreign('id_bpm_step_current')->references('id_bpm_step')->atTable('BPM_STEP')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE);
  }
}