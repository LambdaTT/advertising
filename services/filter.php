<?php

namespace Advertising\Services;

use SplitPHP\Service;
use Throwable;

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

  public function readFilters($params)
  {
    $filters = $this->get($params);
    if (empty($filters)) return null;

    $result = [
      'entityName' => $filters->ds_entity_name
    ];
    foreach ($filters->tx_filters as $field => $value) {
      if (($parses = $this->parseServiceCall($value)) !== $value) {
        $result[$field] = [];
        foreach ($parses as $call)
          $result[$field][] = [
            'serviceURI' => $call['serviceURI'],
            'methodName' => $call['methodName'],
            'methodParams' => $call['methodParams']
          ];
      } else $result[$field] = $value;
    }

    return $result;
  }

  /**
   * Parses a filter value with '$service', returning the service URI, instance, the method name and an array of param values:
   * @param mixed $filterValue
   * @return mixed [[serviceInstance, methodName, methodParams]] or the original filterValue if not a service call
   */
  public function parseServiceCall($filterValue): mixed
  {
    if (!is_array($filterValue) && !str_contains($filterValue, '$service')) return $filterValue;

    if (is_array($filterValue))
      $services = $filterValue;
    else
      $services = [$filterValue];

    $results = [];
    foreach ($services as $serviceCall) {
      $parts = explode('::', $serviceCall);
      $serviceURI = str_replace('$service:', '', $parts[0]);
      $methodPart = $parts[1];
      preg_match('/(.*?)\((.*?)\)/', $methodPart, $matches);
      $methodName = $matches[1];
      $methodParams = [];
      if (isset($matches[2]) && !empty($matches[2])) {
        $methodParams = explode(',', $matches[2]);
      }
      $r = [
        'serviceURI' => $serviceURI,
        'methodName' => $methodName,
        'methodParams' => $methodParams
      ];

      $results[] = $r;
    }

    return $results;
  }

  public function create($advId, $data, $targetEntityName)
  {
    return $this->getDao(self::ENTITY)->insert([
      'id_adv_advertisement' => $advId,
      'tx_filters' => json_encode($data),
      'ds_entity_name' => $targetEntityName
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
