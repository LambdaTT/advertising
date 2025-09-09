<?php

namespace BPM\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class CreateTableBpmTransition extends Migration{
  public function apply(){
    $this->Table('BPM_TRANSITION', 'BPM Transition')
      ->id('id_bpm_transition')
      ->string('ds_key', 17)
      ->string('do_active', 1)->setDefaultValue('Y')
      ->datetime('dt_created')->setDefaultValue(DbVocab::SQL_CURTIMESTAMP())
      ->datetime('dt_updated')->nullable()->setDefaultValue(null)
      ->string('ds_title', 60)
      ->string('ds_icon', 60)->nullable()->setDefaultValue(null)
      ->int('id_bpm_workflow')
      ->int('id_bpm_step_origin')
      ->int('id_bpm_step_destination')
      ->text('tx_rules')->nullable()->setDefaultValue(null)
      ->Index('KEY', DbVocab::IDX_UNIQUE)->onColumn('ds_key')
      ->Foreign('id_bpm_workflow')->references('id_bpm_workflow')->atTable('BPM_WORKFLOW')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE)
      ->Foreign('id_bpm_step_origin')->references('id_bpm_step')->atTable('BPM_STEP')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE)
      ->Foreign('id_bpm_step_destination')->references('id_bpm_step')->atTable('BPM_STEP')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE);
  }
}