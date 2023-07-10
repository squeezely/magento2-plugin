<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Webapi;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use Squeezely\Plugin\Api\Webapi\ManagementInterface;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;
use Squeezely\Plugin\Service\Invalidate\ByProductId as InvalidateByProductId;
use Squeezely\Plugin\Service\Invalidate\ByStore as InvalidateByStore;
use Squeezely\Plugin\Service\Product\GetData as ProductDataService;

/**
 * WebApi Repository
 */
class Repository implements ManagementInterface
{

    /**
     * Attribute map
     *
     * @var array
     */
    private $resulrtMap = [
        'id' => 'sku',
        'entity_id' => 'id',
        'link' => 'url',
        'language' => 'locale',
        'sales_price' => 'sale_price',
        'image_link' => 'image',
    ];

    /**
     * @var Configurable
     */
    private $catalogProductTypeConfigurable;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var ProductDataService
     */
    private $productDataService;
    /**
     * @var ConfigRepositoryInterface
     */
    private $configRepository;
    /**
     * @var ProductCollection
     */
    private $productCollection;
    /**
     * @var ItemsQueueResource
     */
    private $itemsQueueResource;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var InvalidateByProductId
     */
    private $invalidateByProductId;
    /**
     * @var InvalidateByStore
     */
    private $invalidateByStore;

    /**
     * @param Configurable $catalogProductTypeConfigurable
     * @param JsonSerializer $jsonSerializer
     * @param InvalidateByStore $invalidateByStore
     * @param InvalidateByProductId $invalidateByProductId
     * @param ProductDataService $productDataService
     * @param ConfigRepositoryInterface $configRepository
     * @param ProductCollection $productCollection
     * @param ItemsQueueResource $itemsQueueResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Configurable $catalogProductTypeConfigurable,
        JsonSerializer $jsonSerializer,
        InvalidateByStore $invalidateByStore,
        InvalidateByProductId $invalidateByProductId,
        ProductDataService $productDataService,
        ConfigRepositoryInterface $configRepository,
        ProductCollection $productCollection,
        ItemsQueueResource $itemsQueueResource,
        StoreManagerInterface $storeManager
    ) {
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->jsonSerializer = $jsonSerializer;
        $this->invalidateByStore = $invalidateByStore;
        $this->invalidateByProductId = $invalidateByProductId;
        $this->productDataService = $productDataService;
        $this->configRepository = $configRepository;
        $this->productCollection = $productCollection;
        $this->itemsQueueResource = $itemsQueueResource;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getParentIdOfProduct(int $productId): array
    {
        return $this->catalogProductTypeConfigurable
            ->getParentIdsByChild($productId);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductsInfo(string $productIds, int $storeId)
    {
        $ids = $this->jsonSerializer->unserialize($productIds);
        $products = $this->productDataService->execute($ids, $storeId);
        foreach ($products as $productId => $productData) {
            foreach ($this->resulrtMap as $oldKey => $newKey) {
                if (isset($products[$productId][$oldKey])) {
                    $products[$productId][$newKey] = $products[$productId][$oldKey];
                    unset($products[$productId][$oldKey]);
                }
            }
        }

        return $products;
    }

    /**
     * {@inheritDoc}
     */
    public function getProducts(
        int $storeId,
        int $pageSize = 10,
        int $currentPage = 1,
        string $updatedAtFrom = '1970-01-01'
    ) {
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $updatedAtFrom)) {
            return 'Param updatedAtFrom is invalid format. Please use YYYY-MM-DD';
        }
        try {
            $timeStart = microtime(true);
            $productCollection = $this->productCollection->create()->addStoreFilter($storeId);
            $productCollection
                ->addAttributeToSelect('sku')
                ->setPageSize($pageSize)
                ->setCurPage($currentPage)
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter('updated_at', ['from' => $updatedAtFrom]);
            $size = $productCollection->getSize();
            $skus = $productCollection->getColumnValues('sku');
        } catch (\Exception $e) {
            return __('No store with id = %1 found', $storeId)->render();
        }
        $products = $this->productDataService->execute($skus, $storeId);
        foreach ($products as $productId => $productData) {
            foreach ($this->resulrtMap as $oldKey => $newKey) {
                if (isset($products[$productId][$oldKey])) {
                    $products[$productId][$newKey] = $products[$productId][$oldKey];
                    unset($products[$productId][$oldKey]);
                }
            }
        }
        $result = [
            [
                'result' => [
                    'total' => $size,
                    'page' => $currentPage,
                    'output' => count($products),
                    'totalPages' => ceil($size / $pageSize),
                    'processingTime' => number_format((microtime(true) - $timeStart), 2)
                ],
                'items' => $products
            ]
        ];
        if ($updatedAtFrom != '1970-01-01') {
            $result[0]['result']['updatedFrom'] = $updatedAtFrom;
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getModuleInfo()
    {
        return [
            [
                'name' => ConfigRepositoryInterface::SQUEEZELY_PLUGIN_NAME,
                'setup_version' => str_replace('v', '', $this->configRepository->getExtensionVersion()),
                'magento_version' => $this->configRepository->getMagentoVersion()
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getModuleSettings(): array
    {
        return $this->getModuleConfigValues(0);
    }

    /**
     * get module settings by store
     *
     * @param int|null $storeId
     *
     * @return array
     */
    private function getModuleConfigValues(int $storeId = null): array
    {
        return [
            'general' =>
                [
                    'enabled' => (string)$this->configRepository->isEnabled($storeId),
                    'account_id' => $this->configRepository->getAccountId($storeId),
                    'api_key' => $this->configRepository->getApiKey($storeId) ?
                        'set_' . strlen($this->configRepository->getApiKey($storeId)) : 'not_set',
                    'webhook_key' => $this->configRepository->getWebhookKey($storeId) ?
                        'set_' . strlen($this->configRepository->getWebhookKey($storeId)) : 'not_set',
                ],
            'store_sync' =>
                [
                    'enabled' => (string)$this->configRepository->isStoreSyncEnabled($storeId),
                    'language' => $this->configRepository->getLanguage($storeId),
                    'name' => $this->configRepository->getAttributeName($storeId),
                    'description' => $this->configRepository->getAttributeDescription($storeId),
                    'brand' => $this->configRepository->getAttributeBrand($storeId),
                    'size' => $this->configRepository->getAttributeSize($storeId),
                    'color' => $this->configRepository->getAttributeColor($storeId),
                    'condition' => $this->configRepository->getAttributeCondition($storeId),
                    'use_parent_image_for_simples' => $this->configRepository->getUseParentImage($storeId),
                    'extra_fields' => $this->getExtraFields($storeId),
                    'product_updates' => $this->getProductUpdates($storeId)
                ],
            'frontend_events' =>
                [
                    'enabled' => (string)$this->configRepository->isFrontendEventsEnabled($storeId)
                ],
            'backend_events' =>
                [
                    'enabled' => (string)$this->configRepository->isBackendEventsEnabled($storeId),
                    'events' => $this->configRepository->getEnabledBackendEvents($storeId)
                ],
            'advanced_options' =>
                [
                    'debug_enabled' => (string)$this->configRepository->isDebugEnabled(),
                    'endpoint_data_url' => $this->configRepository->getEndpointDataUrl(),
                    'endpoint_tracker_url' => $this->configRepository->getEndpointTrackerUrl(),
                    'api_request_uri' => $this->configRepository->getApiRequestUri()
                ]
        ];
    }

    /**
     * Get extra products fields
     *
     * @param int|null $storeId
     *
     * @return array
     */
    private function getExtraFields(int $storeId = null): array
    {
        $value = [];
        $extraFields = $this->jsonSerializer->unserialize($this->configRepository->getExtraFields($storeId));
        if (is_array($extraFields)) {
            foreach ($extraFields as $extraField) {
                $value[$extraField['name']] = $extraField['attribute'];
            }
        }
        return $value;
    }

    /**
     * Get products updates
     *
     * @param int|null $storeId
     *
     * @return array
     */
    private function getProductUpdates(int $storeId = null): array
    {
        $value = [];
        if ($storeId) {
            $value[] = $this->getStoreProducts($storeId);
        } else {
            foreach ($this->storeManager->getStores() as $store) {
                $value[] = $this->getStoreProducts((int)$store->getId());
            }
        }
        return $value;
    }

    /**
     * get products updates by store
     *
     * @param int|null $storeId
     *
     * @return array
     */
    private function getStoreProducts(int $storeId = null): array
    {
        $value = [];
        try {
            $productCollection = $this->productCollection->create()->addStoreFilter($storeId);
            $value[$storeId] = ['products' => $productCollection->getSize()];

            $connection = $this->itemsQueueResource->getConnection();
            $selectInvalidated = $connection->select()->from(
                $this->itemsQueueResource->getTable('squeezely_items_queue'),
                'product_sku'
            )->where('store_id = ?', $storeId);

            $value[$storeId]['invalidated'] = count($connection->fetchAll($selectInvalidated));

        } catch (NoSuchEntityException $e) {
            $value[$storeId] = __('No store with id = %1 found', $storeId)->render();
        }
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getModuleSettingsByStore(int $storeId): array
    {
        if ($storeId == -1) {
            $value = [];
            foreach ($this->storeManager->getStores() as $store) {
                $value[$store->getId()] = $this->getModuleConfigValues((int)$store->getId());
            }
            return $value;
        } else {
            return $this->getModuleConfigValues($storeId);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function invalidateAll(int $storeId)
    {
        return $this->invalidateByStore->execute($storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function invalidate(int $storeId, string $productIds)
    {
        try {
            $productIdsDecoded = $this->jsonSerializer->unserialize($productIds);
        } catch (\Exception $e) {
            $productIdsDecoded = [];
        }

        if (!is_array($productIdsDecoded)) {
            $productIdsDecoded = [(int)$productIds];
        }
        return [
            $this->invalidateByProductId->execute($productIdsDecoded, $storeId)
        ];
    }
}
