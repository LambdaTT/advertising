<?php

namespace Advertising\Routes;

use SplitPHP\WebService;
use SplitPHP\Request;
use SplitPHP\Exceptions\Unauthorized;
use SplitPHP\Exceptions\NotFound;

class Advertising extends WebService
{
  private const ENTITIES = [
    'ADVERTISEMENT' => 'ADV_ADVERTISEMENT',
    'TARGETFILTER' => 'ADV_TARGETFILTER',
    'CUSTOMFILTER' => 'ADV_TARGETCUSTOMFILTER',
  ];

  private const SERVICES = [
    'advertisement' => 'advertising/advertisement',
    'customFilters' => 'advertising/customfilters',
    'filters' => 'advertising/filters',
  ];

  public function init(): void
  {
    $this->setAntiXsrfValidation(false);

    ///////////////////////////
    // ADVERTISEMENT ENDPOINTS:
    ///////////////////////////
    $this->addEndpoint('GET', "/v1/advertisement/?key?", function (Request $request) {
      // Auth:
      $this->auth([
        self::ENTITIES['ADVERTISEMENT'] => 'R'
      ]);

      $params = [
        'ds_key' => $request->route()->params['key']
      ];

      $data = $this->getService(self::SERVICES['advertisement'])->get(['ds_key' => $paramKey]);
      if (empty($data)) return $this->response->withStatus(404);

      return $this->response
        ->withStatus(200)
        ->withData($data, false);
    });

    $this->addEndpoint('GET', '/v1/advertisement', function (Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['ADVERTISEMENT'] => 'R'
      ]);

      $params = $request->getBody();

      return $this->response
        ->withStatus(200)
        ->withData($this->getService(self::SERVICES['advertisement'])->list($params));
    });

    $this->addEndpoint('POST', "/v1/advertisement-send/?key?", function (Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['ADVERTISEMENT'] => 'C'
      ]);

      $params = [
        'ds_key' => $request->route()->params['key']
      ];

      $adv = $this->getService(self::SERVICES['advertisement'])->get($params);
      if (empty($adv)) throw new NotFound('Não foi possível encontrar a campanha selecionada.');

      return $this->response
        ->withStatus(201)
        ->withData($this->getService(self::SERVICES['advertisement'])->send($adv));
    });

    $this->addEndpoint('POST', '/v1/advertisement', function (Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['ADVERTISEMENT'] => 'C'
      ]);

      $data = $request->getBody();

      // Create Advertisement
      $adv = $this->getService(self::SERVICES['advertisement'])->create($data['input']);

      $filters = $data['filters'];
      $filters['id_adv_advertisement'] = $adv->id_adv_advertisement;

      $custom = $data['custom'];
      foreach ($custom as $key => $field) {
        $customData = [
          'id_adv_advertisement' => $adv->id_adv_advertisement,
          'id_stt_settings_customfield' => $field['id'],
          'tx_value' => $field['value']
        ];
        $this->getService(self::SERVICES['customFilters'])->create($customData);
      }

      $this->getService(self::SERVICES['filters'])->create($filters);

      return $this->response
        ->withStatus(201)
        ->withData($adv);
    });

    $this->addEndpoint('PUT', "/v1/advertisement/?key?", function (Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['ADVERTISEMENT'] => 'U'
      ]);

      $data = $request->getBody();
      $params = [
        'ds_key' => $request->route()->params['key']
      ];

      // Update Advertisement
      $rows = $this->getService(self::SERVICES['advertisement'])->upd($params, $data['input']);
      if ($rows < 1) throw new NotFound('Nenhuma campanha foi encontrada com os parâmetros fornecidos.');

      // Update Filters
      $this->getService(self::SERVICES['filters'])
        ->upd(['id_adv_advertisement' => $data['filters']['id_adv_advertisement']], $data['filters']);

      // Update Custom
      foreach ($data['custom'] as $key => $field) {
        $customData = ['tx_value' => $field['value']];
        $this->getService(self::SERVICES['customFilters'])
          ->upd(['id_adv_customfilter' => $field['id_custom']], $customData);
      }

      return $this->response->withStatus(204);
    });

    $this->addEndpoint('DELETE', "/v1/advertisement/?key?", function (Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['ADVERTISEMENT'] => 'D'
      ]);

      $params = [
        'ds_key' => $request->route()->params['key']
      ];

      $rows = $this->getService(self::SERVICES['advertisement'])->remove($params);
      if ($rows < 1) return $this->response->withStatus(404);

      return $this->response
        ->withStatus(204);
    });

    ////////////////////////////
    // TARGET FILTERS ENDPOINTS:
    ////////////////////////////

    // Get
    $this->addEndpoint('GET', "/v1/target-filters/?advertisementKey?", function (Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['TARGETFILTER'] => 'R'
      ]);

      $params = [
        'ds_key' => $request->route()->params['advertisementKey']
      ];

      $adv = $this->getService(self::SERVICES['advertisement'])->get($params);
      if (empty($adv)) throw new NotFound("Invalid key for Advertisement.");

      $data = $this->getService(self::SERVICES['filters'])->get(['id_adv_advertisement' => $adv->id_adv_advertisement]);

      return $this->response
        ->withStatus(200)
        ->withData($data);
    });

    ///////////////////////////////////
    // TARGET CUSTOM FILTERS ENDPOINTS:
    ///////////////////////////////////

    // Get
    $this->addEndpoint('GET', "/v1/target-custom-filters/?advertisementKey?", function (Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['CUSTOMFILTER'] => 'R'
      ]);

      $params = [
        'ds_key' => $request->route()->params['advertisementKey']
      ];

      $adv = $this->getService(self::SERVICES['advertisement'])->get($params);
      if (empty($adv)) throw new NotFound("Invalid key for Advertisement.");

      $data = $this->getService(self::SERVICES['customFilters'])->list(['id_adv_advertisement' => $adv->id_adv_advertisement]);

      return $this->response
        ->withStatus(200)
        ->withData($data);
    });
  }

  private function auth(array $permissions)
  {
    if (!$this->getService('modcontrol/control')->moduleExists('iam')) return;

    // Auth user login:
    if (!$this->getService('iam/session')->authenticate())
      throw new Unauthorized("Não autorizado.");

    // Validate user permissions:
    $this->getService('iam/permission')
      ->validatePermissions($permissions);
  }
}
