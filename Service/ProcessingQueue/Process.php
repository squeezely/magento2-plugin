<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\ProcessingQueue;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\ProcessingQueue\RepositoryInterface as ProcessingQueueRepository;
use Squeezely\Plugin\Model\ProcessingQueue\Collection as ProcessingQueueCollection;
use Squeezely\Plugin\Model\ProcessingQueue\CollectionFactory as ProcessingQueueCollectionFactory;
use Squeezely\Plugin\Model\ProcessingQueue\Data;
use Squeezely\Plugin\Model\ProcessingQueue\ResourceModel;

/**
 * Queue Processing Service class
 */
class Process
{

    /**
     * @var ProcessingQueueCollectionFactory
     */
    private $processingQueueCollection;
    /**
     * @var Processors\Order
     */
    private $order;
    /**
     * @var Processors\Product
     */
    private $product;
    /**
     * @var ProcessingQueueRepository
     */
    private $processingQueueRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var Processors\DefaultProcessor
     */
    private $defaultProcessor;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ProcessingQueueCollectionFactory $processingQueueCollection
     * @param ProcessingQueueRepository $processingQueueRepository
     * @param ConfigRepository $configRepository
     * @param ResourceConnection $resourceConnection
     * @param Processors\Order $order
     * @param Processors\Product $product
     * @param Processors\DefaultProcessor $defaultProcessor
     */
    public function __construct(
        ProcessingQueueCollectionFactory $processingQueueCollection,
        ProcessingQueueRepository $processingQueueRepository,
        ConfigRepository $configRepository,
        ResourceConnection $resourceConnection,
        Processors\Order $order,
        Processors\Product $product,
        Processors\DefaultProcessor $defaultProcessor
    ) {
        $this->processingQueueCollection = $processingQueueCollection;
        $this->order = $order;
        $this->product = $product;
        $this->processingQueueRepository = $processingQueueRepository;
        $this->configRepository = $configRepository;
        $this->defaultProcessor = $defaultProcessor;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function execute(): void
    {
        $storeIds = $this->configRepository->getAllEnabledBackendSyncStoreIds();
        foreach ($storeIds as $storeId) {
            $events = $this->getQueuedEvents($storeId);
            /* @var Data $process */
            foreach ($events as $process) {
                $data = $process->getProcessingData();
                switch ($process->getType()) {
                    case 'order':
                        $result = $this->order->execute((int)$data['order_id']);
                        break;
                    case 'product':
                        $result = $this->product->execute((int)$data['product_id'], $storeId);
                        break;
                    default:
                        $result = $this->defaultProcessor->execute($data, $storeId);
                        break;
                }

                if ($result) {
                    $this->processingQueueRepository->delete($process);
                } else {
                    $process->addAttempt();
                    $this->processingQueueRepository->save($process);
                }
            }
        }
    }

    /**
     * @param int $storeId
     * @return ProcessingQueueCollection
     */
    private function getQueuedEvents(int $storeId): ProcessingQueueCollection
    {
        return $this->processingQueueCollection->create()
            ->addFieldToFilter('store_id', $storeId)
            ->setPageSize($this->configRepository->getPoolSize())
            ->setCurPage(1);
    }

    /**
     * @return void
     */
    public function cleanupQueue(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->delete(
            $connection->getTableName(ResourceModel::ENTITY_TABLE),
            ['attempts > 3']
        );
        $connection->delete(
            $connection->getTableName(ResourceModel::ENTITY_TABLE),
            ['store_id = 0']
        );
    }
}
