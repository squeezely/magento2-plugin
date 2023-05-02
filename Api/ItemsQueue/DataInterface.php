<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\ItemsQueue;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 *  Items Queue object interface
 */
interface DataInterface extends ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    public const ENTITY_ID = 'entity_id';
    public const PRODUCT_ID = 'product_id';
    public const PRODUCT_SKU = 'product_sku';
    public const STORE_ID = 'store_id';
    public const CREATED_AT = 'created_at';

    /**
     * @return int
     */
    public function getEntityId(): int;

    /**
     * @param mixed $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * @return int
     */
    public function getProductId(): int;

    /**
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * @return string
     */
    public function getProductSku(): string;

    /**
     * @param string $productSku
     * @return $this
     */
    public function setProductSku(string $productSku): self;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): self;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;
}
