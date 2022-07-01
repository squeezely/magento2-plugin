<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\Product;

use Magento\Bundle\Model\Product\Type as BundleTypeModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\UrlInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedTypeModel;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Magento\Catalog\Helper\Data as TaxHelper;

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
        'entity_id',
        'title',
        'link',
        'description',
        'language',
        'price',
        'sale_price',
        'currency',
        'image_link',
        'images',
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
        'status',
        'updated_at'
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
        'id' => 'sku',
        'entity_id' => 'entity_id',
        'visibility' => 'visibility',
        'type_id' => 'type_id',
        'status' => 'status',
        'updated_at' => 'updated_at'
    ];

    /**
     * Custom product attributes
     *
     * @var array
     */
    private $customFields = [];

    /**
     * Loaded Images
     *
     * @var array
     */
    private $images = [];

    /**
     * @var string
     */
    private $linkField;

    /**
     * @var array
     */
    private $entityIds = [];

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
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
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var UrlFinderInterface
     */
    private $urlFinder;
    /**
     * @var ProductResource
     */
    private $productResource;
    /**
     * @var BundleTypeModel
     */
    private $bundleModel;
    /**
     * @var GroupedTypeModel
     */
    private $groupedModel;
    /**
     * @var File
     */
    private $file;
    /**
     * @var DirectoryList
     */
    private $directoryList;
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var TaxHelper
     */
    private $taxHelper;

    /**
     * GetData constructor.
     *
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ConfigRepository $configRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param StockItemRepository $stockItem
     * @param ConfigurableResource $configurableResource
     * @param Attribute $attribute
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     * @param UrlFinderInterface $urlFinder
     * @param ProductResource $productResource
     * @param BundleTypeModel $bundleModel
     * @param GroupedTypeModel $groupedModel
     * @param File $file
     * @param DirectoryList $directoryList
     * @param ResourceConnection $resource
     * @param MetadataPool $metadataPool
     * @param TaxHelper $taxHelper
     * @throws \Exception
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ConfigRepository $configRepository,
        StoreRepositoryInterface $storeRepository,
        ScopeConfigInterface $scopeConfig,
        StockItemRepository $stockItem,
        ConfigurableResource $configurableResource,
        Attribute $attribute,
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer,
        UrlFinderInterface $urlFinder,
        ProductResource $productResource,
        BundleTypeModel $bundleModel,
        GroupedTypeModel $groupedModel,
        File $file,
        DirectoryList $directoryList,
        ResourceConnection $resource,
        MetadataPool $metadataPool,
        TaxHelper $taxHelper
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->configRepository = $configRepository;
        $this->storeRepository = $storeRepository;
        $this->scopeConfig = $scopeConfig;
        $this->stockItem = $stockItem;
        $this->configurableResource = $configurableResource;
        $this->eavAttribute = $attribute;
        $this->logRepository = $logRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->urlFinder = $urlFinder;
        $this->productResource = $productResource;
        $this->bundleModel = $bundleModel;
        $this->groupedModel = $groupedModel;
        $this->file = $file;
        $this->resource = $resource;
        $this->directoryList = $directoryList;
        $this->taxHelper = $taxHelper;
        $this->linkField = $metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * @param array $skus
     * @param int $storeId
     * @return array
     */
    public function execute(array $skus = [], $storeId = 0): array
    {
        $this->logRepository->addDebugLog('GetProductData', __('Start'));
        $this->logRepository->addDebugLog(
            'GetProductData',
            'Requested skus: ' . $this->jsonSerializer->serialize($skus)
        );
        $productData = [];

        $this->collectAttributes($storeId);
        $customFields = $this->getCustomFields($storeId);
        try {
            $this->store = $this->storeRepository->getById($storeId);
        } catch (NoSuchEntityException $e) {
            return $productData;
        }

        foreach ($this->getProducts($skus, $storeId) as $product) {
            if (!$product->getPrice()) {
                $product->setPrice(
                    $product->getBasePrice()
                );
            }
            $oneProduct = [];
            $parentId = $this->getParentId($product);
            foreach ($this->productApiFields as $field) {
                $oneProduct[$field] = $this->getAttributeValue($field, $product, $parentId, $storeId);
            }
            $oneProduct['custom_fields'] = [];
            foreach ($customFields as $customField) {
                $oneProduct['custom_fields'][$customField['name']] =
                    $this->getAttributeValue($customField['attribute'], $product, $parentId, $storeId);
            }
            $productData[] = $oneProduct;
        }
        $this->logRepository->addDebugLog(
            'GetProductData',
            'Response: ' . $this->jsonSerializer->serialize($productData)
        );
        $this->logRepository->addDebugLog('GetProductData', __('Finish'));
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
            'title' => $this->configRepository->getAttributeName($storeId),
            'description' => $this->configRepository->getAttributeDescription($storeId),
            'brand' => $this->configRepository->getAttributeBrand($storeId),
            'color' => $this->configRepository->getAttributeColor($storeId),
            'size' => $this->configRepository->getAttributeSize($storeId),
            'condition' => $this->configRepository->getAttributeCondition($storeId)
        ];
        $customFields = $this->getCustomFields($storeId);
        foreach ($customFields as $customField) {
            $this->attributes += [$customField['attribute'] => $customField['attribute']];
        }
    }

    /**
     * @param array $skus
     * @param int $storeId
     *
     * @return Collection
     */
    private function getProducts(array $skus = [], int $storeId = 0)
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addStoreFilter($storeId)
            ->addAttributeToSelect(array_values($this->attributes))
            ->addAttributeToSelect(['image', 'special_price', 'tax_class_id'])
            ->addAttributeToFilter('sku', ['in' => $skus])
            ->addUrlRewrite();

        $joinCond = join(
            ' AND ',
            ['inventory.product_id = e.' . $this->linkField, 'inventory.website_id = 0']
        );

        $collection->getSelect()->joinLeft(
            ['inventory' => $this->resource->getTableName('cataloginventory_stock_item')],
            $joinCond,
            ['qty', 'is_in_stock']
        );

        $tableName = ['price_index' => $this->resource->getTableName('catalog_product_index_price')];
        $joinCond = join(
            ' AND ',
            [
                'price_index.entity_id = e.' . $this->linkField,
                'price_index.website_id = ' . $this->getWebsiteId($storeId),
                'price_index.customer_group_id = 0'
            ]
        );
        $colls = ['price', 'final_price', 'min_price', 'max_price'];
        $collection->getSelect()->joinLeft($tableName, $joinCond, $colls);
        $collection = $this->getDefaultPrice($collection, $storeId);
        $this->entityIds = $collection->getColumnValues($this->linkField);
        return $collection;
    }

    /**
     * @param Collection $collection
     * @param int $storeId
     * @return Collection
     */
    private function getDefaultPrice(Collection $collection, int $storeId): Collection
    {
        $connection = $this->resource->getConnection();

        $selectPrice = $connection->select()
            ->from(
                $this->resource->getTableName('eav_attribute'),
                'attribute_id'
            )->where('attribute_code = ?', 'price');
        $attributeId = $connection->fetchOne($selectPrice);

        $tableName = ['price' => $this->resource->getTableName('catalog_product_entity_decimal')];
        $joinCond = join(
            ' AND ',
            [
                'price.' . $this->linkField . ' = e.' . $this->linkField,
                'price.attribute_id = ' . $attributeId,
                'price.store_id = ' . $storeId
            ]
        );
        $colls = [];
        $collection->getSelect()->joinLeft($tableName, $joinCond, $colls);
        $tableName = ['default_price' => $this->resource->getTableName('catalog_product_entity_decimal')];
        $joinCond = join(
            ' AND ',
            [
                'default_price.' . $this->linkField . ' = e.' . $this->linkField,
                'default_price.attribute_id = ' . $attributeId,
                'default_price.store_id = 0'
            ]
        );
        $colls = ['base_price' => 'COALESCE(price.value, default_price.value)'];
        $collection->getSelect()->joinLeft($tableName, $joinCond, $colls);
        return $collection;
    }

    /**
     * @param int $storeId
     * @return int
     */
    private function getWebsiteId(int $storeId)
    {
        try {
            return $this->storeRepository->getById($storeId)->getWebsiteId();
        } catch (\Exception $exception) {
            return 0;
        }
    }

    /**
     * @param Product $product
     * @return int
     */
    private function getParentId(Product $product)
    {
        $configurableParentId = $this->configurableResource->getParentIdsByChild($product->getId());
        if (isset($configurableParentId[0])) {
            return (int)$configurableParentId[0];
        }
        $bundleParentId = $this->bundleModel->getParentIdsByChild($product->getId());
        if (isset($bundleParentId[0])) {
            return (int)$bundleParentId[0];
        }
        $groupedParentId = $this->groupedModel->getParentIdsByChild($product->getId());
        if (isset($groupedParentId[0])) {
            return (int)$groupedParentId[0];
        }
        return 0;
    }

    /**
     * @param string $field
     * @param Product $product
     * @param int $parentId
     * @return array|bool|string|float
     */
    private function getAttributeValue(string $field, Product $product, int $parentId, int $storeId = 0)
    {
        switch ($field) {
            case 'entity_id':
                return $product->getEntityId();
            case 'link':
                if ($product->isVisibleInSiteVisibility()) {
                    return $product->getProductUrl();
                } elseif ($parentId) {
                    return $this->getProductUrlByProductId($parentId);
                }
                return $this->store->getBaseUrl() . 'catalog/product/view/id/' . $product->getId();
            case 'price':
                return $this->getPriceInclTax($product, $product->getPrice(), $storeId) ??
                    $this->getPriceInclTax($product, $product->getFinalPrice(), $storeId);
            case 'sale_price':
                return $this->getPriceInclTax($product, $product->getFinalPrice(), $storeId) ?? 0;
            case 'availability':
                return ($product->getData('is_in_stock') == 1) ? ('in stock') : ('out of stock');
            // no break
            case 'language':
                return $this->getLanguage();
            case 'currency':
                return $this->getCurrency();
            case 'image_link':
                return $this->getFullImageLink($product);
            case 'images':
                return $this->getMediaGallery($product, $storeId);
            case 'inventory':
                return $product->getQty();
            case 'parent_id':
                if ($parentId) {
                    $sku = $this->productResource
                        ->getAttributeRawValue($parentId, 'sku', $this->store->getId());
                    if (is_array($sku) && isset($sku['sku'])) {
                        return $sku['sku'];
                    } else {
                        return $sku;
                    }
                } else {
                    return '';
                }
            // no break
            case 'parent_url':
                if ($parentId) {
                    return $this->getProductUrlByProductId($parentId);
                } else {
                    return '';
                }
            // no break
            case 'category_ids':
                return $product->getCategoryIds();
            case 'is_in_stock':
            case 'is_salable':
                return ($product->getData('is_in_stock') == 1);
        }

        $attributeName = $this->attributes[$field] ?? null;
        if ($attributeName) {
            $connection = $this->resource->getConnection();
            $select = $connection->select()
                ->from(
                    $this->resource->getTableName('eav_attribute'),
                    'frontend_input'
                )->where('attribute_code = ?', $attributeName);
            $attributeType = $connection->fetchOne($select);

            if ($attributeType == 'select') {
                if (!$product->getResource()->getAttribute($attributeName)) {
                    return '';
                }
                $value = $product->getAttributeText($attributeName);
                /** @phpstan-ignore-next-line */
                if (is_object($value)) {
                    $value = $value->getText();
                }
            } else {
                $value = $product->getData($attributeName);
            }
            if ($field == 'condition' && !in_array($value, $this->conditions)) {
                $value = 'new';
            }
            return $value;
        }
        return '';
    }

    /**
     * Get Product Url by StoreID from url rewrite
     *
     * @param int $productId
     * @return string
     */
    private function getProductUrlByProductId(int $productId): string
    {
        $productFilter = [
            UrlRewrite::ENTITY_ID => $productId,
            UrlRewrite::ENTITY_TYPE => ProductUrlRewriteGenerator::ENTITY_TYPE,
            UrlRewrite::STORE_ID => $this->store->getId(),
            UrlRewrite::REDIRECT_TYPE => 0
        ];

        if ($rewrite = $this->urlFinder->findOneByData($productFilter)) {
            return $this->store->getBaseUrl() . $rewrite->getRequestPath();
        }

        return $this->store->getBaseUrl() . 'catalog/product/view/id/' . $productId;
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
     * Get full product image link
     *
     * @param Product $product
     *
     * @return string
     */
    public function getFullImageLink(Product $product)
    {
        $productImage = $product->getImage();
        if (!$productImage) {
            return '';
        }
        //check if image has .<ext> in the end
        if ((substr($productImage, -3, 1) == '.') || (substr($productImage, -4, 1) == '.')) {
            return $this->getImageUrl() . $productImage;
        }
        return '';
    }

    /**
     * @return string
     */
    private function getImageUrl(): string
    {
        if (!$this->imageUrl) {
            $this->imageUrl = $this->store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product';
            try {
                $mediaDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                if (strpos($mediaDir, '/pub/') !== false
                    && !$this->file->isDirectory($mediaDir)
                ) {
                    $this->imageUrl = str_replace('/pub/', '/', $this->imageUrl);
                }
            } catch (FileSystemException $exception) {
                $this->imageUrl = str_replace('/pub/', '/', $this->imageUrl);
            }
        }
        return $this->imageUrl;
    }

    /**
     * @param Product $product
     * @param int $storeId
     * @return array
     */
    private function getMediaGallery(Product $product, int $storeId): array
    {
        if (!empty($this->images)) {
            return $this->images[$product->getId()] ?? [];
        }

        $mediaGalleryTable = $this->resource->getTableName('catalog_product_entity_media_gallery');
        $mediaGalleryValueTable = $this->resource->getTableName('catalog_product_entity_media_gallery_value');
        $select = $this->resource->getConnection()
            ->select()->from(
                ['catalog_product_entity_media_gallery' => $mediaGalleryTable],
                'value'
            )->joinLeft(
                ['catalog_product_entity_media_gallery_value' => $mediaGalleryValueTable],
                'catalog_product_entity_media_gallery.value_id = catalog_product_entity_media_gallery_value.value_id',
                ['entity_id' => $this->linkField, 'store_id']
            )->where('catalog_product_entity_media_gallery_value.store_id IN (?)', [0, $storeId])
            ->where('catalog_product_entity_media_gallery_value.' . $this->linkField . ' IN (?)', $this->entityIds);

        $imagesData = $this->resource->getConnection()->fetchAll($select);
        foreach ($imagesData as $imageData) {
            $this->images[$imageData['entity_id']][]
                = $this->getImageUrl() . $imageData['value'];
        }

        return $this->getMediaGallery($product, $storeId);
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    private function getCustomFields(int $storeId): array
    {
        if (!$this->customFields) {
            $this->customFields = $this->jsonSerializer->unserialize(
                $this->configRepository->getExtraFields($storeId)
            );
        }
        return $this->customFields;
    }

    /**
     * Get price including tax
     *
     * @param Product $product
     * @param $price
     * @param int $storeId
     * @return float|null
     */
    private function getPriceInclTax(Product $product, $price, int $storeId): ?float
    {
        return $this->taxHelper->getTaxPrice(
            $product,
            $price,
            true,
            null,
            null,
            null,
            $storeId,
            null
        );
    }
}
