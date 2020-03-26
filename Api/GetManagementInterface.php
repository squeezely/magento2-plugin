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
}