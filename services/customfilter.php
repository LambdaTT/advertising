<?php

namespace Advertising\Services;

use SplitPHP\Service;
use SplitPHP\Exceptions\NotFound;

class CustomFilter extends Service
{
  const ENTITY = "ADV_TARGETCUSTOMFILTER";
  
  public function list($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->find('advertising/customfilter/read');
  }
  
  public function get($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->first('advertising/customfilter/read');
  }

  public function create($data)
  {
    // Removes forbidden fields from $data:
    $data = $this->getService('utils/misc')->dataBlackList($data, [
      'id_adv_targetcustomfilter',
      'ds_key',
      'dt_created',
      'dt_updated',
      'id_iam_user_created',
      'id_iam_user_updated',
    ]);

    // Set refs
    $loggedUser = $this->getService('iam/session')->getLoggedUser();
    $customfield = $this->getService('settings/customfield')->getField($data['id_stt_settings_customfield']);

    // Validation
    // -- Customfield
    if(empty($customfield)){
      throw new NotFound("Falha ao buscar pelo campo customizado informado.");
    }

    // Set default value
    $data['ds_key'] = 'cst-' . uniqid();
    $data['id_iam_user_created'] = empty($loggedUser) ? null : $loggedUser->id_iam_user;

    return $this->getDao(self::ENTITY)->insert($data);
  }

  public function upd($params, $data)
  {
     // Removes forbidden fields from $data:
    $data = $this->getService('utils/misc')->dataBlackList($data, [
      'id_adv_targetcustomfilter',
      'ds_key',
      'dt_created',
      'dt_updated',
      'id_iam_user_created',
      'id_iam_user_updated',
      'id_snd_communication',
      'id_stt_settings_customfield',
    ]);

    // Set refs
    $loggedUser = $this->getService('iam/session')->getLoggedUser();

    // Set default value
    $data['id_iam_user_updated'] = empty($loggedUser) ? null : $loggedUser->id_iam_user;
    $data['dt_updated'] = date("Y-m-d H:i:s");

    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->update($data);
  }

  public function remove($params)
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->delete();
  }
}