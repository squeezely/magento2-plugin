<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository as Subject;
use Squeezely\Plugin\Api\ProcessingQueue\RepositoryInterface as ProcessingQueueRepository;

/**
 * ProductRepository Plugin
 */
class ProductRepository
{
    /**
     * @var ProcessingQueueRepository
     */
    private $processingQueueRepository;

    /**
     * ProductRepository constructor.
     *
     * @param ProcessingQueueRepository $processingQueueRepository
     */
    public function __construct(
        ProcessingQueueRepository $processingQueueRepository
    ) {
        $this->processingQueueRepository = $processingQueueRepository;
    }

    /**
     * @param Subject $subject
     * @param $result
     * @param ProductInterface $product
     * @return mixed
     */
    public function afterDelete(Subject $subject, $result, ProductInterface $product)
    {
        if ($result === true) {
            foreach ($product->getStoreIds() as $storeId) {
                $process = $this->processingQueueRepository->create();
                $process->setType('product')
                    ->setStoreId($storeId)
                    ->setProcessingData([
                        'product_id' => $product->getId()
                    ]);
                $this->processingQueueRepository->save($process);
            }
        }
        return $result;
    }
}
