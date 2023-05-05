<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\ItemUpdate;

use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as ConfigRepository;
use Squeezely\Plugin\Api\ItemsQueue\RepositoryInterface as ItemsQueueRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;
use Squeezely\Plugin\Service\Product\GetData as ProductData;

/**
 * Service to sync products
 */
class SyncByItemIds
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
     * @var ProductData
     */
    private $productData;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ItemsQueueRepository
     */
    private $itemsQueueRepository;

    /**
     * SyncByItemIds constructor.
     *
     * @param RequestRepository $requestRepository
     * @param ConfigRepository $configRepository
     * @param ProductData $productData
     * @param LogRepository $logRepository
     * @param ItemsQueueResource $itemsQueueResource
     * @param ItemsQueueRepository $itemsQueueRepository
     */
    public function __construct(
        RequestRepository $requestRepository,
        ConfigRepository $configRepository,
        ProductData $productData,
        LogRepository $logRepository,
        ItemsQueueResource $itemsQueueResource,
        ItemsQueueRepository $itemsQueueRepository
    ) {
        $this->requestRepository = $requestRepository;
        $this->configRepository = $configRepository;
        $this->productData = $productData;
        $this->logRepository = $logRepository;
        $this->itemsQueueResource = $itemsQueueResource;
        $this->itemsQueueRepository = $itemsQueueRepository;
    }

    /**
     * Send Invalidated products to API
     *
     * @param $itemId
     * @return array
     */
    public function execute($itemId): array
    {
        if (!$this->configRepository->isEnabled()) {
            return ['success' => false, 'message' => 'Items sync is not enabled'];
        }

        try {
            $item = $this->itemsQueueRepository->get((int)$itemId);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'No item found'];
        }

        $productData = $this->productData->execute(
            [$item->getProductSku()],
            $item->getStoreId()
        );

        try {
            $response = $this->requestRepository->sendProducts(
                ['products' => $productData]
            );

            $message = __(
                'Created %1, updated %2, skipped: %3',
                $response['created'] ?? 0,
                $response['updated'] ?? 0,
                !empty($response['errors']) ? count($response['errors']) : 0
            );

            $this->logRepository->addDebugLog('Sync Items', $message);
            $this->removeFromQueue($item->getProductSku(), $item->getStoreId());
            return ['success' => true, 'message' => $message];
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Sync Items', $exception->getMessage());
            return ['success' => false, 'message' => $exception->getMessage()];
        }
    }

    /**
     * @param string $sku
     * @param int $storeId
     * @return void
     */
    private function removeFromQueue(string $sku, int $storeId)
    {
        $connection = $this->itemsQueueResource->getConnection();
        $connection->delete(
            $this->itemsQueueResource->getTable('squeezely_items_queue'),
            [
                'store_id = ?' => $storeId,
                'product_sku = ?' => $sku
            ]
        );
    }
}
