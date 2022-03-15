<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\Config;

/**
 * Config repository interface
 */
interface RepositoryInterface
{
    /**
     * Module Name
     */
    public const SQUEEZELY_PLUGIN_NAME = 'Squeezely_Plugin';

    /**
     * Config paths for 'general'-group
     */
    public const XML_PATH_ENABLED = 'squeezely/general/enabled';
    public const XML_PATH_ACCOUNT_ID = 'squeezely/general/account_id';
    public const XML_PATH_API_KEY = 'squeezely/general/api_key';
    public const XML_PATH_WEBHOOK_KEY = 'squeezely/general/webhook_key';
    public const XML_PATH_EXTENSION_VERSION = 'squeezely/general/version';
    public const SQUEEZELY_COOKIE_NAME = 'sqzllocal';

    /**
     * Generic extension is not enables message
     */
    public const EXTENSION_DISABLED_ERROR = 'Extension is not enabled';

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool;

    /**
     * Get container id
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAccountId(int $storeId = null): string;

    /**
     * Get API key
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getApiKey(int $storeId = null): string;

    /**
     * Get webhook key
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getWebhookKey(int $storeId = null): string;

    /**
     * Get extension version
     *
     * @return string
     */
    public function getExtensionVersion(): string;

    /**
     * Get Magento Version
     *
     * @return string
     */
    public function getMagentoVersion(): string;
}
