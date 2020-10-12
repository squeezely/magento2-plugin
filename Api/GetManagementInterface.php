<?php
namespace Squeezely\Plugin\Api;


interface GetManagementInterface {


    /**
     * GET for Post api
     *
     * @param string $productId
     * @return string
     */
    public function getParentIdOfProduct($productId);

    /**
     * GET for api
     *
     * @param string $productIds
     * @param int $storeId
     * @return mixed[]
     */
    public function getProductsInfo($productIds, $storeId);
}