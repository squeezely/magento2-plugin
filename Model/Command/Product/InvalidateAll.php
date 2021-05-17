<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Command\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as ConfigRepository;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;

/**
 * Invalidate all products command model
 */
class InvalidateAll
{

    /**
     * @var ItemsQueueResource
     */
    protected $itemsQueueResource;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var ProductCollection
     */
    private $productCollection;

    /**
     * InvalidateAll constructor.
     *
     * @param ConfigRepository $configRepository
     * @param ProductCollection $productCollectionFactory
     * @param ItemsQueueResource $itemsQueueResource
     * @param ProductCollection $productCollection
     */
    public function __construct(
        ConfigRepository $configRepository,
        ProductCollectionFactory $productCollectionFactory,
        ItemsQueueResource $itemsQueueResource,
        ProductCollection $productCollection
    ) {
        $this->configRepository = $configRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->itemsQueueResource = $itemsQueueResource;
        $this->productCollection = $productCollection;
    }

    /**
     * @return array
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
            $productCollection = $this->productCollection->create()->addStoreFilter($storeId);
            $skus = $productCollection->getColumnValues('sku');

            $connection = $this->itemsQueueResource->getConnection();
            foreach ($skus as $sku) {
                $select = $connection->select()
                    ->from($this->itemsQueueResource->getTable('squeezely_items_queue'))
                    ->where('product_sku=?', $sku)
                    ->where('store_id=?', $storeId);

                $fetchResult = $connection->fetchRow($select);
                if ($fetchResult == false) {
                    $connection->insert(
                        $this->itemsQueueResource->getTable('squeezely_items_queue'),
                        [
                            'product_sku' => $sku,
                            'store_id' => $storeId
                        ]
                    );
                }
            }

            $result[] = [
                'success' => true,
                'msg' => sprintf(
                    '<info>Store %s, invalidated products: %s</info>',
                    $storeId,
                    count($skus)
                )
            ];
        }

        return $result;
    }
}
