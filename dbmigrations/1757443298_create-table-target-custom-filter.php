<?php

namespace Advertising\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class CreateTableTargetCustomFilter extends Migration
{
  public function apply()
  {
    $this->Table('ADV_TARGETCUSTOMFILTER')
      ->id('id_adv_targetcustomfilter')
      ->fk('id_adv_advertisement')
      ->fk('id_stt_settings_customfield')
      ->text('tx_value')
      ->Foreign('id_adv_advertisement')->references('id_adv_advertisement')->atTable('ADV_ADVERTISEMENT')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE)
      ->Foreign('id_stt_settings_customfield')->references('id_stt_settings_customfield')->atTable('STT_SETTINGS_CUSTOMFIELD')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE);
  }
}
