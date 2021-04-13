<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Controller\Adminhtml\Products;

use Magento\Backend\App\Action;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as StoreSyncConfigRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Model\ItemsQueue\CollectionFactory as ItemsQueueCollectionFactory;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;
use Squeezely\Plugin\Service\Product\GetData as ProductDataService;

/**
 * Class Invalidate
 * Contoller to invalidate all products
 */
class Invalidate extends Action
{

    /**
     * Error Message: not enabled
     */
    const ERROR_MSG_ENABLED = 'Store sync not enabled for this store, please enable this first.';

    /**
     * Error Message
     */
    const ERROR_MSG_NO_ITEMS = 'Something went wrong, please try again';

    /**
     * Success Message
     */
    const SUCCESS_MSG = 'All products were synced.';

    /**
     * @var ProductCollection
     */
    private $productCollection;
    /**
     * @var StoreSyncConfigRepository
     */
    private $storeSyncConfigRepository;
    /**
     * @var ProductDataService
     */
    private $productDataDervice;
    /**
     * @var ItemsQueueResource
     */
    private $itemsQueueResource;
    /**
     * @var ItemsQueueCollectionFactory
     */
    private $itemsQueueCollectionFactory;
    /**
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * Invalidate constructor.
     *
     * @param Action\Context $context
     * @param ProductCollection $productCollection
     * @param StoreSyncConfigRepository $storeSyncConfigRepository
     * @param ProductDataService $productDataDervice
     * @param ItemsQueueResource $itemsQueueResource
     * @param ItemsQueueCollectionFactory $itemsQueueCollection
     * @param RequestRepository $requestRepository
     */
    public function __construct(
        Action\Context $context,
        ProductCollection $productCollection,
        StoreSyncConfigRepository $storeSyncConfigRepository,
        ProductDataService $productDataDervice,
        ItemsQueueResource $itemsQueueResource,
        ItemsQueueCollectionFactory $itemsQueueCollection,
        RequestRepository $requestRepository
    ) {
        $this->messageManager = $context->getMessageManager();
        $this->productCollection = $productCollection;
        $this->storeSyncConfigRepository = $storeSyncConfigRepository;
        $this->productDataDervice = $productDataDervice;
        $this->itemsQueueResource = $itemsQueueResource;
        $this->itemsQueueCollectionFactory = $itemsQueueCollection;
        $this->requestRepository = $requestRepository;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $storeId = (int)$this->getRequest()->getParam('store_id');

        if (!$this->storeSyncConfigRepository->isEnabled($storeId)) {
            $msg = self::ERROR_MSG_ENABLED;
            $this->messageManager->addErrorMessage(__($msg));
            return $resultRedirect->setPath(
                $this->_redirect->getRefererUrl()
            );
        }

        $this->invalidateAllProducts($storeId);

        return $resultRedirect->setPath(
            $this->_redirect->getRefererUrl()
        );
    }

    /**
     * Invalidate all products by store
     *
     * @param int $storeId
     */
    private function invalidateAllProducts(int $storeId)
    {
        $productCollection = $this->productCollection->create()->addStoreFilter($storeId);
        $skus = $productCollection->getColumnValues('sku');

        $connection = $this->itemsQueueResource->getConnection();
        foreach ($skus as $sku) {
            $select = $connection->select()
                ->from($this->itemsQueueResource->getTable('squeezely_items_queue'))
                ->where('product_sku=?', $sku)
                ->where('store_id=?', $storeId);

            $result = $connection->fetchRow($select);
            if ($result == false) {
                $connection->insert(
                    $this->itemsQueueResource->getTable('squeezely_items_queue'),
                    [
                        'product_sku' => $sku,
                        'store_id' => $storeId
                    ]
                );
            }
        }
    }
}
