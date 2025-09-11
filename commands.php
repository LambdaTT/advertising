<?php

namespace Advertising\Commands;

use SplitPHP\Cli;

class Advertising extends Cli
{
  private const SERVICES = [
    'advertisement' => 'advertising/advertisement',
  ];

  public function init()
  {
    $this->addCommand('send', function () {
      $advertisements = $this->getService(self::SERVICES['advertisement'])->list(['dt_next' => date('Y-m-d')]);

      foreach ($advertisements as $adv) {
        // Update
        $this->getService(self::SERVICES['advertisement'])->updNextAdvertisementDate($adv);

        // Send
        $this->getService(self::SERVICES['advertisement'])->send($adv);
      }
    });
  }
}
