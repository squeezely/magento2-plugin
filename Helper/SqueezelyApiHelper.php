<?php

namespace Squeezely\Plugin\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class SqueezelyApiHelper extends AbstractHelper {

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    const XML_PATH_SQUEEZELY_API_KEY = 'squeezely_plugin/general/squeezely_api_key';
    const XML_PATH_SQUEEZELY_WEBHOOK_KEY = 'squeezely_plugin/general/squeezely_webhook_key';
    const XML_PATH_SQUEEZELY_ID = 'squeezely_plugin/general/SQZLY_id';

    private $squeezelyApiKey;
    private $squeezelyWebhookKey;
    private $squeezelyAccountId;

    const PRODUCT_END_POINT = "https://api.squeezely.tech/v1/products";
    const TRACKER_END_POINT = "https://api.squeezely.tech/v1/track";
    const VERIFY_API_LOGIN_END_POINT = "https://api.squeezely.tech/v1/verifyAuth?channel=2";

    public function __construct(ScopeConfigInterface $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
        $storeScope = ScopeInterface::SCOPE_STORE;
        $this->squeezelyAccountId = trim($this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_ID, $storeScope));
        $this->squeezelyApiKey = trim($this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_API_KEY, $storeScope));
        $this->squeezelyWebhookKey = trim($this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_WEBHOOK_KEY,
            $storeScope));
    }


    private function postData($fields, $url) {
        $json = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-AUTH-ACCOUNT: $this->squeezelyAccountId",
            "X-AUTH-APIKEY: $this->squeezelyApiKey",
            "Content-Type: application/json",
            "Content-Length: " . strlen($json)
        ]);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function sendProducts($products) {
        return $this->postData($products, self::PRODUCT_END_POINT);
    }

    public function sendPurchases($purchases) {
        return $this->postData($purchases, self::TRACKER_END_POINT);
    }

    public function sendMagentoTokenToSqueezelyAndVerifyAuth($magentoToken) {
        $data = (array)json_decode($this->postData($magentoToken, self::VERIFY_API_LOGIN_END_POINT));

        if(isset($data['verified']) && $data['verified'] == true) {
            return true;
        }

        return false;
    }

}