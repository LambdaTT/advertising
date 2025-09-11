<?php

namespace Advertising\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class CreateTableTargetFilter extends Migration
{
  public function apply()
  {
    $this->Table('ADV_TARGETFILTER')
      ->id('id_adv_targetfilter') // int primary key auto increment
      ->int('id_adv_advertisement') // int
      ->Foreign('id_adv_advertisement')->references('id_adv_advertisement')->atTable('ADV_ADVERTISEMENT')->onUpdate(DbVocab::FKACTION_CASCADE)
      ->text('tx_filters');
  }
}
