<?php

namespace Advertising\Services;

use SplitPHP\Service;

class TargetFilter extends Service
{
  const ENTITY = "ADV_TARGETFILTER";

  public function list($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->fetch(fn(&$row) => $row->tx_filters = json_decode($row->tx_filters));
  }

  public function get($params = [])
  {
    return $this->list($params)[0] ?? null;
  }

  public function create($advId, $data)
  {
    return $this->getDao(self::ENTITY)->insert([
      'id_adv_advertisement' => $advId,
      'tx_filters' => json_encode($data)
    ]);
  }

  public function upd($advId, $data)
  {
    return $this->getDao(self::ENTITY)
      ->filter('id_adv_advertisement')->equalsTo($advId)
      ->update([
        'tx_filters' => json_encode($data)
      ]);
  }

  public function remove($advId)
  {
    return $this->getDao(self::ENTITY)
      ->filter('id_adv_advertisement')->equalsTo($advId)
      ->delete();
  }
}
