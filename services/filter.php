<?php

namespace Advertising\Services;

use SplitPHP\Service;
use SplitPHP\Exceptions\NotFound;

class TargetFilter extends Service
{
  const ENTITY = "ADV_TARGETFILTER";

  public function list($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->find();
  }

  public function get($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->first(
        "SELECT 
          ftr.*,
          org.ds_title as orgName,
          wrp.ds_title as wrpName,
          sts.ds_title as stsName,
          -- Audit
          DATE_FORMAT(ftr.dt_created, '%d/%m/%Y %T') as dtCreated, 
          DATE_FORMAT(ftr.dt_updated, '%d/%m/%Y %T') as dtUpdated, 
          CONCAT(usrc.ds_first_name, ' ', usrc.ds_last_name) as userCreated,
          CONCAT(usru.ds_first_name, ' ', usru.ds_last_name) as userUpdated
        FROM `ADV_TARGETFILTER` ftr
        LEFT JOIN `IAM_USER` usrc ON usrc.id_iam_user = ftr.id_iam_user_created
        LEFT JOIN `IAM_USER` usru ON usru.id_iam_user = ftr.id_iam_user_updated
        LEFT JOIN `SND_ORGANIZATION` org ON org.id_snd_organization = ftr.id_snd_organization
        LEFT JOIN `SND_ORGANIZATION_WORKPLACE` wrp ON wrp.id_snd_organization_workplace = ftr.id_snd_organization_workplace
        LEFT JOIN `SND_EMPLOYMENT_STATUS` sts ON sts.id_snd_employment_status = ftr.id_snd_employment_status"
      );
  }

  public function create($data)
  {
    // Removes forbidden fields from $data:
    $data = $this->getService('utils/misc')->dataBlackList($data, [
      'id_adv_targetfilter',
      'ds_key',
      'id_iam_user_created',
      'id_iam_user_updated',
      'dt_created',
      'dt_updated'
    ]);

    // Set refs
    $loggedUser = $this->getService('iam/session')->getLoggedUser();

    // Set default value
    $data['ds_key'] = 'ftr-' . uniqid();
    $data['id_iam_user_created'] = empty($loggedUser) ? null : $loggedUser->id_iam_user;

    return $this->getDao(self::ENTITY)->insert($data);
  }

  public function upd($params, $data)
  {
    // Removes forbidden fields from $data:
    $data = $this->getService('utils/misc')->dataBlackList($data, [
      'id_adv_targetfilter',
      'ds_key',
      'id_iam_user_created',
      'id_iam_user_updated',
      'dt_created',
      'dt_updated'
    ]);

    // Set refs
    $loggedUser = $this->getService('iam/session')->getLoggedUser();

    // Validation
    // -- Communication
    $adv = $this->getService('advertising/advertisement')->get(['id_adv_advertisement' => $data['id_adv_advertisement']]);
    if (empty($adv)) throw new NotFound('Falha ao buscar pela campanha correspondente');

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
