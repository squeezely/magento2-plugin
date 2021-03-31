<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\ItemsQueue;

use Magento\Framework\Api\SearchResultsInterface as FrameworkSearchResultsInterface;
use Squeezely\Plugin\Api\ItemsQueue\DataInterface as ItemsQueueData;

/**
 * Interface for items queue search results.
 */
interface SearchResultsInterface extends FrameworkSearchResultsInterface
{
    /**
     * Gets items
     *
     * @return ItemsQueueData[]
     */
    public function getItems(): array;

    /**
     * Sets items
     *
     * @param ItemsQueueData[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self;
}
