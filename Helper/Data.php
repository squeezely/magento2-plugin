<?php
namespace Squeezely\Plugin\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    const XML_PATH_SQUEEZELY_ENABLED = 'squeezely_plugin/general/enabled';
    const XML_PATH_SQUEEZELY_ID = 'squeezely_plugin/general/SQZLY_id';
    const XML_PATH_SQUEEZELY_API_KEY = 'squeezely_plugin/general/squeezely_api_key';
    const XML_PATH_SQUEEZELY_WEBHOOK_KEY = 'squeezely_plugin/general/squeezely_webhook_key';

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if the module is enabled
     *
     * @return boolean 0 or 1
     */
    public function getIsEnable() {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_ENABLED, $storeScope);
    }

    /**
     * Get container id
     *
     * @return string
     */
    public function getSQZLYId() {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $SQZLYId = $this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_ID, $storeScope);
        return trim($SQZLYId);
    }

    /**
     * @return string
     */
    public function getSqueezelyApiKey() {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $SQZLYApiKey = $this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_API_KEY, $storeScope);
        return trim($SQZLYApiKey);
    }

    /**
     * @return string
     */
    public function getSqueezelyWebhookKey() {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $SQZLYWebHookKey = $this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_WEBHOOK_KEY, $storeScope);
        return trim($SQZLYWebHookKey);
    }
}
