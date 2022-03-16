<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\ItemsQueue;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Squeezely\Plugin\Api\ItemsQueue\DataInterface as ItemsQueueData;

/**
 * Item Queue repository interface class
 */
interface RepositoryInterface
{
    /**
     * Input exception text
     */
    public const INPUT_EXCEPTION = 'An ID is needed. Set the ID and try again.';

    /**
     * "No such entity" exception text
     */
    public const NO_SUCH_ENTITY_EXCEPTION = 'The item with id "%1" does not exist.';

    /**
     * "Could not delete" exception text
     */
    public const COULD_NOT_DELETE_EXCEPTION = 'Could not delete the item: %1';

    /**
     * "Could not save" exception text
     */
    public const COULD_NOT_SAVE_EXCEPTION = 'Could not save the item: %1';

    /**
     * Loads a specified account
     *
     * @param int $entityId
     *
     * @return ItemsQueueData
     * @throws LocalizedException
     */
    public function get(int $entityId): ItemsQueueData;

    /**
     * Return new item object
     *
     * @return ItemsQueueData
     */
    public function create();

    /**
     * Retrieves items matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList($searchCriteria);

    /**
     * Register entity to delete
     *
     * @param ItemsQueueData $item
     *
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        ItemsQueueData $item
    ): bool;

    /**
     * Deletes item entity by ID
     *
     * @param int $entityId
     *
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($entityId): bool;

    /**
     * Register entity to save
     *
     * @param ItemsQueueData $item
     *
     * @return ItemsQueueData
     * @throws LocalizedException
     */
    public function save(
        ItemsQueueData $item
    ): ItemsQueueData;

    /**
     * Get all items collection ids
     *
     * @return array
     */
    public function getAllIds(): array;

    /**
     * @param array $fields
     * @param int $limit
     * @param array $sort
     * @return SearchCriteria
     */
    public function buildSearchCriteria(array $fields, $limit = 0, $sort = []);
}
