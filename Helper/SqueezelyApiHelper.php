<?php

namespace Squeezely\Plugin\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

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
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var State
     */
    private $_state;
    /**
     * @var bool
     */
    private $storeMode;
    /**
     * @var bool
     */
    private $websiteMode;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        State $state,
        Context $context
    ) {
        parent::__construct($context);

        $this->scopeConfig = $scopeConfig;

        $this->websiteMode = $this->_request->getParam('website', 0) !== 0;
        $this->storeMode = $this->_request->getParam('store', 0) !== 0;

        $this->_storeManager = $storeManager;
        $this->_state = $state;

        $this->squeezelyAccountId = trim($this->getConfigValue(self::XML_PATH_SQUEEZELY_ID));
        $this->squeezelyApiKey = trim($this->getConfigValue(self::XML_PATH_SQUEEZELY_API_KEY));
        $this->squeezelyWebhookKey = trim($this->getConfigValue(self::XML_PATH_SQUEEZELY_WEBHOOK_KEY));

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

    public function sendCompleteRegistration($eventData) {
        return $this->postData($eventData, self::TRACKER_END_POINT);
    }

    public function sendMagentoTokenToSqueezelyAndVerifyAuth($magentoToken) {
        $data = (array)json_decode($this->postData($magentoToken, self::VERIFY_API_LOGIN_END_POINT));

        if(isset($data['verified']) && $data['verified'] == true) {
            return true;
        }

        return false;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    private function getCurrentStore() {
        if($this->_state->getAreaCode() == Area::AREA_ADMINHTML) {
            $storeId = (int)$this->_request->getParam('store', 0);
        } else {
            $storeId = true; // get current store from the store resolver
        }

        return $this->_storeManager->getStore($storeId);
    }

    /**
     * @return \Magento\Store\Api\Data\WebsiteInterface
     */
    private function getCurrentWebsite() {
        if($this->_state->getAreaCode() == Area::AREA_ADMINHTML) {
            $websiteId = (int)$this->_request->getParam('website', 0);
        } else {
            $websiteId = true; // get current store from the store resolver
        }

        return $this->_storeManager->getWebsite($websiteId);
    }

    /**
     * Retrieve config value by path, keeping in mind the current mode your are (website, store, or default)
     *
     * @param string $path The path through the tree of configuration values, e.g., 'general/store_information/name'
     * @return mixed
     */
    protected function getConfigValue($path) {
        $scopeCode = null;
        $configScope = ScopeInterface::SCOPE_STORES;

        if($this->storeMode) {
            $scopeCode = $this->getCurrentStore()->getId();
        }
        elseif($this->websiteMode) {
            $scopeCode = $this->getCurrentWebsite()->getId();
            $configScope = ScopeInterface::SCOPE_WEBSITES;
        }

        return trim($this->scopeConfig->getValue($path, $configScope, $scopeCode));
    }
}