<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Webapi;

use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use Squeezely\Plugin\Api\Webapi\ManagementInterface;
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
     * @var ModuleListInterface
     */
    private $moduleList;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var ProductDataService
     */
    private $productDataService;

    /**
     * Repository constructor.
     *
     * @param Configurable $catalogProductTypeConfigurable
     * @param ModuleListInterface $moduleList
     * @param JsonSerializer $jsonSerializer
     * @param ProductDataService $productDataService
     */
    public function __construct(
        Configurable $catalogProductTypeConfigurable,
        ModuleListInterface $moduleList,
        JsonSerializer $jsonSerializer,
        ProductDataService $productDataService
    ) {
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->moduleList = $moduleList;
        $this->jsonSerializer = $jsonSerializer;
        $this->productDataService = $productDataService;
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
    public function getModuleInfo()
    {
        return [
            $this->moduleList->getOne(
                ConfigRepositoryInterface::SQUEEZELY_PLUGIN_NAME
            )
        ];
    }
}
