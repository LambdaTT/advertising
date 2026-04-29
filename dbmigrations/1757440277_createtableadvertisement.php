<?php

namespace Advertising\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class Createtableadvertisement extends Migration
{
  public function apply()
  {
    $this->Table('ADV_ADVERTISEMENT')
      ->id('id_adv_advertisement')
      ->string('ds_key', 17)
      ->datetime('dt_created')->setDefaultValue(DbVocab::SQL_CURTIMESTAMP())
      ->datetime('dt_updated')->nullable()->setDefaultValue(null)
      ->fk('id_iam_user_created')->nullable()->setDefaultValue(null)
      ->fk('id_iam_user_updated')->nullable()->setDefaultValue(null)
      ->string('ds_title', 40)
      ->string('ds_brief', 128)
      ->text('tx_content')
      ->string('do_type', 1)->setDefaultValue('U')
      ->date('dt_start')
      ->date('dt_next')->nullable()->setDefaultValue(null)
      ->date('dt_end')->nullable()->setDefaultValue(null)
      ->string('do_interval', 1)->nullable()->setDefaultValue(null)
      ->int('nr_repeat_count')->nullable()->setDefaultValue(null)
      ->Index('KEY', DbVocab::IDX_UNIQUE)->onColumn('ds_key');
  }
}
