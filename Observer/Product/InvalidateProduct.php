<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Observer\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Service\Invalidate\ByProductId as InvalidateByProductId;

/**
 * Class InvalidateProduct
 * Invalidating product data after it saved
 */
class InvalidateProduct implements ObserverInterface
{

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @var InvalidateByProductId
     */
    private $invalidateByProductId;

    /**
     * InvalidateProduct constructor.
     * @param LogRepository $logRepository
     * @param InvalidateByProductId $invalidateByProductId
     */
    public function __construct(
        LogRepository $logRepository,
        InvalidateByProductId $invalidateByProductId
    ) {
        $this->logRepository = $logRepository;
        $this->invalidateByProductId = $invalidateByProductId;
    }

    /**
     * Add Invalidated Product to Queue
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $productId = $product->getId();
        $storeIds = $product->getStoreIds();

        try {
            foreach ($storeIds as $storeId) {
                $result = $this->invalidateByProductId->execute([$productId], (int)$storeId);

                // TODO DO WE NEED TO WRITE RESULT HERE TO LOG?
            }
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('InvalidateProduct', $exception->getMessage());
        }
    }
}
