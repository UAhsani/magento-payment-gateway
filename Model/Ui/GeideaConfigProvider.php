<?php

namespace Geidea\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Session\SessionManagerInterface;

use Geidea\Payment\Gateway\Config\Config;

class GeideaConfigProvider implements ConfigProviderInterface {

  const CODE = 'geidea_payment';

  private $config;
  private $session;

  public function __construct(
    Config $config,
    SessionManagerInterface $session
  ) {
      $this->config = $config;
      $this->session = $session;
  }
  
  public function getConfig() {
    
    $storeId = $this->session->getStoreId();

    return [
      'payment' => [
        self::CODE => [
          'title' => $this->config->getTitle($storeId)
        ]
      ]
    ];
  }

}
