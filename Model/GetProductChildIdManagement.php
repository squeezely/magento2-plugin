<?php
namespace Squeezely\Plugin\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use \Magento\Framework\Controller\Result\JsonFactory;

class GetProductChildIdManagement
{

    protected $_catalogProductTypeConfigurable;

    public function __construct(
        Configurable $catalogProductTypeConfigurable
    ) {
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentIdOfProduct($productId)
    {
        $productChildId =  $this->_catalogProductTypeConfigurable->getParentIdsByChild($productId);
        return $productChildId;
    }
}