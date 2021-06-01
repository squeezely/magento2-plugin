<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\Invalidate;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as ConfigRepository;

/**
 * Products invalidator by store id
 */
class ByStore
{

    const SUCCESS_MESSAGE = 'Store %s, invalidated products: %s';
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var string
     */
    private $linkField;

    /**
     * InvalidateAll constructor.
     *
     * @param ConfigRepository $configRepository
     * @param ResourceConnection $resource
     * @param MetadataPool $metadataPool
     * @throws \Exception
     */
    public function __construct(
        ConfigRepository $configRepository,
        ResourceConnection $resource,
        MetadataPool $metadataPool
    ) {
        $this->configRepository = $configRepository;
        $this->resource = $resource;
        $this->linkField = $metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function execute(int $storeId): array
    {
        $count = 0;
        $connection = $this->resource->getConnection();
        $connection->beginTransaction();
        $selectProductSku = $connection->select()
            ->from(
                $this->resource->getTableName('squeezely_items_queue'),
                'product_sku'
            )->where('store_id = ?', $storeId);
        $entityIdsByWebsite = $this->filterWebsite($storeId);
        $items = $connection->select()
            ->from(
                ['m_product' => $this->resource->getTableName('catalog_product_entity')],
                ['entity_id' => $this->linkField, 'sku']
            )->joinLeft(
                ['relations' => $this->resource->getTableName('catalog_product_relation')],
                "m_product.{$this->linkField} = relations.child_id",
                'parent_id'
            )->where('m_product.sku not in (?)', $selectProductSku)
            ->where('m_product.entity_id in (?)', $entityIdsByWebsite);
        $items = $connection->fetchAll($items);
        foreach ($items as $item) {
            $connection->insert(
                $this->resource->getTableName('squeezely_items_queue'),
                [
                    'product_sku' => $item['sku'],
                    'store_id' => $storeId,
                    'product_id' => $item['entity_id'],
                    'parent_id' => $item['parent_id']
                ]
            );
            $count++;
        }
        try {
            $connection->commit();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }
        return [
            'success' => true,
            'msg' =>  sprintf(self::SUCCESS_MESSAGE, $storeId, $count)
        ];
    }

    /**
     * Filter entity ids to exclude products by website
     *
     * @param int $storeId
     * @return array
     */
    private function filterWebsite(int $storeId): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            ['store' => $this->resource->getTableName('store')],
            []
        )->joinLeft(
            ['catalog_product_website' => $this->resource->getTableName('catalog_product_website')],
            'catalog_product_website.website_id = store.website_id',
            ['product_id']
        )->where('store.store_id = ?', $storeId);
        return $connection->fetchCol($select, 'catalog_product_website.product_id');
    }
}
