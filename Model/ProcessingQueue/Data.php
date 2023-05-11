<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\ProcessingQueue;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Squeezely\Plugin\Api\ProcessingQueue\DataInterface as ProcessingQueueData;
use Squeezely\Plugin\Model\ProcessingQueue\ResourceModel as ProcessingQueueResource;

/**
 * Processing Queue data class
 */
class Data extends AbstractModel implements ExtensibleDataInterface, ProcessingQueueData
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Data constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Serializer $serializer
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Serializer $serializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(ProcessingQueueResource::class);
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
    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setType(string $type): ProcessingQueueData
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritDoc
     */
    public function getProcessingData(): array
    {
        $data = $this->getData(self::PROCESSING_DATA);
        if (is_string($data)) {
            $data = $this->serializer->unserialize($data);
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function setProcessingData(array $processingData): ProcessingQueueData
    {
        return $this->setData(self::PROCESSING_DATA, $processingData);
    }

    /**
     * @inheritDoc
     */
    public function getAttempts(): int
    {
        return (int)$this->getData(self::ATTEMPTS);
    }

    /**
     * @inheritDoc
     */
    public function setAttempts(int $attempts): ProcessingQueueData
    {
        return $this->setData(self::ATTEMPTS, $attempts);
    }

    /**
     * @inheritDoc
     */
    public function addAttempt(): ProcessingQueueData
    {
        $attempts = $this->getAttempts() + 1;
        return $this->setData(self::ATTEMPTS, $attempts);
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
    public function setCreatedAt(string $createdAt): ProcessingQueueData
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
