<?php
namespace Squeezely\Plugin\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use \Magento\Framework\Controller\Result\JsonFactory;

class GetProductChildIdManagement
{

    protected $_catalogProductTypeConfigurable;
    protected $_productRepository;

    public function __construct(
        Configurable $catalogProductTypeConfigurable,
        ProductRepositoryInterface $productRepository
    ) {
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentIdOfProduct($productId)
    {
        $productChildId =  $this->_catalogProductTypeConfigurable->getParentIdsByChild($productId);
        return $productChildId;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductsInfo($productIds)
    {
        $ids = json_decode($productIds, true);
        $products = [];
        foreach($ids as $id) {
            $product = false;
            try {
                $product = $this->_productRepository->get($id);
            } catch (\Exception $e) {
                //nothing
            }
            if($product){
                $productImageUrls = [];
                $galleryImages = $product->getMediaGalleryImages();
                $productImage = false;
                foreach ($galleryImages as $image) {
                    $data = $image->getData();
                    if($data['file'] == $product->getData('image')) {
                        $productImage = $image->getUrl();
                    }
                    else{
                        $productImageUrls[] = $image->getUrl();
                    }
                }

                $stockItem = $product->getExtensionAttributes()->getStockItem();
                $productChildId =  $this->_catalogProductTypeConfigurable->getParentIdsByChild($id);

                //get price
                $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
                $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();
                if ($product->getTypeId() == 'configurable') {
                    $basePrice = $product->getPriceInfo()->getPrice('regular_price');
                    $regularPrice = $basePrice->getMinRegularAmount()->getValue();
                    $specialPrice = $product->getFinalPrice();
                }
                else if ($product->getTypeId() == 'bundle') {
                    $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
                    $specialPrice = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
                }
                elseif ($product->getTypeId() == 'grouped') {
                    $usedProds = $product->getTypeInstance(true)->getAssociatedProducts($product);
                    foreach ($usedProds as $child) {
                        if ($child->getId() != $product->getId()) {
                            $regularPrice += $child->getPrice();
                            $specialPrice += $child->getFinalPrice();
                        }
                    }
                }


                $productData = [];
                $productData['parent_id'] = $productChildId;
                $productData['images'] = $productImageUrls;
                $productData['image'] = $productImage;
                $productData['price'] = $regularPrice ?: $product->getPrice();
                $productData['sale_price'] = $specialPrice;
                $productData['availability'] = ($product->isAvailable() ? 'in stock' : 'out of stock');
                $productData['inventory'] = $stockItem ? $stockItem->getQty() : 1;
                $productData['sku'] = $id;
                $products[$id] = $productData;
            }
        }

        return $products;
    }
}