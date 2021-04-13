<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepositoryInterface;

/**
 * Generic config provider class
 */
class Repository implements ConfigRepositoryInterface
{

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ProductMetadataInterface
     */
    private $metadata;

    /**
     * Repository constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductMetadataInterface $metadata
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ProductMetadataInterface $metadata
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->metadata = $metadata;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_ENABLED, $storeId);
    }

    /**
     * Get config value flag
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     *
     * @return bool
     */
    protected function getFlag(
        string $path,
        int $storeId = null,
        string $scope = null
    ): bool {
        if (!$storeId) {
            $storeId = (int)$this->_getStore()->getId();
        }
        $scope = $scope ?? ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->isSetFlag($path, $scope, (int)$storeId);
    }

    /**
     * Get current store
     *
     * @return StoreInterface
     */
    private function _getStore(): StoreInterface
    {
        try {
            return $this->storeManager->getStore();
        } catch (Exception $e) {
            if ($store = $this->storeManager->getDefaultStoreView()) {
                return $store;
            }
        }
        $stores = $this->storeManager->getStores();
        return reset($stores);
    }

    /**
     * @inheritDoc
     */
    public function getAccountId(int $storeId = null): string
    {
        return trim($this->getStoreValue(self::XML_PATH_ACCOUNT_ID, $storeId));
    }

    /**
     * Get store config value
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     * @return string
     */
    protected function getStoreValue(
        string $path,
        int $storeId = null,
        string $scope = null
    ): string {
        if (!$storeId) {
            $storeId = (int)$this->_getStore()->getId();
        }
        $scope = $scope ?? ScopeInterface::SCOPE_STORE;
        return (string)$this->scopeConfig->getValue($path, $scope, (int)$storeId);
    }

    /**
     * @inheritDoc
     */
    public function getApiKey(int $storeId = null): string
    {
        return trim($this->getStoreValue(self::XML_PATH_API_KEY, $storeId));
    }

    /**
     * @inheritDoc
     */
    public function getWebhookKey(int $storeId = null): string
    {
        return trim($this->getStoreValue(self::XML_PATH_WEBHOOK_KEY, $storeId));
    }

    /**
     * {@inheritDoc}
     */
    public function getMagentoVersion(): string
    {
        return $this->metadata->getVersion();
    }

    /**
     * {@inheritDoc}
     */
    public function getExtensionVersion(): string
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_VERSION);
    }
}
