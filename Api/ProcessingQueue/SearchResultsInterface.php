<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\ProcessingQueue;

use Magento\Framework\Api\SearchResultsInterface as FrameworkSearchResultsInterface;
use Squeezely\Plugin\Api\ProcessingQueue\DataInterface as ProcessingQueueData;

/**
 * Interface for items queue search results.
 */
interface SearchResultsInterface extends FrameworkSearchResultsInterface
{
    /**
     * Gets processes
     *
     * @return ProcessingQueueData[]
     */
    public function getItems(): array;

    /**
     * Sets processes
     *
     * @param ProcessingQueueData[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self;
}
