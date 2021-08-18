<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\Webapi;

/**
 * Interface ManagementInterface
 */
interface ManagementInterface
{
    /**
     * GET for Post api
     *
     * @param int $productId
     *
     * @return array
     */
    public function getParentIdOfProduct(int $productId): array;

    /**
     * GET for api
     *
     * @param string $productIds
     * @param int $storeId
     *
     * @return mixed[]
     */
    public function getProductsInfo(string $productIds, int $storeId);

    /**
     * Get products data
     *
     * @param int $storeId
     * @param int $pageSize
     * @param int $currentPage
     * @param string $updatedAtFrom
     * @return mixed
     */
    public function getProducts(
        int $storeId,
        int $pageSize = 10,
        int $currentPage = 1,
        string $updatedAtFrom = '1970-01-01'
    );

    /**
     * GET module info
     *
     * @return mixed[]
     */
    public function getModuleInfo();

    /**
     * GET module settings
     *
     * @return mixed[]
     */
    public function getModuleSettings();

    /**
     * GET module settings by store
     *
     * @param int $storeId
     *
     * @return mixed[]
     */
    public function getModuleSettingsByStore(int $storeId);

    /**
     * GET for api
     *
     * @param int $storeId
     *
     * @return mixed[]
     * @api
     */
    public function invalidateAll(int $storeId);

    /**
     * GET for api
     *
     * @param int $storeId
     * @param string $productIds
     *
     * @return mixed[]
     * @api
     */
    public function invalidate(int $storeId, string $productIds);
}
