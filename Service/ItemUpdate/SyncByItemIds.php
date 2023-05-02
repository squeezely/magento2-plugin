<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\ItemUpdate;

use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;
use Squeezely\Plugin\Service\Product\GetData as ProductData;
use Squeezely\Plugin\Api\ItemsQueue\RepositoryInterface as ItemsQueueRepository;

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
     * @return array
     */
    public function execute($itemId)
    {
        $return = ['success' => false, 'message' => ''];
        if (!$this->configRepository->isEnabled()) {
            $return['message'] = 'Items sync is not enabled';
            return $return;
        }

        try {
            $item = $this->itemsQueueRepository->get((int)$itemId);
        } catch (\Exception $e) {
            $return['message'] = 'No item found';
            return $return;
        }

        $productData = $this->productData->execute(
            [$item->getProductSku()],
            $item->getStoreId()
        );
        try {
            $response = $this->requestRepository->sendProducts(
                ['products' => $productData]
            );
            if ($response['success'] == true || $response['message'] == 'skipped empty products') {
                $this->logRepository->addDebugLog(
                    'Sync Items',
                    __(
                        'Store %1, created %2, updated %3',
                        $item->getStoreId(),
                        $response['created'],
                        $response['updated']
                    )
                );
                $connection = $this->itemsQueueResource->getConnection();
                $tableName = $this->itemsQueueResource->getTable('squeezely_items_queue');
                $connection->delete(
                    $tableName,
                    "store_id = " . $item->getStoreId() .  " AND product_sku = '" . $item->getProductSku() . "'"
                );
            } else {
                foreach ($response['errors'] as $error) {
                    $this->logRepository->addDebugLog('Sync Items', $error);
                }
            }
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Sync Items', $exception->getMessage());
            $return['message'] = $exception->getMessage();
            return $return;
        }
        $return['success'] = true;
        $return['message'] = 'Items has been synced';
        return $return;
    }
}
