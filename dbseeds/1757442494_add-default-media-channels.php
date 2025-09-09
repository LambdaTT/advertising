<?php

namespace Advertising\Seeds;

use SplitPHP\DbManager\Seed;

class AddDefaultMediaChannels extends Seed
{
  public function apply()
  {
    $this->SeedTable('ADV_MEDIACHANNEL', batchSize: 1)
      ->onField('ds_key', true)->setByFunction(function () {
        return 'med-' . uniqid();
      })
      ->onField('ds_title')->setFixedValue('E-mail')
      ->onField('ds_service_uri')->setFixedValue('advertising/media')
      ->onField('ds_service_method')->setFixedValue('sendByEmail');

    $this->SeedTable('ADV_MEDIACHANNEL', batchSize: 1)
      ->onField('ds_key', true)->setByFunction(function () {
        return 'med-' . uniqid();
      })
      ->onField('ds_title')->setFixedValue('App Notification')
      ->onField('ds_service_uri')->setFixedValue('advertising/media')
      ->onField('ds_service_method')->setFixedValue('sendAppNotification');
  }
}
