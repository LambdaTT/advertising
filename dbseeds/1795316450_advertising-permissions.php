<?php

namespace Advertising\Seeds;

use SplitPHP\DbManager\Seed;
use SplitPHP\ModsManager;

class AdvertisingPermissions extends Seed
{
  public function apply()
  {
    if (!ModsManager::modExists('iam')) return;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Ler Informações de Campanhas')
      ->onField('ds_tag')->setFixedValue('advertising.advertisement.read')
    ;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Gerenciar Informações de Campanhas')
      ->onField('ds_tag')->setFixedValue('advertising.advertisement.manage')
    ;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Publicar Campanhas')
      ->onField('ds_tag')->setFixedValue('advertising.advertisement.publish')
    ;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Ler Filtros de Segmentação')
      ->onField('ds_tag')->setFixedValue('advertising.targetfilter.read')
    ;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Gerenciar Filtros de Segmentação')
      ->onField('ds_tag')->setFixedValue('advertising.targetfilter.manage')
    ;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Ler Filtros Personalizados')
      ->onField('ds_tag')->setFixedValue('advertising.customfilter.read')
    ;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Gerenciar Filtros Personalizados')
      ->onField('ds_tag')->setFixedValue('advertising.customfilter.manage')
    ;
  }
}
