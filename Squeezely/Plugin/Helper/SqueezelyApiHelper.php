<?php

namespace Squeezely\Plugin\Helper;

use Magento\Framework\App\Helper\Context;

class SqueezelyApiHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    const XML_PATH_SQUEEZELY_API_KEY = 'squeezely_plugin/general/squeezely_api_key';
    const XML_PATH_SQUEEZELY_WEBHOOK_KEY = 'squeezely_plugin/general/squeezely_webhook_key';
    const XML_PATH_SQUEEZELY_ID = 'squeezely_plugin/general/SQZLY_id';

    private $squeezelyApiKey;
    private $squeezelyWebhookKey;
    private $squeezelyAccountId;

//    const PRODUCT_END_POINT = "https://squeezely.tech/api/products";
//    const PURCHASE_END_POINT = "https://squeezely.tech/api/track";

    // Test webhook hattar Dev
    const PRODUCT_END_POINT = "https://hattardev.sqzly.nl/api/products";
    const PURCHASE_END_POINT = "https://hattardev.sqzly.nl/api/track";
    const VERIFY_API_LOGIN_END_POINT = "https://hattardev.sqzly.nl/api/v1/verifyAuth?channel=2";
    const SEND_MAGENTO_TOKEN_END_POINT = "https://hattardev.sqzly.nl/callback/magento2_webhook";


    // TEST WEBHOOKS TODO: REMOVE THIS
//    const PRODUCT_END_POINT = "https://webhook.site/6314a4b0-0ada-4851-8612-f532cbc185e7";
//    const PURCHASE_END_POINT = "https://webhook.site/6314a4b0-0ada-4851-8612-f532cbc185e7";



    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->squeezelyAccountId = trim($this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_ID, $storeScope));
        $this->squeezelyApiKey  = trim($this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_API_KEY, $storeScope));
        $this->squeezelyWebhookKey = trim($this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_WEBHOOK_KEY, $storeScope));
    }


    private function postData($fields, $url)
    {
        $json = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST , true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-AUTH-ACCOUNT: $this->squeezelyAccountId",
            "X-AUTH-APIKEY: $this->squeezelyApiKey",
            "Content-Type: application/json",
            "Content-Length: " . strlen($json)
        ]);

        // USED FOR TESTING REQUEST
    //        $response = curl_exec($ch);
    //        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    //        $headers = substr($response, 0, $headerSize);
    //        $content = substr($response, $headerSize);
    //        $curlError = curl_errno($ch);
    //        $curlError .= ': '. curl_error($ch);
    //
    //        print_r($response);
    //        print_r($headerSize);
    //        print_r($headers);
    //        print_r($content);
    //        print_r($curlError);

        $result = curl_exec($ch);

        // USED FOR TESTING REQUEST
    //        print_r($result);
        curl_close($ch);
        return $result;
    }

    private function getData($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-AUTH-ACCOUNT: $this->squeezelyAccountId",
            "X-AUTH-APIKEY: $this->squeezelyApiKey",
        ]);

        $result = curl_exec($ch);

        curl_close($ch);
        return $result;
    }

    public function sendProducts($products)
    {
        return $this->postData($products, self::PRODUCT_END_POINT);
    }

    public function sendPurchases($purchases)
    {
        return $this->postData($purchases, self::PURCHASE_END_POINT);
    }

    public function verifySqueezelyAuth()
    {
        $data = json_decode($this->getData(self::VERIFY_API_LOGIN_END_POINT), true);

        if(isset($data['verified']) && $data['verified'] === true) {
            return true;
        }

        return false;
    }

    public function sendMagentoTokenToSqueezely($magentoToken)
    {

        $data = $this->postData($magentoToken, self::VERIFY_API_LOGIN_END_POINT);
//        if(i$data)

        return $data;
    }

}