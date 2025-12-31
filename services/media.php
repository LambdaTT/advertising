<?php

namespace Advertising\Services;

use SplitPHP\Service;

class Media extends Service
{
  const ENTITY = "ADV_MEDIACHANNEL";

  public function list($params = [])
  {
    return $this->getDao(self::ENTITY)
      ->bindParams($params)
      ->find();
  }

  public function sendByEmail($adv, $recipients)
  {
    $count = 0;

    $content = $adv->tx_content;
    $subject = $adv->ds_title;

    foreach ($recipients as $rcpnt) {
      $this->getService('utils/mail')
        ->setSender(TENANT_NAME, 'comunicacao@sindiapp.app.br')
        ->send($content, $rcpnt->ds_email, $subject);

      $count++;
    }

    return $count;
  }

  public function sendAppNotification($adv, $recipients)
  {
    $count = 0;

    $notification = [
      'ds_headline' => $adv->ds_title,
      'ds_brief' => $adv->ds_brief,
      'tx_content' => $adv->tx_content,
      'id_iam_user_recipient' => null,
    ];

    foreach ($recipients as $rcpnt) {
      $notification['id_iam_user_recipient'] = $rcpnt->id_iam_user;
      $this->getService('messaging/notification')->create($notification);
      $count++;
    }

    return $count;
  }
}
