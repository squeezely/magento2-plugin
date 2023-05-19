<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\ProcessingQueue;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 *  Processing Queue object interface
 */
interface DataInterface extends ExtensibleDataInterface
{

    /**
     * Constants for keys of data array.
     * Identical to the name of the getter in snake case.
     */
    public const ENTITY_ID = 'entity_id';
    public const TYPE = 'type';
    public const PROCESSING_DATA = 'processing_data';
    public const STORE_ID = 'store_id';
    public const ATTEMPTS = 'attempts';
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
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self;

    /**
     * @return array
     */
    public function getProcessingData(): array;

    /**
     * @param array $processingData
     * @return $this
     */
    public function setProcessingData(array $processingData): self;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId): self;

    /**
     * @param int $attempts
     * @return $this
     */
    public function setAttempts(int $attempts): self;

    /**
     * @return int
     */
    public function getAttempts(): int;

    /**
     * @return $this
     */
    public function addAttempt(): self;

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
