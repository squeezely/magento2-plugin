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
     * GET module info
     *
     * @return mixed[]
     */
    public function getModuleInfo();
}
