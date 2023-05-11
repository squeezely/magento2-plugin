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
use Squeezely\Plugin\Model\ProcessingQueue\Data;
use Squeezely\Plugin\Service\ProcessingQueue\Processors\DefaultProcessor;
use Squeezely\Plugin\Service\ProcessingQueue\Processors\Order;
use Squeezely\Plugin\Service\ProcessingQueue\Processors\Product;
use Squeezely\Plugin\Model\ProcessingQueue\ResourceModel;

/**
 * Queue Processing Service class
 */
class Process
{

    /**
     * @var ProcessingQueueCollection
     */
    private $processingQueueCollection;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var Product
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
     * @var DefaultProcessor
     */
    private $defaultProcessor;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Process constructor.
     *
     * @param ProcessingQueueCollection $processingQueueCollection
     * @param Order $order
     * @param Product $product
     * @param ProcessingQueueRepository $processingQueueRepository
     * @param ConfigRepository $configRepository
     * @param DefaultProcessor $defaultProcessor
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ProcessingQueueCollection $processingQueueCollection,
        Order $order,
        Product $product,
        ProcessingQueueRepository $processingQueueRepository,
        ConfigRepository $configRepository,
        DefaultProcessor $defaultProcessor,
        ResourceConnection $resourceConnection
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
        $this->processingQueueCollection->setPageSize(
            $this->configRepository->getPoolSize()
        )->setCurPage(1);

        /* @var Data $process */
        foreach ($this->processingQueueCollection as $process) {
            $data = $process->getProcessingData();
            switch ($process->getType()) {
                case 'order':
                    $result = $this->order->execute((int)$data['order_id']);
                    break;
                case 'product':
                    $result = $this->product->execute((int)$data['product_id']);
                    break;
                default:
                    $result = $this->defaultProcessor->execute($data);
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
    }
}
