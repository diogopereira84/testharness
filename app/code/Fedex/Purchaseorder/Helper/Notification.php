<?php
namespace Fedex\Purchaseorder\Helper;

use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Notification extends \Magento\Framework\App\Helper\AbstractHelper
{

  /**
   * @codeCoverageIgnore
  */
  public function __construct(
      protected LoggerInterface $logger,
      protected ToggleConfig $toggleConfig
  )
  {
  }
  /**
   * @codeCoverageIgnore
   *
   */
  public function sendXmlNotification($cxml,$url)
  {
    if ($cxml && $url) {
      
     if($this->toggleConfig->getToggleConfigValue('colo_migration_header_length_issue_fixed'))
      {
          $headers = array("Content-Type: application/x-www-form-urlencoded","Content-Length: ".mb_strlen("cXML-urlencoded=".$cxml, 'utf-8'));
      }
      else
      {
      	  $headers = array("Content-Type: application/x-www-form-urlencoded");
      }
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_ENCODING, "");
      curl_setopt($ch, CURLOPT_TIMEOUT, 0);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, "cXML-urlencoded=" . $cxml);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $data = curl_exec($ch);
      if ($data === false) {
          $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . curl_error($ch));
          return 'Curl error: ' . curl_error($ch);

      } else {
        $result = curl_getinfo($ch);
        curl_close($ch);
        if ($result['http_code'] == 200) {
          return $data;
        } else {
          $this->logger->error(__METHOD__ . ':' . __LINE__ . 'Result from Notification API'. var_export($result, true));
          $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Failed sending xml notification.');
          return 'failed';
        }
      }
    }
  }
}
