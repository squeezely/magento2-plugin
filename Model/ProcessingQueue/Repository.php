<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\ProcessingQueue;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\ProcessingQueue\DataInterface;
use Squeezely\Plugin\Api\ProcessingQueue\DataInterface as ProcessingQueueData;
use Squeezely\Plugin\Api\ProcessingQueue\RepositoryInterface as ProcessingQueueRepository;
use Squeezely\Plugin\Api\ProcessingQueue\SearchResultsInterface;
use Squeezely\Plugin\Api\ProcessingQueue\SearchResultsInterfaceFactory as ProcessingQueueSearchResultsFactory;
use Squeezely\Plugin\Model\ProcessingQueue\CollectionFactory as ProcessingQueueCollectionFactory;
use Squeezely\Plugin\Model\ProcessingQueue\DataFactory as ProcessingQueueDataFactory;
use Squeezely\Plugin\Model\ProcessingQueue\ResourceModel as ProcessingQueueResource;

/**
 * Processing Queue Repository
 */
class Repository implements ProcessingQueueRepository
{

    /**
     * @var ProcessingQueueSearchResultsFactory
     */
    private $processesQueueSearchResultsFactory;
    /**
     * @var ProcessingQueueCollectionFactory
     */
    private $processesQueueCollectionFactory;
    /**
     * @var ProcessingQueueResource
     */
    private $processesQueueResource;
    /**
     * @var ProcessingQueueDataFactory
     */
    private $processesQueueDataFactory;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * Repository constructor.
     * @param ProcessingQueueSearchResultsFactory $processesQueueSearchResultsFactory
     * @param ProcessingQueueCollectionFactory $processesQueueCollectionFactory
     * @param ProcessingQueueResource $processesQueueResource
     * @param ProcessingQueueDataFactory $processesQueueDataFactory
     * @param LogRepository $logRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        ProcessingQueueSearchResultsFactory $processesQueueSearchResultsFactory,
        ProcessingQueueCollectionFactory $processesQueueCollectionFactory,
        ProcessingQueueResource $processesQueueResource,
        ProcessingQueueDataFactory $processesQueueDataFactory,
        LogRepository $logRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->processesQueueSearchResultsFactory = $processesQueueSearchResultsFactory;
        $this->processesQueueCollectionFactory = $processesQueueCollectionFactory;
        $this->processesQueueResource = $processesQueueResource;
        $this->processesQueueDataFactory = $processesQueueDataFactory;
        $this->logRepository = $logRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $searchResults = $this->processesQueueSearchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $collection = $this->processesQueueCollectionFactory->create();
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrdersData = $searchCriteria->getSortOrders();
        if ($sortOrdersData) {
            foreach ($sortOrdersData as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $searchResults->setItems($collection->getData());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function create(): DataInterface
    {
        return $this->processesQueueDataFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $entityId): bool
    {
        $process = $this->get($entityId);
        return $this->delete($process);
    }

    /**
     * @inheritDoc
     */
    public function get(int $entityId): ProcessingQueueData
    {
        if (!$entityId) {
            $exceptionMsg = self::INPUT_EXCEPTION;
            throw new InputException(__($exceptionMsg));
        } elseif (!$this->processesQueueResource->isExists($entityId)) {
            $exceptionMsg = self::NO_SUCH_ENTITY_EXCEPTION;
            throw new NoSuchEntityException(__($exceptionMsg, $entityId));
        }
        return $this->processesQueueDataFactory->create()
            ->load($entityId);
    }

    /**
     * @inheritDoc
     */
    public function delete(ProcessingQueueData $process): bool
    {
        try {
            $this->processesQueueResource->delete($process);
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Delete process', $exception->getMessage());
            $exceptionMsg = self::COULD_NOT_DELETE_EXCEPTION;
            throw new CouldNotDeleteException(__(
                $exceptionMsg,
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function save(
        ProcessingQueueData $process
    ): ProcessingQueueData {
        try {
            $this->processesQueueResource->save($process);
            $this->logRepository->addDebugLog(
                sprintf('Added Backend Queue: %s', $process->getType()),
                $process->getProcessingData()
            );
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog(
                'Error on save process ' . $process->getType(),
                $exception->getMessage()
            );
        }
        return $process;
    }

    /**
     * @inheritDoc
     */
    public function getAllIds(): array
    {
        $collection = $this->processesQueueCollectionFactory->create();
        return $collection->getAllIds();
    }

    /**
     * @inheritDoc
     */
    public function buildSearchCriteria(array $fields, int $limit = 0, array $sort = []): SearchCriteria
    {
        $filterGroups = [];
        foreach ($fields as $field => $value) {
            $filter = $this->filterBuilder
                ->setField($field)
                ->setValue(current($value))
                ->setConditionType(key($value))
                ->create();
            $filterGroups[] = $this->filterGroupBuilder
                ->addFilter($filter)
                ->create();
        }
        $searchCriteria = $this->searchCriteriaBuilder
            ->setFilterGroups($filterGroups)
            ->create();
        if ($limit) {
            $searchCriteria->setPageSize($limit);
        }
        if (!empty($sort)) {
            $sortOrder = $this->sortOrderBuilder->setField($sort['field'])->setDirection($sort['direction'])->create();
            $searchCriteria->setSortOrders([$sortOrder]);
        }
        return $searchCriteria;
    }
}
