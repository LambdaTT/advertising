<?php

namespace BPM\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class CreateTableBpmStepTracking extends Migration{
  public function apply(){
    $this->Table('BPM_STEP_TRACKING', 'BPM Step Tracking')
      ->id('id_bpm_step_tracking')
      ->datetime('dt_track')->setDefaultValue(DbVocab::SQL_CURTIMESTAMP())
      ->int('id_bpm_execution')
      ->int('id_bpm_step')->nullable()
      ->Foreign('id_bpm_execution')->references('id_bpm_execution')->atTable('BPM_EXECUTION')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE)
      ->Foreign('id_bpm_step')->references('id_bpm_step')->atTable('BPM_STEP')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_SETNULL);
  }
}