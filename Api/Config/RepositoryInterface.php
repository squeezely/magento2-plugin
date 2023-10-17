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
interface RepositoryInterface extends System\StoreSyncInterface
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
     * All Events Codes
     */
    public const VIEW_CONTENT_EVENT = 'ViewContent';
    public const VIEW_CATEGORY_EVENT = 'ViewCategory';
    public const SEARCH_EVENT = 'Search';
    public const ADD_TO_CART_EVENT = 'AddToCart';
    public const REMOVE_FROM_CART_EVENT = 'RemoveFromCart';
    public const INITIATE_CHECKOUT_EVENT = 'InitiateCheckout';
    public const EMAIL_OPT_IN_EVENT = 'EmailOptIn';
    public const PURCHASE_EVENT = 'Purchase';
    public const PRE_PURCHASE_EVENT = 'PrePurchase';
    public const CRM_UPDATE_EVENT = 'CRMUpdate';
    public const COMPLETE_REGISTRATION_EVENT = 'CompleteRegistration';
    public const PRODUCT_DELETE = 'ProductDelete';

    /**
     * Generic extension is not enables message
     */
    public const EXTENSION_DISABLED_ERROR = 'Extension is not enabled';

    /**
     * Header links
     */
    public const MODULE_DOCUMENTATION_LINK = 'https://squeezely.atlassian.net/wiki/spaces/SG/pages/1399652355/Magento+2+Plugin'; // phpcs:ignore
    public const MODULE_SUPPORT_LINK = 'https://squeezely.atlassian.net/servicedesk/customer/portals';
    public const MODULE_API_LINK = 'https://squeezely.tech/company/settings';
    public const MODULE_MAGMODULES_LINK = 'https://www.magmodules.eu/';

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

    /**
     * Get Documentation Link
     *
     * @return string
     */
    public function getDocumentationLink(): string;

    /**
     * Get Support Link
     *
     * @return string
     */
    public function getSupportLink(): string;

    /**
     * Get Api Link
     *
     * @return string
     */
    public function getApiLink(): string;

    /**
     * Get Magmodules Link
     *
     * @return string
     */
    public function getMagmodulesLink(): string;
}
