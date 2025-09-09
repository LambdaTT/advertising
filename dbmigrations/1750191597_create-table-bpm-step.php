<?php

namespace BPM\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class CreateTableBpmStep extends Migration{
  public function apply()
  {
    $this->Table('BPM_STEP', 'BPM Step')
      ->id('id_bpm_step')
      ->string('ds_key', 17)
      ->string('do_active', 1)->setDefaultValue('Y')
      ->datetime('dt_created')->setDefaultValue(DbVocab::SQL_CURTIMESTAMP())
      ->datetime('dt_updated')->nullable()->setDefaultValue(null)
      ->string('ds_title', 60)
      ->int('nr_step_order')
      ->text('tx_in_rules')->nullable()->setDefaultValue(null)
      ->text('tx_out_rules')->nullable()->setDefaultValue(null)
      ->string('do_is_terminal', 1)->setDefaultValue('N')
      ->string('ds_style', 255)->nullable()->setDefaultValue(null)
      ->string('ds_tag', 60)->nullable()->setDefaultValue(null)
      ->int('id_bpm_workflow')
      ->Index('KEY', DbVocab::IDX_UNIQUE)->onColumn('ds_key')
      ->Foreign('id_bpm_workflow')->references('id_bpm_workflow')->atTable('BPM_WORKFLOW')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE);
  }
}