<?php

namespace Squeezely\Plugin\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Squeezely\Plugin\Helper\SqueezelyApiHelper;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\UrlInterface;
use \stdClass;


class ProductSaveAfter implements ObserverInterface
{
    private $_squeezelyHelperApi;

    protected $_storeManager;

    // TODO: REMOVE THIS LATER, USE ONLY FOR DEV
    protected $_messageManager;

    protected $_catalogProductTypeConfigurable;

    protected $_frontUrlModel;


    public function __construct(
        SqueezelyApiHelper $squeezelyHelperApi,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        Configurable $catalogProductTypeConfigurable,
        UrlInterface $frontUrlModel
    )
    {
        $this->_squeezelyHelperApi = $squeezelyHelperApi;
        $this->_messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_frontUrlModel = $frontUrlModel;
    }


    /**
     * @param Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $_product = $observer->getProduct();
        $this->_squeezelyHelperApi->sendProducts($this->transformProductData($_product));
    }


    /**
     * @param $_product
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function transformProductData($_product)
    {
        $productsData = array();

        $categoriesIds = array_map(
            function($value) { return (int)$value; },
            $_product->getCategoryIds()
        );

        $productImageUrls = $this->getAllProductImageUrls($_product);

        $_customOptions = $_product->getOptions();

        if(!empty($_customOptions)){
            $productsData = $this->getCustomizableProducts($_customOptions, $_product, $productImageUrls, $categoriesIds);
        } else {
            // each product variant is a product itself, so the Save After event (observer) is called separately
            array_push($productsData, $this->addProduct($_product, $productImageUrls, $categoriesIds));
        }

        // Echo message for testing
//        $this->_messageManager->addError($message); // For Error Message

        return ['products' => $productsData];
    }

    /**
     * // Get all product customizable options, each customization has its own sku
     *
     * @param $customOptions
     * @param $product
     * @param $productImageUrls
     * @param $categoriesIds
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomizableProducts($customOptions, $product, $productImageUrls, $categoriesIds)
    {
        $productsData = array();

        foreach ($customOptions as $option) {
            foreach ($option['values'] as $childData) {
                $formattedProduct = new stdClass();
                $formattedProduct->id = $childData['sku'];
                $formattedProduct->title = $product->getName() . " - " . $childData['title'];
                $formattedProduct->link = $this->getProductUrl($product);
                $formattedProduct->description = $product->getDescription();
                $formattedProduct->language = $this->_storeManager->getStore()->getLocaleCode(); //returns null
                $formattedProduct->price = $childData['price'];
                $formattedProduct->currency = $this->_storeManager->getStore()->getCurrentCurrencyCode();

                if(!empty($productImageUrls)) {
                    $formattedProduct->image_links = $productImageUrls;
                }

                $formattedProduct->availability = ($product->isAvailable() ? 'in stock' : 'out of stock') ;
                $formattedProduct->inventory = 1;
                $formattedProduct->parent_id = $product->getId();
                $formattedProduct->category_ids = $categoriesIds;
                array_push($productsData ,$formattedProduct);
            }
        }
        return $productsData;
    }

    /**
     * Add product to Squeezely Api
     *
     * @param $product
     * @param $productImageUrls
     * @param $categoriesIds
     *
     * @return stdClass
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addProduct($product, $productImageUrls, $categoriesIds)
    {
        $formattedProduct = new stdClass();
        $parentByChild = $this->_catalogProductTypeConfigurable->getParentIdsByChild($product->getId());

        $formattedProduct->id = $product->getSku();
        $formattedProduct->title = $product->getName();
        $formattedProduct->link = $this->getProductUrl($product);
        $formattedProduct->description = $product->getDescription();
//        $formattedProduct->language = $this->_storeManager->getStore()->getLocaleCode(); //returns null
        $formattedProduct->price = $product->getPrice();
        $formattedProduct->currency = $this->_storeManager->getStore()->getCurrentCurrencyCode();

        if(!empty($productImageUrls)) {
            $formattedProduct->image_links = $productImageUrls;
        }

        $formattedProduct->availability = ($product->isAvailable() ? 'in stock' : 'out of stock');
        $formattedProduct->inventory = $product->getExtensionAttributes()->getStockItem()->getQty();

        if(isset($parentByChild[0])) {
            $formattedProduct->parent_id  = $parentByChild[0];
        }

        $formattedProduct->category_ids = $categoriesIds;
        return $formattedProduct;
    }

    /**
     * @param        $product
     * @param string $storeCode
     *
     * @return string
     */
    private function getProductUrl($product, $storeCode = 'default')
    {
        $routeParams = [ '_nosid' => true, '_query' => ['___store' => $storeCode]];
        $routeParams['id'] = $product->getId();
        $routeParams['s'] = $product->getUrlKey();
        return $this->_frontUrlModel->getUrl('catalog/product/view', $routeParams);
    }

    /**
     * @param $product
     *
     * @return array
     */
    private function getAllProductImageUrls($product)
    {
        $productImageUrls = array();
        foreach ($product->getMediaGalleryImages() as $image)
        {
            array_push($productImageUrls, $image->getUrl());
        }
        return $productImageUrls;
    }


}