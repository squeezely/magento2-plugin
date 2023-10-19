<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config;

use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepositoryInterface;

/**
 * Generic config provider class
 */
class Repository extends System\StoreSyncRepository implements ConfigRepositoryInterface
{

    /**
     * @inheritDoc
     */
    public function getAccountId(int $storeId = null): string
    {
        return trim($this->getStoreValue(self::XML_PATH_ACCOUNT_ID, $storeId));
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

    /**
     * @inheritDoc
     */
    public function isEnabled(int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_ENABLED, $storeId);
    }

    /**
     * Documentation link for extension.
     *
     * @return string
     */
    public function getDocumentationLink(): string
    {
        return self::MODULE_DOCUMENTATION_LINK;
    }

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink(): string
    {
        return self::MODULE_SUPPORT_LINK;
    }

    /**
     * Api link for extension.
     *
     * @return string
     */
    public function getApiLink(): string
    {
        return self::MODULE_API_LINK;
    }

    /**
     * Magmodules link for extension.
     *
     * @return string
     */
    public function getMagmodulesLink(): string
    {
        return self::MODULE_MAGMODULES_LINK;
    }
}
