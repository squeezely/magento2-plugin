<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Block\Adminhtml\System\Design;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as StoreSyncConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;

/**
 * Products Block for system config
 */
class Products extends Template implements RendererInterface
{

    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Squeezely_Plugin::system/config/fieldset/products.phtml';

    /**
     * @var StoreSyncConfigRepository
     */
    private $storeSyncConfigRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ItemsQueueResource
     */
    private $itemsQueueResource;
    /**
     * @var ProductCollection
     */
    private $productCollection;

    /**
     * InvalidatedProducts constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param StoreSyncConfigRepository $storeSyncConfigRepository
     * @param LogRepository $logRepository
     * @param ItemsQueueResource $itemsQueueResource
     * @param ProductCollection $productCollection
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        StoreSyncConfigRepository $storeSyncConfigRepository,
        LogRepository $logRepository,
        ItemsQueueResource $itemsQueueResource,
        ProductCollection $productCollection
    ) {
        $this->storeManager = $storeManager;
        $this->storeSyncConfigRepository = $storeSyncConfigRepository;
        $this->logRepository = $logRepository;
        $this->itemsQueueResource = $itemsQueueResource;
        $this->productCollection = $productCollection;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element)
    {
        $this->setData('element', $element);
        return $this->toHtml();
    }

    /**
     * Returns content configuration data array for all stores
     *
     * @return array
     */
    public function getContentStoreData(): array
    {
        $storeData = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int)$store->getStoreId();
            try {
                $storeData[$storeId] = [
                    'store_id' => $storeId,
                    'name' => $store->getName(),
                    'totals' => $this->getContentData($storeId),
                    'invalidate_url' => $this->getUrl(
                        'sqzl/products/invalidate',
                        ['store_id' => $storeId]
                    ),
                ];
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('LocalizedException', $e->getMessage());
                continue;
            }
        }

        return $storeData;
    }

    /**
     * @param int $storeId
     * @return array
     */
    private function getContentData(int $storeId): array
    {
        $totals = [];

        $productCollection = $this->productCollection->create()->addStoreFilter($storeId);
        $totals['all'] = $productCollection->getSize();

        $connection = $this->itemsQueueResource->getConnection();
        $selectInvalidated = $connection->select()->from(
            $this->itemsQueueResource->getTable('squeezely_items_queue'),
            'product_sku'
        )->where('store_id = ?', $storeId);
        $totals['invalidated'] = count($connection->fetchAll($selectInvalidated));
        return $totals;
    }
}
