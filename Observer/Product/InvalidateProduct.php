<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Observer\Product;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;

/**
 * Class InvalidateProduct
 * Invalidating product data after it saved
 */
class InvalidateProduct implements ObserverInterface
{

    /**
     * @var ItemsQueueResource
     */
    private $itemsQueueResource;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ConfigurableResource
     */
    private $configurableResource;

    /**
     * InvalidateProduct constructor.
     * @param ItemsQueueResource $itemsQueueResource
     * @param LogRepository $logRepository
     * @param ConfigurableResource $configurableResource
     */
    public function __construct(
        ItemsQueueResource $itemsQueueResource,
        LogRepository $logRepository,
        ConfigurableResource $configurableResource
    ) {
        $this->itemsQueueResource = $itemsQueueResource;
        $this->logRepository = $logRepository;
        $this->configurableResource = $configurableResource;
    }

    /**
     * Add Invalidated Product to Queue
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $connection = $this->itemsQueueResource->getConnection();

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $productId = $product->getId();
        $storeIds = $product->getStoreIds();
        $parentIds = implode(',', $this->configurableResource->getParentIdsByChild($productId));

        try {
            foreach ($storeIds as $storeId) {
                $select = $connection->select()
                    ->from($this->itemsQueueResource->getTable('squeezely_items_queue'))
                    ->where('product_sku=?', $productId)
                    ->where('store_id=?', $storeId);

                $result = $connection->fetchRow($select);
                if ($result == false) {
                    $connection->insert(
                        $this->itemsQueueResource->getTable('squeezely_items_queue'),
                        [
                            'product_id' => $productId,
                            'parent_id' => $parentIds,
                            'store_id' => $storeId
                        ]
                    );
                }
            }
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('InvalidateProduct', $exception->getMessage());
        }
    }
}
