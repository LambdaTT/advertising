<?php

namespace Advertising\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class CreateTableMediaChannels extends Migration
{
  public function apply()
  {
    $this->Table('ADV_MEDIACHANNEL')
      ->id('id_adv_mediachannel')
      ->string('ds_key', 17)
      ->datetime('dt_created')->setDefaultValue(\SplitPHP\Database\DbVocab::SQL_CURTIMESTAMP())
      ->datetime('dt_updated')->nullable()->setDefaultValue(null)
      ->int('id_iam_user_created')->nullable()->setDefaultValue(null)
      ->int('id_iam_user_updated')->nullable()->setDefaultValue(null)
      ->string('ds_title', 40)
      ->string('ds_service_uri', 128)->nullable()->setDefaultValue(null)
      ->string('ds_service_method', 40)->nullable()->setDefaultValue(null)
      ->text('tx_function')->nullable()->setDefaultValue(null)
      ->Index('KEY', DbVocab::IDX_UNIQUE)->onColumn('ds_key');

    $this->Table('ADV_ADVERTISEMENT_MEDIACHANNEL')
      ->id('id_adv_advertisement_mediachannel')
      ->int('id_adv_advertisement')
      ->int('id_adv_mediachannel')
      ->Foreign('id_adv_advertisement')->references('id_adv_advertisement')->atTable('ADV_ADVERTISEMENT')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE)
      ->Foreign('id_adv_mediachannel')->references('id_adv_mediachannel')->atTable('ADV_MEDIACHANNEL')->onUpdate(DbVocab::FKACTION_CASCADE)->onDelete(DbVocab::FKACTION_CASCADE);
  }
}
