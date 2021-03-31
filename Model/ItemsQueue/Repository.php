<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\ItemsQueue;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\ItemsQueue\DataInterface as ItemsQueueData;
use Squeezely\Plugin\Api\ItemsQueue\RepositoryInterface as ItemsQueueRepository;
use Squeezely\Plugin\Api\ItemsQueue\SearchResultsInterfaceFactory as ItemsQueueSearchResultsFactory;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Model\ItemsQueue\CollectionFactory as ItemsQueueCollectionFactory;
use Squeezely\Plugin\Model\ItemsQueue\DataFactory as ItemsQueueDataFactory;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;

/**
 * Items Queue Repository
 */
class Repository implements ItemsQueueRepository
{

    /**
     * @var ItemsQueueSearchResultsFactory
     */
    private $itemsQueueSearchResultsFactory;
    /**
     * @var ItemsQueueCollectionFactory
     */
    private $itemsQueueCollectionFactory;
    /**
     * @var ItemsQueueResource
     */
    private $itemsQueueResource;
    /**
     * @var ItemsQueueDataFactory
     */
    private $itemsQueueDataFactory;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
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
     * @param ItemsQueueSearchResultsFactory $itemsQueueSearchResultsFactory
     * @param ItemsQueueCollectionFactory $itemsQueueCollectionFactory
     * @param ItemsQueueResource $itemsQueueResource
     * @param ItemsQueueDataFactory $itemsQueueDataFactory
     * @param LogRepository $logRepository
     * @param ConfigRepository $configRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        ItemsQueueSearchResultsFactory $itemsQueueSearchResultsFactory,
        ItemsQueueCollectionFactory $itemsQueueCollectionFactory,
        ItemsQueueResource $itemsQueueResource,
        ItemsQueueDataFactory $itemsQueueDataFactory,
        LogRepository $logRepository,
        ConfigRepository $configRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->itemsQueueSearchResultsFactory = $itemsQueueSearchResultsFactory;
        $this->itemsQueueCollectionFactory = $itemsQueueCollectionFactory;
        $this->itemsQueueResource = $itemsQueueResource;
        $this->itemsQueueDataFactory = $itemsQueueDataFactory;
        $this->logRepository = $logRepository;
        $this->configRepository = $configRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * @inheritDoc
     */
    public function create()
    {
        return $this->itemsQueueDataFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getList($searchCriteria)
    {
        $searchResults = $this->itemsQueueSearchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $collection = $this->itemsQueueCollectionFactory->create();
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
    public function deleteById($entityId): bool
    {
        $item = $this->get($entityId);
        return $this->delete($item);
    }

    /**
     * @inheritDoc
     */
    public function get(int $entityId): ItemsQueueData
    {
        if (!$entityId) {
            $exceptionMsg = self::INPUT_EXCEPTION;
            throw new InputException(__($exceptionMsg));
        } elseif (!$this->itemsQueueResource->isExists($entityId)) {
            $exceptionMsg = self::NO_SUCH_ENTITY_EXCEPTION;
            throw new NoSuchEntityException(__($exceptionMsg, $entityId));
        }
        return $this->itemsQueueDataFactory->create()
            ->load($entityId);
    }

    /**
     * @inheritDoc
     */
    public function delete(ItemsQueueData $item): bool
    {
        try {
            $this->itemsQueueResource->delete($item);
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Delete item', $exception->getMessage());
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
        ItemsQueueData $item
    ): ItemsQueueData {
        try {
            $this->itemsQueueResource->save($item);
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Save item', $exception->getMessage());
            $exceptionMsg = self::COULD_NOT_SAVE_EXCEPTION;
            throw new CouldNotSaveException(__(
                $exceptionMsg,
                $exception->getMessage()
            ));
        }
        return $item;
    }

    /**
     * @inheritDoc
     */
    public function getAllIds(): array
    {
        $collection = $this->itemsQueueCollectionFactory->create();
        return $collection->getAllIds();
    }

    /**
     * @inheritDoc
     */
    public function buildSearchCriteria(array $fields, $limit = 0, $sort = [])
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
            /** @var SortOrder $sortOrder */
            $sortOrder = $this->sortOrderBuilder->setField($sort['field'])->setDirection($sort['direction'])->create();
            $searchCriteria->setSortOrders([$sortOrder]);
        }
        return $searchCriteria;
    }
}
