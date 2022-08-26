<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\Request;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;

/**
 * API request service interface
 */
interface RepositoryInterface
{
    /**
     * Product api endpoint
     */
    public const PRODUCT_END_POINT = "v1/products";

    /**
     * Product api endpoint
     */
    public const PRODUCT_DELETE_ENDPOINT = "products";

    /**
     * Tracker api endpoint
     */
    public const TRACKER_END_POINT = "v1/track";

    /**
     * Verify api login endpoint v1
     */
    public const VERIFY_API_LOGIN_END_POINT = "v1/verifyAuth?channel=2";

    /**
     * Event cocd for Email Opt in
     */
    public const EMAIL_OPT_IN_EVENT_NAME = 'EmailOptIn';

    /**
     * Event cocd for Purchase
     */
    public const PURCHASE_EVENT_NAME = 'Purchase';

    /**
     * Event code for CRMUpdate
     */
    public const CRM_UPDATE_EVENT_NAME = 'CRMUpdate';

    /**
     * Number of products per one request
     */
    public const PRODUCTS_PER_REQUEST = 250;

    /**
     * Send product data to API
     *
     * @param array $products
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function sendProducts(array $products): array;

    /**
     * @param array $products
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function sendDeleteProducts(array $products): array;

    /**
     * Send purchased order data to API
     *
     * @param array $purchases
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function sendPurchases(array $purchases): array;

    /**
     * Send CRMUpdate data to API
     *
     * @param array $data
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function sendCRMUpdate(array $data): array;

    /**
     * Send registered customer data to API
     *
     * @param array $eventData
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function sendCompleteRegistration(array $eventData): array;

    /**
     * Send request for credentials validation
     *
     * @param array $magentoToken
     * @return bool
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function sendMagentoTokenToSqueezelyAndVerifyAuth(array $magentoToken, int $storeId): bool;
}
