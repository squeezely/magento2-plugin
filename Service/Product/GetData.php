<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as StoreSyncConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;

/**
 * Product Data Service class
 */
class GetData
{

    /**
     * Base Product Attributes
     *
     * @var array
     */
    private $productApiFields = [
        'id',
        'title',
        'link',
        'description',
        'language',
        'price',
        'sale_price',
        'currency',
        'image_link',
        'availability',
        'condition',
        'inventory',
        'brand',
        'size',
        'color',
        'parent_id',
        'category_ids',
        'parent_url',
        'is_in_stock',
        'is_salable',
        'type_id',
        'visibility',
        'status'
    ];

    /**
     * Available products condition
     *
     * @var array
     */
    private $conditions = [
        'new',
        'used',
        'refurbished'
    ];

    /**
     * Base Product Attributes
     *
     * @var array
     */
    private $attributes = [
        'sku' => 'sku',
        'entity_id' => 'entity_id',
        'visibility' => 'visibility',
        'type_id' => 'type_id',
        'status' => 'status'
    ];

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var StoreSyncConfigRepository
     */
    private $storeSynConfigRepository;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var StockItemRepository
     */
    private $stockItem;
    /**
     * @var ConfigurableResource
     */
    private $configurableResource;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var Attribute
     */
    private $eavAttribute;
    /**
     * @var StoreInterface;
     */
    private $store;
    /**
     * Store Language
     */
    private $language;
    /**
     * Store currency
     */
    private $currency;
    /**
     * Image Url
     */
    private $imageUrl;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * GetData constructor.
     *
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreSyncConfigRepository $storeSynConfigRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param StockItemRepository $stockItem
     * @param ConfigurableResource $configurableResource
     * @param ProductRepositoryInterface $productRepository
     * @param Attribute $attribute
     * @param LogRepository $logRepository
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        StoreSyncConfigRepository $storeSynConfigRepository,
        StoreRepositoryInterface $storeRepository,
        ScopeConfigInterface $scopeConfig,
        StockItemRepository $stockItem,
        ConfigurableResource $configurableResource,
        ProductRepositoryInterface $productRepository,
        Attribute $attribute,
        LogRepository $logRepository
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeSynConfigRepository = $storeSynConfigRepository;
        $this->storeRepository = $storeRepository;
        $this->scopeConfig = $scopeConfig;
        $this->stockItem = $stockItem;
        $this->configurableResource = $configurableResource;
        $this->productRepository = $productRepository;
        $this->eavAttribute = $attribute;
        $this->logRepository = $logRepository;
    }

    /**
     * @param array $entityIds
     * @param int $storeId
     * @return array
     */
    public function execute(array $entityIds = [], $storeId = 0): array
    {
        $productData = [];

        $this->collectAttributes($storeId);
        try {
            $this->store = $this->storeRepository->getById($storeId);
        } catch (NoSuchEntityException $e) {
            return $productData;
        }

        foreach ($this->getProducts($entityIds, $storeId) as $product) {
            $oneProduct = [];
            foreach ($this->productApiFields as $field) {
                $oneProduct[$field] = $this->getAttributeValue($field, $product);
            }
            $productData[] = $oneProduct;
        }

        return $productData;
    }

    /**
     * Attribute collector
     *
     * @param int $storeId
     */
    private function collectAttributes(int $storeId): void
    {
        $this->attributes += [
            'title' => $this->storeSynConfigRepository->getAttributeName($storeId),
            'id' => $this->storeSynConfigRepository->getAttributeSku($storeId),
            'description' => $this->storeSynConfigRepository->getAttributeDescription($storeId),
            'brand' => $this->storeSynConfigRepository->getAttributeBrand($storeId),
            'color' => $this->storeSynConfigRepository->getAttributeColor($storeId),
            'size' => $this->storeSynConfigRepository->getAttributeSize($storeId),
            'condition' => $this->storeSynConfigRepository->getAttributeCondition($storeId)
        ];
    }

    /**
     * @param array $entityIds
     * @param int $storeId
     *
     * @return Collection
     */
    private function getProducts(array $entityIds = [], int $storeId = 0)
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addStoreFilter($storeId)
            ->addAttributeToSelect(array_values($this->attributes))
            ->addAttributeToSelect(['image', 'special_price'])
            ->addAttributeToFilter('sku', ['in' => $entityIds])
            ->addUrlRewrite()
            ->addFinalPrice();

        return $collection;
    }

    /**
     * @param string $field
     * @param Product $product
     * @return mixed|null
     */
    private function getAttributeValue(string $field, Product $product)
    {
        $attributeName = $this->attributes[$field] ?? null;

        if ($attributeName) {
            try {
                $productAttribute = $this->eavAttribute->loadByCode('catalog_product', $attributeName);
                if ($productAttribute->getId() && $productAttribute->getFrontendInput() == 'select') {
                    $value = $product->getAttributeText($attributeName);
                } else {
                    $value = $product->getData($attributeName);
                }
                if ($field == 'condition' && !in_array($value, $this->conditions)) {
                    $value = 'new';
                }
                return $value;
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('getAttributeValue', $exception->getMessage());
                return '';
            }
        }

        /** TOTO ADD REST OF LOGICS */
        switch ($field) {
            case 'link':
                return $product->getProductUrl();
            case 'price':
                return $product->getFinalPrice();
            case 'sale_price':
                return $product->getSpecialPrice();
            case 'availability':
                if ($product->isInStock()) {
                    return 'in stock';
                } else {
                    return 'out of stock';
                }
            case 'language':
                return $this->getLanguage();
            case 'currency':
                return $this->getCurrency();
            case 'image_link':
                return $this->getImageUrl() . $product->getImage();
            case 'inventory':
                try {
                    $productStock = $this->stockItem->get($product->getId());
                    return $productStock->getQty();
                } catch (NoSuchEntityException $e) {
                    return '';
                }
            // no break
            case 'parent_id':
                $parentId = $this->configurableResource->getParentIdsByChild($product->getId());
                if (isset($parentId[0]) && ($this->storeSynConfigRepository->getAttributeSku() == 'sku')) {
                    try {
                        $parentProduct = $this->productRepository->getById($parentId[0]);
                        return $parentProduct->getSku();
                    } catch (NoSuchEntityException $e) {
                        return '';
                    }
                } elseif (isset($parentId[0])) {
                    return $parentId[0];
                } else {
                    return null;
                }
            // no break
            case 'parent_url':
                $parentId = $this->configurableResource->getParentIdsByChild($product->getId());
                if (isset($parentId[0])) {
                    try {
                        $parentProduct = $this->productRepository->getById($parentId[0]);
                        return $parentProduct->getProductUrl();
                    } catch (NoSuchEntityException $e) {
                        return '';
                    }
                } else {
                    return null;
                }
            // no break
            case 'category_ids':
                return $product->getCategoryIds();
            case 'is_in_stock':
                return $product->isInStock();
            case 'is_salable':
                return $product->isSalable();
        }

        return null;
    }

    /**
     * @return string
     */
    private function getLanguage(): string
    {
        if (!$this->language) {
            $this->language = (string)$this->scopeConfig->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE,
                $this->store->getId()
            );
        }
        return $this->language;
    }

    /**
     * @return string
     */
    private function getCurrency(): string
    {
        if (!$this->currency) {
            try {
                $this->currency = $this->store->getCurrentCurrency()->getCode();
            } catch (\Exception $exception) {
                $this->currency = '';
            }
        }
        return $this->currency;
    }

    /**
     * @return string
     */
    private function getImageUrl(): string
    {
        if (!$this->imageUrl) {
            $this->imageUrl = $this->store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product';
        }
        return $this->imageUrl;
    }
}
