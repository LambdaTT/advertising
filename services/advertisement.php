<?php

namespace Advertising\Services;

use SplitPHP\Service;
use SplitPHP\Exceptions\FailedValidation;
use SplitPHP\Exceptions\NotFound;
use SplitPHP\Database\Dbmetadata;
use SplitPHP\Database\Database;
use DateTime;
use Exception;

class Advertisement extends Service
{
  const ENTITY = "ADV_ADVERTISEMENT";

  private $entity;

  public function __construct()
  {
    $this->entity = (object) [
      'name' => null,
      'pk' => null
    ];
  }

  public function list($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->find('advertising/advertisement/read');
  }

  public function get($params = [])
  {
    $adv = $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->first('advertising/advertisement/read');

    if (empty($adv)) return null;

    $adv->mediaChannels = $this->getDao('ADV_ADVERTISEMENT_MEDIACHANNEL')
      ->filter('id_adv_advertisement')->equalsTo($adv->id_adv_advertisement)
      ->fetch(fn(&$row) => $row = $row->id_adv_mediachannel);

    return $adv;
  }

  public function create($data)
  {
    // Removes forbidden fields from $data:
    $data = $this->getService('utils/misc')->dataBlackList($data, [
      'id_adv_advertisement',
      'ds_key',
      'id_iam_user_created',
      'id_iam_user_updated',
      'dt_created',
      'dt_updated'
    ]);

    // Set refs
    $loggedUser = $this->getService('iam/session')->getLoggedUser();
    $mediaChannels = $data['mediaChannels'] ?? [];
    unset($data['mediaChannels']);

    // Validation
    $type = $data['do_type'];
    if (!in_array($type, ['U', 'I', 'R'])) {
      throw new FailedValidation("Tipo de campanha inválida.");
    }

    // Set default value
    $data['ds_key'] = 'adv-' . uniqid();
    $data['id_iam_user_created'] = empty($loggedUser) ? null : $loggedUser->id_iam_user;
    $data['dt_next'] = $data['dt_start'];

    $created = $this->getDao(self::ENTITY)->insert($data);

    $this->handleMediaChannels($created, $mediaChannels);

    return $created;
  }

  public function upd($params, $data)
  {
    // Removes forbidden fields from $data:
    $data = $this->getService('utils/misc')->dataBlackList($data, [
      'id_adv_advertisement',
      'ds_key',
      'id_iam_user_created',
      'id_iam_user_updated',
      'dt_created',
      'dt_updated'
    ]);

    // Set refs
    $adv = $this->get($params);
    if (empty($adv))
      throw new NotFound("Nenhuma campanha foi encontrada com os parâmetros informados.");

    $loggedUser = $this->getService('iam/session')->getLoggedUser();

    if (isset($data['mediaChannels'])) {
      $mediaChannels = $data['mediaChannels'] ?? [];
      unset($data['mediaChannels']);
      $this->handleMediaChannels($adv, $mediaChannels);
    }

    if (isset($data['dt_start'])) {
      if ($adv->dt_start != $data['dt_start']) {
        $data['dt_next'] = $data['dt_start'];
      }
    }

    // Validation
    if (isset($data['do_type']) && !in_array($data['do_type'], ['U', 'I', 'R'])) {
      throw new FailedValidation("Tipo de campanha inválida.");
    }

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

  public function publish($adv)
  {
    // Build Filters and get Recipients:
    $recipients = $this->getRecipients($adv->id_adv_advertisement);

    // Execute each media channel's sending method:
    $this->getDao('ADV_MEDIACHANNEL')
      ->filter('adId')->equalsTo($adv->id_adv_advertisement)
      ->fetch(
        function ($media) use ($adv, $recipients) {
          if (!empty($media->ds_service_uri) && !empty($media->ds_service_method)) {
            return $this->getService($media->ds_service_uri)->{$media->ds_service_method}($adv, $recipients);
          } elseif (!empty($media->tx_function)) {
            eval($media->tx_function);
          } else {
            throw new Exception("Media Channel '{$media->ds_title}' is not properly configured.");
          }
        },
        "SELECT 
            med.*
          FROM `ADV_MEDIACHANNEL` med
          JOIN `ADV_ADVERTISEMENT_MEDIACHANNEL` adm ON adm.id_adv_mediachannel = med.id_adv_mediachannel
          WHERE adm.id_adv_advertisement = ?adId?"
      );
  }

  public function updNextAdvertisementDate($adv)
  {
    $ntfDate = new DateTime($adv->dt_next);
    $ntfDate->setTime(0, 0, 0);
    $nextDate = clone $ntfDate;

    if ($adv->do_type == 'I') {
      $endDate = new DateTime($adv->dt_end);
      $nextDate->modify('+1 day');

      if ($nextDate <= $endDate) {
        $this->upd(['ds_key' => $adv->ds_key], ['dt_next' => $nextDate]);
      }
    } else if ($adv->do_type == 'R') {
      if (!empty($adv->nr_repeat_count)) {
        $adv->nr_repeat_count--;
      }

      switch ($adv->do_interval) {
        case 'D':
          $nextDate->modify('+1 day');
          break;
        case 'W':
          $nextDate->modify('+1 week');
          break;
        case 'M':
          $nextDate->modify('+1 month');
          break;
        case 'Y':
          $nextDate->modify('+1 year');
          break;
        default:
          throw new Exception("Invalid Interval type: $adv->do_interval");
      }

      $this->upd(['ds_key' => $adv->ds_key], ['nr_repeat_count' => $adv->nr_repeat_count, 'dt_next' => $nextDate]);
    }
  }

  // ======= Private Methods =======
  private function buildParams($advertisementId)
  {
    return [
      ...$this->buildTargetFilters($advertisementId),
      ...$this->buildCustomFilters($advertisementId)
    ];
  }

  private function buildTargetFilters($advertisementId)
  {
    // Load Target Filters values for the given Advertisement:
    $filters = $this->getService('advertising/filter')->readFilters([
      'id_adv_advertisement' => $advertisementId
    ]);
    $this->entity->name = $filters['entityName'];
    $this->entity->pk = Dbmetadata::tbPrimaryKey($this->entity->name);
    unset($filters['entityName']);

    if (empty($filters))
      throw new Exception("Cannot find advertisement filters for Advertisement $advertisementId");

    // Build Params
    $params = [];
    foreach ($filters as $key => $f)
      if ($f !== null && $f !== '') {
        if (is_array($f) && count($f) >= 1) {
          foreach ($f as $fc) {
            if (!isset($fc['serviceURI']) || !isset($fc['methodName']))
              continue;

            // Service Call
            $method = $fc['methodName'];
            $methodParams = $fc['methodParams'];
            $params[$key][] = $this->getService($fc['serviceURI'])->{$method}(...$methodParams);
          }
        } else {
          // Direct Value
          $params[$key] = $f;
        }
      }
    return $params;
  }

  private function buildCustomFilters($advertisementId)
  {
    $filters = $this->getDao('ADV_TARGETCUSTOMFILTER')
      ->filter('id_adv_advertisement')->equalsTo($advertisementId)
      ->find();

    if (empty($filters)) return [];

    $filters = $this->getService('utils/misc')->dataBlackList($filters, [
      'id_adv_advertisement_custom',
      'ds_key',
      'dt_created',
      'dt_updated',
      'id_iam_user_created',
      'id_iam_user_updated',
      'id_adv_advertisement',
    ]);

    include CORE_PATH . "/database/" . Database::getRdbmsName() . "/class.dbmetadata.php";

    // Build Filters
    $this->entity->name = $filters[0]->ds_entity_name;
    $this->entity->pk = Dbmetadata::tbPrimaryKey($this->entity->name);

    $clauses = [];
    $clausesCount = 0;

    foreach ($filters as $field) {
      if (is_null($field->tx_value)) continue;

      $clauses[] = "(id_cst_customfield = '$field->id_cst_customfield' AND tx_value = '$field->tx_value')";
      $clausesCount++;
    }

    $clauses = empty($clauses) ? '1=1' : implode(' OR ', $clauses);

    $targetIds = $this->executeCustomClauses($clauses, $clausesCount);

    return [
      $this->entity->pk => '$in|' . (empty($targetIds) ? '0' : implode('|', $targetIds))
    ];
  }

  private function getRecipients($advertisementId)
  {
    $params = $this->buildParams($advertisementId);

    return $this->getDao($this->entity->name)
      ->bindParams($params)
      ->find();
  }

  private function executeCustomClauses($clauses, $clausesCount)
  {
    // Get Associate Ids
    $idList = null;

    $sql =
      "SELECT 
        filtered.id_reference_entity as id
      FROM (
        SELECT id_reference_entity
        FROM `CST_CUSTOMFIELD_VALUE`
        WHERE $clauses
      ) as filtered
      GROUP BY filtered.id_reference_entity
      HAVING COUNT(*) = $clausesCount
      ORDER BY id";

    $idList = array_column($this->getDao('CST_CUSTOMFIELD_VALUE')->find($sql), 'id');

    return empty($idList) ? [] : $idList;
  }

  private function handleMediaChannels($adv, $mediaChannels)
  {
    if (empty($mediaChannels) || !is_array($mediaChannels)) {
      return;
    }

    $adv = (array) $adv;

    // First, remove existing links:
    $this->getDao('ADV_ADVERTISEMENT_MEDIACHANNEL')
      ->filter('id_adv_advertisement')->equalsTo($adv['id_adv_advertisement'])
      ->delete();

    // Then, create new links:
    foreach ($mediaChannels as $mediaChannelId) {
      $this->getDao('ADV_ADVERTISEMENT_MEDIACHANNEL')
        ->insert([
          'id_adv_advertisement' => $adv['id_adv_advertisement'],
          'id_adv_mediachannel' => $mediaChannelId,
        ]);
    }
  }
}
