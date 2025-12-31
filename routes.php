<?php

namespace Advertising\Routes;

use SplitPHP\WebService;
use SplitPHP\Request;
use SplitPHP\Exceptions\Unauthorized;
use SplitPHP\Exceptions\NotFound;
use Exception;

class Advertising extends WebService
{
  private const ENTITIES = [
    'ADVERTISEMENT' => 'ADV_ADVERTISEMENT',
    'TARGETFILTER' => 'ADV_TARGETFILTER',
    'CUSTOMFILTER' => 'ADV_TARGETCUSTOMFILTER',
    'MEDIACHANNEL' => 'ADV_MEDIACHANNEL',
  ];

  private const SERVICES = [
    'advertisement' => 'advertising/advertisement',
    'customFilters' => 'advertising/customfilter',
    'filters' => 'advertising/filter',
    'mediaChannel' => 'advertising/media',
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
        'ds_key' => $request->getRoute()->params['key']
      ];

      $data = $this->getService(self::SERVICES['advertisement'])->get($params);
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

    $this->addEndpoint('POST', '/v1/advertisement', function (Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['ADVERTISEMENT'] => 'C'
      ]);

      $data = $request->getBody();

      // Create Advertisement
      $adv = $this->getService(self::SERVICES['advertisement'])->create($data['input']);

      $filters = $data['filters'];

      $custom = $data['custom'];
      foreach ($custom as $key => $field) {
        $customData = [
          'id_adv_advertisement' => $adv->id_adv_advertisement,
          'id_cst_customfield' => $field['id'],
          'tx_value' => $field['value']
        ];
        $this->getService(self::SERVICES['customFilters'])->create($customData);
      }

      $this->getService(self::SERVICES['filters'])->create($adv->id_adv_advertisement, $filters);

      return $this->response
        ->withStatus(201)
        ->withData($adv);
    });

    $this->addEndpoint('PUT', "/v1/advertisement/?key?", function ($key, Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['ADVERTISEMENT'] => 'U'
      ]);

      $data = $request->getBody();
      $params = [
        'ds_key' => $key
      ];

      // Retrieve existing advertisement
      $adv = $this->getService(self::SERVICES['advertisement'])->get($params);
      if (empty($adv)) throw new NotFound('Nenhuma campanha foi encontrada com os parâmetros fornecidos.');

      // Update Advertisement
      $rows = $this->getService(self::SERVICES['advertisement'])->upd($params, $data['input']);
      if ($rows < 1) throw new NotFound('Nenhuma campanha foi encontrada com os parâmetros fornecidos.');

      // Update Filters
      $this->getService(self::SERVICES['filters'])
        ->upd(['id_adv_advertisement' => $adv->id_adv_advertisement], $data['filters']);

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
        'ds_key' => $request->getRoute()->params['key']
      ];

      $rows = $this->getService(self::SERVICES['advertisement'])->remove($params);
      if ($rows < 1) return $this->response->withStatus(404);

      return $this->response
        ->withStatus(204);
    });

    ////////////////////////////
    // TARGET FILTERS ENDPOINTS:
    ////////////////////////////

    // Get Fields
    $this->addEndpoint('GET', "/v1/target-fields", function () {
      // Auth user login:
      $this->auth(execPermission: 'permission.advertising.view_target_fields');

      $fields = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
      if (empty($fields) || !isset($fields['target']) || !isset($fields['target']['fields'])) {
        throw new Exception("Target Filters configuration not found.");
      }

      return $this->response
        ->withStatus(200)
        ->withData($fields['target']['fields']);
    });

    // Get Values
    $this->addEndpoint('GET', "/v1/target-filters/?advertisementKey?", function (Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['TARGETFILTER'] => 'R'
      ]);

      $params = [
        'ds_key' => $request->getRoute()->params['advertisementKey']
      ];

      $adv = $this->getService(self::SERVICES['advertisement'])->get($params);
      if (empty($adv)) throw new NotFound("Invalid key for Advertisement.");

      $data = $this->getService(self::SERVICES['filters'])->get(['id_adv_advertisement' => $adv->id_adv_advertisement]);

      return $this->response
        ->withStatus(200)
        ->withData($data);
    });

    // Get Values
    $this->addEndpoint('GET', "/v1/target-custom-filters/?advertisementKey?", function (Request $request) {
      // Auth user login:
      $this->auth([
        self::ENTITIES['CUSTOMFILTER'] => 'R'
      ]);

      $params = [
        'ds_key' => $request->getRoute()->params['advertisementKey']
      ];

      $adv = $this->getService(self::SERVICES['advertisement'])->get($params);
      if (empty($adv)) throw new NotFound("Invalid key for Advertisement.");

      $data = $this->getService(self::SERVICES['customFilters'])->list(['id_adv_advertisement' => $adv->id_adv_advertisement]);

      return $this->response
        ->withStatus(200)
        ->withData($data);
    });

    //////////////////
    // MISC ENDPOINTS:
    //////////////////
    $this->addEndpoint('POST', "/v1/publish/?key?", function (Request $request) {
      // Auth user login:
      $this->auth(execPermission: 'permission.advertising.publish');

      $params = [
        'ds_key' => $request->getRoute()->params['key']
      ];

      $adv = $this->getService(self::SERVICES['advertisement'])->get($params);
      if (empty($adv)) throw new NotFound('Não foi possível encontrar a campanha selecionada.');

      return $this->response
        ->withStatus(201)
        ->withData($this->getService(self::SERVICES['advertisement'])->publish($adv));
    });

    //////////////////
    // MEDIA CHANNELS:
    //////////////////
    $this->addEndpoint('GET', "/v1/media-channel", function ($params) {
      $this->auth();

      $list = $this->getService(self::SERVICES['mediaChannel'])->list($params);

      return $this->response
        ->withStatus(200)
        ->withData($list);
    });
  }

  private function auth(array $tablePermissions = [], ?string $execPermission = null)
  {
    if (!$this->getService('modcontrol/control')->moduleExists('iam')) return;

    // Auth user login:
    if (!$this->getService('iam/session')->authenticate())
      throw new Unauthorized("Não autorizado.");

    // Validate user permissions:
    if (!empty($tablePermissions))
      $this->getService('iam/permission')
        ->validatePermissions($tablePermissions);

    // Validate execution permissions:
    if (!empty($execPermission))
      $this->getService('iam/permission')
        ->canExecute($execPermission);
  }
}
