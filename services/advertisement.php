<?php

namespace Advertising\Services;

use SplitPHP\Service;
use SplitPHP\Utils;
use SplitPHP\Exceptions\FailedValidation;
use SplitPHP\Exceptions\NotFound;
use DateTime;
use Exception;

class Advertisement extends Service
{
  const ENTITY = "ADV_ADVERTISEMENT";

  public function list($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->find(
        "SELECT
          adv.*,
          DATE_FORMAT(adv.dt_start, '%d/%m/%Y') as dtStart,
          DATE_FORMAT(adv.dt_next, '%d/%m/%Y') as dtNext
        FROM `SND_COMMUNICATION` adv"
      );
  }

  public function get($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->first(
        "SELECT 
          adv.*,
          DATE_FORMAT(adv.dt_start, '%d/%m/%Y %T') as dtStart,
          DATE_FORMAT(adv.dt_end, '%d/%m/%Y %T') as dtEnd,
          CASE
            WHEN adv.do_interval = 'D' THEN 'Diariamente'
            WHEN adv.do_interval = 'W' THEN 'Semanalmente'
            WHEN adv.do_interval = 'M' THEN 'Mensalmente'
            WHEN adv.do_interval = 'Y' THEN 'Anualmente' 
          END intervalText,
          -- Audit
          DATE_FORMAT(adv.dt_created, '%d/%m/%Y %T') as dtCreated, 
          DATE_FORMAT(adv.dt_updated, '%d/%m/%Y %T') as dtUpdated, 
          CONCAT(usrc.ds_first_name, ' ', usrc.ds_last_name) as userCreated,
          CONCAT(usru.ds_first_name, ' ', usru.ds_last_name) as userUpdated
        FROM `ADV_ADVERTISEMENT` adv
        LEFT JOIN `IAM_USER` usrc ON usrc.id_iam_user = adv.id_iam_user_created
        LEFT JOIN `IAM_USER` usru ON usru.id_iam_user = adv.id_iam_user_updated"
      );
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

    // Validation
    $type = $data['do_type'];
    if (!in_array($type, ['U', 'I', 'R'])) {
      throw new FailedValidation("Tipo de comunicação inválida.");
    }

    // Set default value
    $data['ds_key'] = 'adv-' . uniqid();
    $data['id_iam_user_created'] = empty($loggedUser) ? null : $loggedUser->id_iam_user;
    $data['dt_next'] = $data['dt_start'];

    return $this->getDao(self::ENTITY)->insert($data);
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
    $loggedUser = $this->getService('iam/session')->getLoggedUser();

    if (isset($data['dt_start'])) {
      $adv = $this->get($params);
      if (empty($adv)) throw new NotFound("Nenhuma campanha foi encontrada com os parâmetros informados.");

      if ($adv->dt_start != $data['dt_start']) {
        $data['dt_next'] = $data['dt_start'];
      }
    }

    // Validation
    if (isset($data['do_type']) && !in_array($data['do_type'], ['U', 'I', 'R'])) {
      throw new FailedValidation("Tipo de comunicação inválida.");
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

  public function send($adv)
  {
    // Build Filters and get Recipients:
    $targetFilters = $this->buildFilters($adv->id_adv_advertisement);
    $recipients = $this->getRecipients($targetFilters);

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

    Utils::printLn("Campanha '$adv->id_adv_advertisement - $adv->ds_title' enviada com sucesso.\n");
  }

  public function validateAdvertisement($adv)
  {
    $todayDate = new DateTime();
    $todayDate->setTime(0, 0, 0);
    $ntfDate = new DateTime($adv->dt_next);
    $ntfDate->setTime(0, 0, 0);

    // -- Wrong day
    if ($ntfDate != $todayDate) {
      return false;
    }

    // -- Recurrence completed: no remaining repetitions.
    if ($adv->do_type == 'R' && ($adv->nr_repeat_count === 0 || $adv->nr_repeat_count === '0')) {
      return false;
    }

    return true;
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

  private function buildFilters($advertisementId)
  {
    $stdCount = 0;
    $ctmCount = 0;

    return [
      'standard' => $this->buildStdFilters($advertisementId, $stdCount),
      'custom' => $this->buildCustomFilters($advertisementId, $ctmCount),
      'standardCount' => $stdCount,
      'customCount' => $ctmCount,
    ];
  }

  private function buildStdFilters($advertisementId, &$clausesCount)
  {
    $filters = $this->getDao('ADV_TARGETFILTER')
      ->filter('id_adv_advertisement')->equalsTo($advertisementId)
      ->first();

    if (empty($filters)) {
      throw new Exception("Cannot find advertisement filters for Advertisement $advertisementId");
    }

    $filters = $this->getService('utils/misc')->dataBlackList($filters, [
      'id_adv_targetfilter',
      'ds_key',
      'dt_created',
      'dt_updated',
      'id_iam_user_created',
      'id_iam_user_updated',
      'id_adv_advertisement',
    ]);

    // Construindo a cláusula WHERE
    $clauses = [];

    foreach ($filters as $key => $value) {
      if (is_null($value)) continue;

      switch ($key) {
        case 'do_gender':
        case 'id_snd_organization':
        case 'id_snd_organization_workplace':
        case 'id_snd_employment_status':
          $escapedValue = addslashes($value);
          $clauses[] = "$key = '$escapedValue'";
          $clausesCount++;
          break;
        case 'dt_last_access':
          $clauses[] = "usr.dt_last_access <= DATE_SUB(CURDATE(), INTERVAL $value DAY)";
          $clausesCount++;
          break;
        case 'do_is_birthday':
          if ($value == 'Y') {
            $clauses[] = "MONTH(act.dt_birthday) = MONTH(CURDATE()) AND DAY(act.dt_birthday) = DAY(CURDATE())";
            $clausesCount++;
          }
          break;
        case 'nr_min_age':
          $clauses[] = "act.dt_birthday <= DATE_SUB(CURDATE(), INTERVAL $value YEAR)";
          $clausesCount++;
          break;
        case 'nr_max_age':
          $clauses[] = "act.dt_birthday >= DATE_SUB(CURDATE(), INTERVAL $value YEAR)";
          $clausesCount++;
          break;
      }
    }

    return empty($clauses) ? '1:1' : implode(' AND ', $clauses);
  }

  private function buildCustomFilters($communicationId, &$clausesCount)
  {
    $filters = $this->getDao('SND_COMMUNICATION_CUSTOM')
      ->filter('id_adv_advertisement')->equalsTo($communicationId)
      ->find();

    if (empty($filters)) {
      return null;
    }

    $filters = $this->getService('utils/misc')->dataBlackList($filters, [
      'id_adv_advertisement_custom',
      'ds_key',
      'dt_created',
      'dt_updated',
      'id_iam_user_created',
      'id_iam_user_updated',
      'id_adv_advertisement',
    ]);

    // Build Filters
    $clauses = [];

    foreach ($filters as $field) {
      if (is_null($field->tx_value)) continue;

      $clauses[] = "(id_stt_settings_customfield = '$field->id_stt_settings_customfield' AND tx_value = '$field->tx_value')";
      $clausesCount++;
    }

    return empty($clauses) ? null : implode(' OR ', $clauses);
  }

  private function getRecipients($clauses)
  {
    $where = $clauses['standard'];

    if (!empty($clauses['custom'])) {
      $associateIds = $this->executeCustomClauses($clauses['custom'], $clauses['customCount']);
      if ($associateIds) {
        $where .= " AND id_snd_associate IN ($associateIds)";
      } else {
        return [];
      }
    }

    $sql =
      "SELECT 
        act.id_iam_user,
        act.id_snd_associate,
        act.id_snd_organization,
        act.id_snd_organization_workplace,
        act.id_snd_employment_status,
        act.dt_birthday,
        MONTH(act.dt_birthday) as birthMonth,
        YEAR(act.dt_birthday) as birthYear,
        act.do_gender,
        usr.dt_last_access,
        usr.ds_email
      FROM `SND_ASSOCIATE` act
      JOIN `IAM_USER` usr ON usr.id_iam_user = act.id_iam_user
      WHERE $where";

    return $this->getDao('SND_ASSOCIATE')->find($sql);
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
        FROM `STT_SETTINGS_CUSTOMFIELD_VALUE`
        WHERE $clauses
      ) as filtered
      GROUP BY filtered.id_reference_entity
      HAVING COUNT(*) = $clausesCount
      ORDER BY id";

    $idList = array_column($this->getDao('STT_SETTINGS_CUSTOMFIELD_VALUE')->find($sql), 'id');

    return empty($idList) ? null : implode(',', $idList);
  }

  private function sendAppCommunication($comm, $recipients)
  {
    $count = 0;

    $notification = [
      'ds_headline' => $comm->ds_title,
      'ds_brief' => $comm->ds_brief,
      'tx_content' => $comm->tx_content,
      'id_iam_user_recipient' => null,
    ];

    foreach ($recipients as $associate) {
      $notification['id_iam_user_recipient'] = $associate->id_iam_user;
      $this->getService('messaging/notification')->create($notification);
      $count++;
    }

    return $count;
  }

  private function sendEmailCommunication($comm, $recipients)
  {
    $count = 0;

    $content = $comm->tx_content;
    $subject = $comm->ds_title;

    foreach ($recipients as $associate) {
      $this->getService('utils/mail')
        ->setSender(TENANT_NAME, 'comunicacao@sindiapp.app.br')
        ->send($content, $associate->ds_email, $subject);

      $count++;
    }

    return $count;
  }
}
