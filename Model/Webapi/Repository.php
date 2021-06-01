<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Webapi;

use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use Squeezely\Plugin\Api\Webapi\ManagementInterface;
use Squeezely\Plugin\Service\Product\GetData as ProductDataService;
use Squeezely\Plugin\Service\Invalidate\ByStore as InvalidateByStore;
use Squeezely\Plugin\Service\Invalidate\ByProductId as InvalidateByProductId;

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
     * @var InvalidateByStore
     */
    private $invalidateByStore;
    /**
     * @var InvalidateByProductId
     */
    private $invalidateByProductId;

    /**
     * Repository constructor.
     *
     * @param Configurable $catalogProductTypeConfigurable
     * @param JsonSerializer $jsonSerializer
     * @param ProductDataService $productDataService
     */
    public function __construct(
        Configurable $catalogProductTypeConfigurable,
        JsonSerializer $jsonSerializer,
        InvalidateByStore $invalidateByStore,
        InvalidateByProductId $invalidateByProductId,
        ProductDataService $productDataService,
        ConfigRepositoryInterface $configRepository
    ) {
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->jsonSerializer = $jsonSerializer;
        $this->invalidateByStore = $invalidateByStore;
        $this->invalidateByProductId = $invalidateByProductId;
        $this->productDataService = $productDataService;
        $this->configRepository = $configRepository;
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
