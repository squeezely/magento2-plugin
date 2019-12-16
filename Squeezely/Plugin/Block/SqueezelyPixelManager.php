<?php
namespace Squeezely\Plugin\Block;

use Braintree\Exception;
use \stdClass;
use Magento\Catalog\Api\Data\CategoryInterface;

// TODO: Product view doesn't work anymore, fix bug
// TODO: Fix deprecated classes
class SqueezelyPixelManager extends \Magento\Framework\View\Element\Template
{
    /**
     * Squeezely Pixel Manager Helper
     *
     * @var \Squeezely\Plugin\Helper\Data
     */
    protected $_helper;

    /**
     * Header Logo
     *
     * @var \Magento\Theme\Block\Html\Header\Logo
     */
    protected $_logo;

    /**
     * Http Request
     *
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;

    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * Order
     *
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    protected $_order;

    /**
     * Checkout Session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Category Repository
     *
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $_categoryRepository;

    /**
     * Currency
     *
     * @var \Magento\Directory\Model\Currency
     */
    protected $_currency;

    /**
     * Current Category
     *
     * @var CategoryInterface
     */
    private $currentCategory;

    /**
     * Current Category
     *
     * @var \Squeezely\Plugin\Helper\SqueezelyApiHelper
     */
    private $_helperApi;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Squeezely\Plugin\Helper\Data $helper,
        \Squeezely\Plugin\Helper\SqueezelyApiHelper $_helperApi,
        \Magento\Theme\Block\Html\Header\Logo $logo,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Directory\Model\Currency $currency,
        array $data = []
    )
    {
        $this->_helper = $helper;
        $this->_helperApi = $_helperApi;
        $this->_logo = $logo;
        $this->_request = $request;
        $this->_registry = $registry;
        $this->_order = $order;
        $this->_checkoutSession = $checkoutSession;
        $this->_categoryRepository = $categoryRepository;
        $this->_currency = $currency;
        parent::__construct($context, $data);
    }

    // HELPER FUNCTIONS

    /**
     * Check if the module is enabled
     *
     * @return boolean 0 or 1
     */
    public function getIsEnable() {
        return $this->_helper->getIsEnable();
    }

    /**
     * Check if data layered is enabled or not
     *
     * @return boolean 0 or 1
     */
    public function getIsEnableDataLayer() {
        return $this->_helper->getIsEnableDataLayer();
    }

    /**
     * Get container id
     *
     * @return string
     */
    public function getSQZLYId() {
        return $this->_helper->getSQZLYId();
    }

    // END HELPER FUNCTIONS


    /**
     * Check if current page is homepage
     *
     * @return boolean	true or false
     */
    public function getIsHomePage() {
        return $this->_logo->isHomePage();
    }

    /**
     * Check if current page is order success page
     *
     * @return boolean	true or false
     */
    public function getIsOrderSuccessPage() {
        if (strpos($this->_request->getPathInfo(), '/checkout/onepage/success') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Check if current page is shopping cart page
     *
     * @return boolean	true or false
     */
    public function getIsCartPage() {
        if (strpos($this->_request->getPathInfo(), '/checkout/cart') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Check if current page is checkout page
     *
     * @return boolean	true or false
     */
    public function getIsCheckoutPage() {
        if (strpos($this->_request->getPathInfo(), '/checkout/onepage') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Get current product
     *
     * @return \Magento\Catalog\Model\Product\Interceptor
     */
    public function getCurrentProduct() {
        return $this->_registry->registry('current_product');
    }

    /**
     * Get order
     *
     * @return mixed	\Magento\Sales\Model\Order or false
     */
    public function getOrder() {
        if ($this->getIsOrderSuccessPage()) {
            $orderId = $this->_checkoutSession->getLastOrderId();
            $order = $this->_order->load($orderId);
            if (!$order) {
                return false;
            }
            return $order;
        }
        return false;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDataLayerProduct() { // Get current product (view)
        if ($product = $this->getCurrentProduct()) {

            $categoryCollection = $product->getCategoryCollection();

            $categories = array();
            foreach ($categoryCollection as $category) {
                $categories[] = $this->_categoryRepository->get($category->getEntityId())->getName();
            }

            $objProduct = new stdClass();
            $objProduct->name = $product->getName();
            $objProduct->id = $product->getSku();
            $objProduct->price =  $product->getFinalPrice();
            $objProduct->category = implode('|', $categories);


            $objEcommerce = new stdClass();
            $objEcommerce->event = 'ViewContent';
            $objEcommerce->products = $objProduct;

            $objAddToCart = new stdClass();
            $objAddToCart->event = 'addToCart';
            $objAddToCart->ecommerce = new stdClass();
            $objAddToCart->ecommerce->add = new stdClass();
            $objAddToCart->ecommerce->add->products = $objProduct;

            $pageCategory = json_encode(array('pageCategory' => 'product-detail'), JSON_PRETTY_PRINT);

            $dataScript = PHP_EOL;

            $dataScript .= '<script type="text/javascript">'.PHP_EOL.'dataLayer = [' . $pageCategory . '];'.PHP_EOL.'</script>';

            $dataScript .= PHP_EOL.PHP_EOL;

            $dataScript .= '<script type="text/javascript">'.PHP_EOL.'_sqzl.push('. json_encode($objEcommerce, JSON_PRETTY_PRINT) . ');'.PHP_EOL.'</script>';


            return $dataScript;
        }
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDataLayerOrder() { // Purchase Squeezely
        if ($order = $this->getOrder()) {

            $aItems = array();
            $productItems = array();
            foreach ($order->getAllVisibleItems() as $item) {

                $categoryCollection = $item->getProduct()->getCategoryCollection();
                $categories = array();
                foreach ($categoryCollection as $category) {
                    $categories[] = $this->_categoryRepository->get($category->getEntityId())->getName();
                }

                $productItem = array();
                $productItem['id'] = $item->getSku();
                $productItem['name'] = $item->getName();
                $productItem['price'] = $this->_currency->formatTxt($item->getBasePrice(), array('display' => \Magento\Framework\Currency::NO_SYMBOL));
                $productItem['category'] = implode('|', $categories);
                $productItem['quantity'] = intval($item->getQtyOrdered()); // converting qty from decimal to integer
                $productItem['coupon'] = '';
                $productItems[] = (object) $productItem;

                $objItem = array();
                $objItem['sku'] = $item->getSku();
                $objItem['name'] = $item->getName();
                $objItem['category'] = implode('|', $categories);
                $objItem['price'] = $this->_currency->formatTxt($item->getBasePrice(), array('display' => \Magento\Framework\Currency::NO_SYMBOL));
                $objItem['quantity'] = intval($item->getQtyOrdered()); // converting qty from decimal to integer
                $aItems[] = (object) $objItem;
            }

            $objOrder = new stdClass();

            $objOrder->event = 'Purchase';

            $objOrder->email = $order->getCustomerEmail();
            $objOrder->orderid = $order->getIncrementId();
            $objOrder->firstname = $order->getCustomerFirstname();
            $objOrder->lastname = $order->getCustomerLastname();
            $objOrder->userid = $order->getCustomerId();

            $objOrder->transactionAffiliation = $this->_storeManager->getStore()->getFrontendName();
            $objOrder->transactionTotal = $this->_currency->formatTxt($order->getBaseGrandTotal(), array('display' => \Magento\Framework\Currency::NO_SYMBOL));
            $objOrder->transactionTax = $this->_currency->formatTxt($order->getBaseTaxAmount(), array('display' => \Magento\Framework\Currency::NO_SYMBOL));
            $objOrder->transactionShipping = $this->_currency->formatTxt($order->getBaseShippingAmount(), array('display' => \Magento\Framework\Currency::NO_SYMBOL));

            $objOrder->transactionProducts = $aItems;

            $objOrder->ecommerce = new stdClass();
            $objOrder->ecommerce->purchase = new stdClass();
            $objOrder->ecommerce->purchase->actionField = new stdClass();
            $objOrder->ecommerce->purchase->actionField->id = $order->getIncrementId();
            $objOrder->ecommerce->purchase->actionField->affiliation = $this->_storeManager->getStore()->getFrontendName();
            $objOrder->ecommerce->purchase->actionField->revenue = $this->_currency->formatTxt($order->getBaseGrandTotal(), array('display' => \Magento\Framework\Currency::NO_SYMBOL));
            $objOrder->ecommerce->purchase->actionField->tax = $this->_currency->formatTxt($order->getBaseTaxAmount(), array('display' => \Magento\Framework\Currency::NO_SYMBOL));
            $objOrder->ecommerce->purchase->actionField->shipping = $this->_currency->formatTxt($order->getBaseShippingAmount(), array('display' => \Magento\Framework\Currency::NO_SYMBOL));
            $coupon = $order->getCouponCode();
            $objOrder->ecommerce->purchase->actionField->coupon = $coupon == null ? '' : $coupon;

            $objOrder->ecommerce->products = $productItems;

            $pageCategory = json_encode(array('pageCategory' => 'order-success'), JSON_PRETTY_PRINT);

            $dataScript = PHP_EOL;

            $dataScript .= '<script type="text/javascript">'.PHP_EOL.'dataLayer = [' . $pageCategory . '];'.PHP_EOL.'</script>';

            $dataScript .= PHP_EOL.PHP_EOL;

            $dataScript .= '<script type="text/javascript">'.PHP_EOL.'_sqzl.push('. json_encode($objOrder, JSON_PRETTY_PRINT) . ');'.PHP_EOL.'</script>';

            return $dataScript;
        }
    }

    /**
     * @return string|null
     */
    public function getCurrentCategory(){
        try{
            $categoryId = (int) $this->getRequest()->getParam('id', false);
            $this->currentCategory = $categoryId;
            $category = $this->_categoryRepository->get($categoryId, $this->_storeManager->getStore()->getId());
        } catch (\Exception $e) {
            return null;
        }

        $objViewCategory = new stdClass();
        $objViewCategory->event = 'ViewCategory';
        $objViewCategory->category_id = $categoryId;
        $objViewCategory->objectname = $category->getName();

        $dataScript = PHP_EOL;

        $dataScript .= '<script type="text/javascript">'.PHP_EOL.'_sqzl.push('. json_encode($objViewCategory, JSON_PRETTY_PRINT) . ');'.PHP_EOL.'</script>';

        return $dataScript;
    }
}
