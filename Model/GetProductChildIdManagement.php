<?php
namespace Squeezely\Plugin\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use \Magento\Store\Model\StoreManagerInterface;

class GetProductChildIdManagement
{

    protected $_catalogProductTypeConfigurable;
    protected $_productRepository;
    /**
     * @var LoggerInterface
     */
    private $_logger;
    /**
     * @var StoreManager
     */
    private $_storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    public function __construct(
        Configurable $catalogProductTypeConfigurable,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_productRepository = $productRepository;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
    }

    public function getParentIdOfProduct($productId)
    {
        return  $this->_catalogProductTypeConfigurable->getParentIdsByChild($productId);
    }

    /**
     * @param string $productIds
     * @param int $storeId
     */
    public function getProductsInfo($productIds, $storeId)
    {
        $ids = json_decode($productIds, true);
        $products = [];
        $stores = $this->_storeManager->getStores();

        $defaultStore = array_shift($stores);
        $defaultStoreId = $defaultStore->getId();

        $storeLocale = $this->_scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $defaultStoreId);
        $storeLocale = str_replace('_', '-', $storeLocale);

        foreach($ids as $id) {
            $product = null;
            try {
                $storeLocale = $this->_scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId);
            }
            catch(\Exception $exception) {
                ;
            }

            try {
                /** @var ProductInterface $product */
                $product = $this->_productRepository->get($id, false, $storeId);
            } catch (\Exception $e) {
                //nothing
            }
            if($product) {
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
                $productParentIds = $this->_catalogProductTypeConfigurable->getParentIdsByChild($product->getId());

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
                $productData['url'] = $product->getProductUrl();

                if(!$product->getVisibleInSiteIds() && $productParentIds) {
                    // We only support one parent product per child
                    $parentProduct = $this->_productRepository->getById($productParentIds[0], false, $storeId);
                    if($parentProduct) {
                        $productData['url'] = $parentProduct->getProductUrl();
                    }
                }

                $productData['parent_id'] = $productParentIds;
                $productData['images'] = $productImageUrls;
                $productData['image'] = $productImage;
                $productData['price'] = $regularPrice ?: $product->getPrice();
                $productData['sale_price'] = $specialPrice;
                $productData['availability'] = ($product->isAvailable() ? 'in stock' : 'out of stock');
                $productData['inventory'] = $stockItem ? $stockItem->getQty() : 1;
                $productData['sku'] = $id;
                $productData['locale'] = $storeLocale;

                $products[$id] = $productData;
            }
        }

        return $products;
    }
}