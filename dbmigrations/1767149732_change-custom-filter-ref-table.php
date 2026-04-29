<?php

namespace Advertising\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class ChangeCustomFilterRefTable extends Migration{
  public function apply(){
    $this->Table('ADV_TARGETCUSTOMFILTER')
      ->fk('id_stt_settings_customfield')->drop()
      ->fk('id_cst_customfield')
      ->Foreign('id_cst_customfield')->references('id_cst_customfield')->atTable('CST_CUSTOMFIELD')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE);
  }
}