<?php
declare(strict_types=1);
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Squeezely\Plugin\Model\ItemsQueue;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Model\AbstractModel;
use Squeezely\Plugin\Api\ItemsQueue\DataInterface as ItemsQueueData;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;

/**
 * Items Queue data class
 */
class Data extends AbstractModel implements ExtensibleDataInterface, ItemsQueueData
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(ItemsQueueResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): int
    {
        return (int)$this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getProductId(): int
    {
        return (int)$this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setProductId(int $productId): ItemsQueueData
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritDoc
     */
    public function getParentId(): int
    {
        return (int)$this->getData(self::PARENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setParentId(int $parentId): ItemsQueueData
    {
        return $this->setData(self::PARENT_ID, $parentId);
    }

    /**
     * @inheritDoc
     */
    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setStoreId(int $storeId): ItemsQueueData
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): ItemsQueueData
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
