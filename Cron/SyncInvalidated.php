<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Cron;

use Magento\Store\Api\StoreRepositoryInterface;
use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Model\ItemsQueue\CollectionFactory as ItemsQueueCollectionFactory;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;
use Squeezely\Plugin\Service\Product\GetData as ProductData;

/**
 * Cron class to sync products
 */
class SyncInvalidated
{
    /**
     * @var ItemsQueueResource
     */
    protected $itemsQueueResource;
    /**
     * @var RequestRepository
     */
    private $requestRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var ItemsQueueCollectionFactory
     */
    private $itemsQueueCollectionFactory;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var ProductData
     */
    private $productData;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * SyncInvalidated constructor.
     *
     * @param RequestRepository $requestRepository
     * @param ConfigRepository $configRepository
     * @param ItemsQueueCollectionFactory $itemsQueueCollection
     * @param StoreRepositoryInterface $storeRepository
     * @param ProductData $productData
     * @param LogRepository $logRepository
     * @param ItemsQueueResource $itemsQueueResource
     */
    public function __construct(
        RequestRepository $requestRepository,
        ConfigRepository $configRepository,
        ItemsQueueCollectionFactory $itemsQueueCollection,
        StoreRepositoryInterface $storeRepository,
        ProductData $productData,
        LogRepository $logRepository,
        ItemsQueueResource $itemsQueueResource
    ) {
        $this->requestRepository = $requestRepository;
        $this->configRepository = $configRepository;
        $this->itemsQueueCollectionFactory = $itemsQueueCollection;
        $this->storeRepository = $storeRepository;
        $this->productData = $productData;
        $this->logRepository = $logRepository;
        $this->itemsQueueResource = $itemsQueueResource;
    }

    /**
     * Send Invalidated products to API
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->configRepository->isEnabled()
            || $this->configRepository->getCronFrequency() == '-'
        ) {
            return;
        }

        $storeIds = $this->configRepository->getAllEnabledStoreIds();
        foreach ($storeIds as $storeId) {
            $itemsCollection = $this->itemsQueueCollectionFactory->create();
            $itemsCollection->addFieldToFilter('store_id', $storeId);
            if ($itemsCollection->getSize() == 0) {
                continue;
            }
            $productData = $this->productData->execute(
                $itemsCollection->getAllIds(),
                (int)$storeId
            );
            try {
                $response = $this->requestRepository->sendProducts(
                    ['products' => $productData]
                );
                if ($response['success'] == true) {
                    $this->logRepository->addDebugLog(
                        'CRON',
                        __(
                            'Store %1, created %2, updated %3',
                            $storeId,
                            $response['created'],
                            $response['updated']
                        )
                    );
                    $connection = $this->itemsQueueResource->getConnection();
                    $tableName = $this->itemsQueueResource->getTable('squeezely_items_queue');
                    $connection->delete($tableName, ['store_id' => $storeId]);
                } else {
                    foreach ($response['errors'] as $error) {
                        $this->logRepository->addDebugLog('SyncInvalidated (cron)', $error);
                    }
                }
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('SyncInvalidated (cron)', $exception->getMessage());
            }
        }
    }
}
