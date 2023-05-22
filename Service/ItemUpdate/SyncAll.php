<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\ItemUpdate;

use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Model\ItemsQueue\CollectionFactory as ItemsQueueCollectionFactory;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;
use Squeezely\Plugin\Service\Product\GetData as ProductData;

/**
 * Service to sync products
 */
class SyncAll
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
     * @param ProductData $productData
     * @param LogRepository $logRepository
     * @param ItemsQueueResource $itemsQueueResource
     */
    public function __construct(
        RequestRepository $requestRepository,
        ConfigRepository $configRepository,
        ItemsQueueCollectionFactory $itemsQueueCollection,
        ProductData $productData,
        LogRepository $logRepository,
        ItemsQueueResource $itemsQueueResource
    ) {
        $this->requestRepository = $requestRepository;
        $this->configRepository = $configRepository;
        $this->itemsQueueCollectionFactory = $itemsQueueCollection;
        $this->productData = $productData;
        $this->logRepository = $logRepository;
        $this->itemsQueueResource = $itemsQueueResource;
    }

    /**
     * Send Invalidated products to API
     *
     * @return array
     */
    public function execute(): array
    {
        $return = [];
        if (!$this->configRepository->isEnabled()) {
            return ['success' => false, 'message' => 'Module is not enabled'];
        }

        $storeIds = $this->configRepository->getAllEnabledStoreSyncStoreIds();
        foreach ($storeIds as $storeId) {
            $itemsCollection = $this->getCollection($storeId);
            if ($itemsCollection->getSize() == 0) {
                $return[$storeId] = [
                    'success' => true,
                    'message' => sprintf('Store %s: there are no invalidated products', $storeId)
                ];
                continue;
            }

            $skus = $itemsCollection->getColumnValues('product_sku');
            $productData = $this->productData->execute(
                $skus,
                (int)$storeId
            );

            try {
                $response = $this->requestRepository->sendProducts(
                    ['products' => $productData],
                    $storeId
                );

                $message = __(
                    'Store %1: created %2, updated %3, skipped: %4',
                    $storeId,
                    $response['created'] ?? 0,
                    $response['updated'] ?? 0,
                    !empty($response['errors']) ? count($response['errors']) : 0
                );

                $this->logRepository->addDebugLog('Sync Items', $message);
                $this->removeFromQueue($skus, $storeId);
                $return[$storeId] = ['success' => true, 'message' => $message];
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('Sync Items', $exception->getMessage());
                $return[$storeId] = ['success' => false, 'message' => $exception->getMessage()];
            }
        }
        return $return;
    }

    /**
     * @param $storeId
     * @return mixed
     */
    private function getCollection($storeId)
    {
        return $this->itemsQueueCollectionFactory->create()
            ->addFieldToFilter('store_id', $storeId)
            ->setPageSize(RequestRepository::PRODUCTS_PER_REQUEST)
            ->setCurPage(1);
    }

    /**
     * @param array $skus
     * @param int $storeId
     * @return void
     */
    private function removeFromQueue(array $skus, int $storeId)
    {
        $connection = $this->itemsQueueResource->getConnection();
        $connection->delete(
            $this->itemsQueueResource->getTable('squeezely_items_queue'),
            [
                'store_id = ?' => $storeId,
                'product_sku in (?)' => $skus
            ]
        );
    }
}
