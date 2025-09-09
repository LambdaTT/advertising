<?php

namespace Advertising\Services;

use SplitPHP\Service;
use SplitPHP\Exceptions\NotFound;

class CustomFilter extends Service
{
  const ENTITY = "ADV_TARGETFILTER";
  
  public function list($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->find(
        "SELECT 
          ftr.*,
          cst.ds_fieldlabel,
          CASE
            WHEN cst.do_fieldtype = 'T' THEN 'text'
            WHEN cst.do_fieldtype = 'N' THEN 'number'
            ELSE 'text'
          END as typeText,
          -- Audit
          DATE_FORMAT(ftr.dt_created, '%d/%m/%Y %T') as dtCreated, 
          DATE_FORMAT(ftr.dt_updated, '%d/%m/%Y %T') as dtUpdated, 
          CONCAT(usrc.ds_first_name, ' ', usrc.ds_last_name) as userCreated,
          CONCAT(usru.ds_first_name, ' ', usru.ds_last_name) as userUpdated
        FROM `ADV_TARGETFILTER` ftr
        LEFT JOIN `IAM_USER` usrc ON usrc.id_iam_user = ftr.id_iam_user_created
        LEFT JOIN `IAM_USER` usru ON usru.id_iam_user = ftr.id_iam_user_updated
        JOIN STT_SETTINGS_CUSTOMFIELD cst ON cst.id_stt_settings_customfield = ftr.id_stt_settings_customfield"
      );
  }
  
  public function get($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->first(
        "SELECT 
          ftr.*,
          -- Audit
          DATE_FORMAT(ftr.dt_created, '%d/%m/%Y %T') as dtCreated, 
          DATE_FORMAT(ftr.dt_updated, '%d/%m/%Y %T') as dtUpdated, 
          CONCAT(usrc.ds_first_name, ' ', usrc.ds_last_name) as userCreated,
          CONCAT(usru.ds_first_name, ' ', usru.ds_last_name) as userUpdated
        FROM `ADV_TARGETFILTER` ftr
        LEFT JOIN `IAM_USER` usrc ON usrc.id_iam_user = ftr.id_iam_user_created
        LEFT JOIN `IAM_USER` usru ON usru.id_iam_user = ftr.id_iam_user_updated"
      );
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