<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\ProcessingQueue;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Squeezely\Plugin\Api\ProcessingQueue\DataInterface as ProcessingQueueData;

/**
 * Processing Queue repository interface class
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
    public const NO_SUCH_ENTITY_EXCEPTION = 'The process with id "%1" does not exist.';

    /**
     * "Could not delete" exception text
     */
    public const COULD_NOT_DELETE_EXCEPTION = 'Could not delete the process: %1';

    /**
     * "Could not save" exception text
     */
    public const COULD_NOT_SAVE_EXCEPTION = 'Could not save the process: %1';

    /**
     * Loads a specified process
     *
     * @param int $entityId
     *
     * @return ProcessingQueueData
     * @throws LocalizedException
     */
    public function get(int $entityId): ProcessingQueueData;

    /**
     * Return new process object
     *
     * @return ProcessingQueueData
     */
    public function create(): DataInterface;

    /**
     * Retrieves processes matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Register entity to delete
     *
     * @param ProcessingQueueData $process
     *
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        ProcessingQueueData $process
    ): bool;

    /**
     * Deletes process entity by ID
     *
     * @param int $entityId
     *
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $entityId): bool;

    /**
     * Register entity to save
     *
     * @param ProcessingQueueData $process
     *
     * @return ProcessingQueueData
     */
    public function save(
        ProcessingQueueData $process
    ): ProcessingQueueData;

    /**
     * Get all processes collection ids
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
    public function buildSearchCriteria(array $fields, int $limit = 0, array $sort = []): SearchCriteria;
}
