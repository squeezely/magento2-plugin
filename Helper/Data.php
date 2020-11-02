<?php
namespace Squeezely\Plugin\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper {

    const SQUEEZELY_PLUGIN_NAME = 'Squeezely_Plugin';
    const XML_PATH_SQUEEZELY_ENABLED = 'squeezely_plugin/general/enabled';
    const XML_PATH_SQUEEZELY_ID = 'squeezely_plugin/general/SQZLY_id';
    const XML_PATH_SQUEEZELY_API_KEY = 'squeezely_plugin/general/squeezely_api_key';
    const XML_PATH_SQUEEZELY_WEBHOOK_KEY = 'squeezely_plugin/general/squeezely_webhook_key';
    const SQUEEZELY_COOKIE_NAME = 'sqzllocal';

    /**
     * Check if the module is enabled
     *
     * @return boolean 0 or 1
     */
    public function getIsEnabled() {
        return $this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get container id
     *
     * @return string
     */
    public function getSQZLYId() {
        $squeezelyId = $this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_ID, ScopeInterface::SCOPE_STORE);
        return trim($squeezelyId);
    }

    /**
     * @return string
     */
    public function getSqueezelyApiKey() {
        $apiKey = $this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_API_KEY, ScopeInterface::SCOPE_STORE);
        return trim($apiKey);
    }

    /**
     * @return string
     */
    public function getSqueezelyWebhookKey() {
        $webhookKey = $this->scopeConfig->getValue(self::XML_PATH_SQUEEZELY_WEBHOOK_KEY, ScopeInterface::SCOPE_STORE);
        return trim($webhookKey);
    }
}
