<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Command\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as ConfigRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Model\ItemsQueue\CollectionFactory as ItemsQueueCollectionFactory;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;
use Squeezely\Plugin\Service\Product\GetData as ProductData;

/**
 * Sync invalidated products command model
 */
class SyncInvalidated
{

    /**
     * @var ItemsQueueResource
     */
    private $itemsQueueResource;
    /**
     * @var RequestRepository
     */
    private $requestRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var ProductData
     */
    private $productData;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var ItemsQueueCollectionFactory
     */
    private $itemsQueueCollectionFactory;

    /**
     * SyncInvalidated constructor.
     *
     * @param RequestRepository $requestRepository
     * @param ConfigRepository $configRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductData $productData
     * @param StoreRepositoryInterface $storeRepository
     * @param ItemsQueueCollectionFactory $itemsQueueCollection
     * @param ItemsQueueResource $itemsQueueResource
     */
    public function __construct(
        RequestRepository $requestRepository,
        ConfigRepository $configRepository,
        ProductCollectionFactory $productCollectionFactory,
        ProductData $productData,
        StoreRepositoryInterface $storeRepository,
        ItemsQueueCollectionFactory $itemsQueueCollection,
        ItemsQueueResource $itemsQueueResource
    ) {
        $this->requestRepository = $requestRepository;
        $this->configRepository = $configRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productData = $productData;
        $this->storeRepository = $storeRepository;
        $this->itemsQueueCollectionFactory = $itemsQueueCollection;
        $this->itemsQueueResource = $itemsQueueResource;
    }

    /**
     *  {@inheritdoc}
     */
    public function execute(): array
    {
        if (!$this->configRepository->isEnabled()) {
            return [
                'success' => false,
                'msg' => sprintf('<error>%s</error>', ConfigRepository::EXTENSION_DISABLED_ERROR)
            ];
        }

        $result = [];
        $storeIds = $this->configRepository->getAllEnabledStoreIds();
        foreach ($storeIds as $storeId) {
            $itemsCollection = $this->itemsQueueCollectionFactory->create();
            $itemsCollection->addFieldToFilter('store_id', $storeId)
                ->setPageSize(RequestRepository::PRODUCTS_PER_REQUEST)
                ->setCurPage(1);
            if ($itemsCollection->getSize() == 0) {
                $result[] = [
                    'success' => true,
                    'msg' => sprintf(
                        '<info>Store %s: there is no invalidated products</info>',
                        $storeId
                    )
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
                    ['products' => $productData]
                );
                if ($response['success'] == true) {
                    $result[] = [
                        'success' => true,
                        'msg' => sprintf(
                            '<info>Store %s, created %s, updated %s</info>',
                            $storeId,
                            $response['created'],
                            $response['updated']
                        )
                    ];
                    $connection = $this->itemsQueueResource->getConnection();
                    $tableName = $this->itemsQueueResource->getTable('squeezely_items_queue');
                    $connection->delete($tableName, ['store_id' => $storeId, 'product_sku in (?)' => $skus]);
                } else {
                    foreach ($response['errors'] as $error) {
                        $result[] = [
                            'success' => false,
                            'msg' => sprintf('<error>%s</error>', $error)
                        ];
                    }
                }
            } catch (\Exception $exception) {
                $result[] = [
                    'success' => false,
                    'msg' => sprintf('<error>Exception: %s</error>', $exception->getMessage())
                ];
            }
        }

        return $result;
    }
}
