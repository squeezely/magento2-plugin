<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product as Subject;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Service\Invalidate\ByProductId as InvalidateByProductId;

/**
 * Product Save Plugin
 */
class Product
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
     * Invalidate product after save
     *
     * @param Subject $product
     * @param Subject $result
     * @return ProductInterface
     */
    public function afterSave(
        Subject $product
    ): ProductInterface {
        $productId = $product->getId();
        $storeIds = $product->getStoreIds();
        try {
            foreach ($storeIds as $storeId) {
                $result = $this->invalidateByProductId->execute([$productId], (int)$storeId);
                $this->logRepository->addDebugLog('InvalidateProduct Plugin', $result);
            }
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('InvalidateProduct Plugin', $exception->getMessage());
        }
        return $product;
    }
}
