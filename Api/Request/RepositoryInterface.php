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
     * Number of products per one request
     */
    public const PRODUCTS_PER_REQUEST = 150;

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
     * Send event data to API
     *
     * @param array $eventData
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function sendToPlatform(array $eventData): array;

    /**
     * Send request for credentials validation
     *
     * @param array $magentoToken
     * @param int $storeId
     * @return bool
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function sendMagentoTokenToSqueezelyAndVerifyAuth(array $magentoToken, int $storeId): bool;
}
