<?php
namespace Squeezely\Plugin\Block;

use Magento\Catalog\Model\Product;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Squeezely\Plugin\Helper\SqueezelyDataLayerHelper;
use \stdClass;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Backend\Block\Template\Context;
use Squeezely\Plugin\Helper\Data;
use Squeezely\Plugin\Helper\SqueezelyApiHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Directory\Model\Currency;

class SqueezelyPixelManager extends Template
{
    /**
     * Squeezely Pixel Manager Helper
     *
     * @var Data
     */
    protected $_sqzlyHelper;

    /**
     * Http Request
     *
     * @var Http
     */
    protected $_request;

    /**
     * Registry
     *
     * @var Registry
     */
    protected $_registry;

    /**
     * Order
     *
     * @var OrderInterface
     */
    protected $_order;

    /**
     * Checkout Session
     *
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * Category Repository
     *
     * @var CategoryRepository
     */
    protected $_categoryRepository;

    /**
     * Currency
     *
     * @var Currency
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
     * @var SqueezelyApiHelper
     */
    private $_helperApi;
    /**
     * @var StoreInterface
     */
    private $_store;

    /** @var int */
    private $_storeId;

    /** @var string */
    private $_storeLocale;

    /**
     * @var string|null
     */
    private $_storeCurrency;
    /**
     * @var SqueezelyDataLayerHelper
     */
    private $_squeezelyDataLayerHelper;

    public function __construct(
        Context $context,
        Data $sqzlyHelper,
        SqueezelyApiHelper $_helperApi,
        Http $request,
        Registry $registry,
        OrderInterface $order,
        Session $checkoutSession,
        CategoryRepository $categoryRepository,
        Currency $currency,
        array $data = [],
        Resolver $localStore,
        SqueezelyDataLayerHelper $squeezelyDataLayerHelper
    ) {
        $this->_sqzlyHelper = $sqzlyHelper;
        $this->_helperApi = $_helperApi;
        $this->_request = $request;
        $this->_registry = $registry;
        $this->_order = $order;
        $this->_checkoutSession = $checkoutSession;
        $this->_categoryRepository = $categoryRepository;
        $this->_currency = $currency;
        parent::__construct($context, $data);

        $this->_store = $this->_storeManager->getStore();
        $this->_storeId = $this->_store->getId();
        $this->_storeCurrency = $this->_store->getCurrentCurrencyCode();
        $this->_storeLocale = $localStore->getLocale() ?: $localStore->getDefaultLocale();
        $this->_storeLocale = str_replace('_', '-', $this->_storeLocale);
        $this->_squeezelyDataLayerHelper = $squeezelyDataLayerHelper;
    }

    // HELPER FUNCTIONS

    /**
     * Check if the module is enabled
     *
     * @return boolean 0 or 1
     */
    public function getIsEnabled() {
        return $this->_sqzlyHelper->getIsEnabled();
    }

    /**
     * Get container id
     *
     * @return string
     */
    public function getSQZLYId() {
        return $this->_sqzlyHelper->getSQZLYId();
    }

    // END HELPER FUNCTIONS

    /**
     * Check if current page is order success page
     *
     * @TODO Find a better way then getPathInfo()
     * @return boolean	true or false
     */
    public function getIsOrderSuccessPage() {
        if (
            strpos($this->_request->getPathInfo(), '/checkout/onepage/success') !== false
            || strpos($this->_request->getPathInfo(), '/checkout/success') !== false
        ) {
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
        // https://magento.stackexchange.com/questions/265001/how-to-get-current-product-in-phtml-without-registry?atw=1#answer-265004 TODO: Refactor Registry (current product) after Magento has an alternative
        return $this->_registry->registry('current_product');
    }

    /**
     * Get order
     *
     * @return Order|false
     */
    public function getOrder() {
        if ($this->getIsOrderSuccessPage()) {
            $order = $this->_checkoutSession->getLastRealOrder();
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
        /** @var Product $product */
        if ($product = $this->getCurrentProduct()) {
            $categoryCollection = $product->getCategoryCollection();

            $categories = [];
            foreach ($categoryCollection as $category) {
                $categories[] = $this->_categoryRepository->get($category->getEntityId())->getId();
            }

            $objProduct = new stdClass();
            $objProduct->name = $product->getName();
            $objProduct->id = $product->getSku();
            $objProduct->price =  $product->getFinalPrice();
            $objProduct->language = $this->_storeLocale;
            $objProduct->category_ids = $categories;

            $objEcommerce = new stdClass();
            $objEcommerce->event = 'ViewContent';
            $objEcommerce->products = $objProduct;
            $objEcommerce->currency = $this->_storeCurrency;

            $dataScript = PHP_EOL;

            $dataScript .= '<script type="text/javascript">'.PHP_EOL.'window._sqzl = _sqzl || []; _sqzl.push('. json_encode($objEcommerce, JSON_PRETTY_PRINT) . ')'.PHP_EOL.'</script>';

            return $dataScript;
        }

        return '';
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDataLayerOrder() { // Purchase Squeezely
        if ($order = $this->getOrder()) {

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
                $productItem['price'] = $item->getPrice();
                $productItem['quantity'] = intval($item->getQtyOrdered()); // converting qty from decimal to integer
                $productItems[] = (object) $productItem;
            }

            $objOrder = new stdClass();

            $objOrder->event = 'Purchase';

            $objOrder->email = $order->getCustomerEmail();
            $objOrder->orderid = $order->getIncrementId();
            $objOrder->firstname = $order->getCustomerFirstname();
            $objOrder->lastname = $order->getCustomerLastname();
            $objOrder->userid = $order->getCustomerId();
            $objOrder->service = 'enabled';
            $objOrder->products = $productItems;
            $objOrder->currency = $this->_storeCurrency;

            $dataScript = PHP_EOL;

            $dataScript .= '<script type="text/javascript">'.PHP_EOL.'window._sqzl = _sqzl || []; _sqzl.push('. json_encode($objOrder, JSON_PRETTY_PRINT) . ')'.PHP_EOL.'</script>';

            return $dataScript;
        }

        return '';
    }

    /**
     * @return string|null
     */
    public function getCurrentCategory(){
        try{
            $categoryId = (int) $this->getRequest()->getParam('id', false);
            $this->currentCategory = $categoryId;
            $category = $this->_categoryRepository->get($categoryId, $this->_storeId);
        } catch (\Exception $e) {
            return null;
        }

        $objViewCategory = new stdClass();
        $objViewCategory->event = 'ViewCategory';
        $objViewCategory->category_id = $categoryId;
        $objViewCategory->objectname = $category->getName();

        $dataScript = PHP_EOL;

        $dataScript .= '<script type="text/javascript">'.PHP_EOL.'window._sqzl = _sqzl || []; _sqzl.push('. json_encode($objViewCategory, JSON_PRETTY_PRINT) . ')'.PHP_EOL.'</script>';

        return $dataScript;
    }

    public function fireQueuedEvents() {
        return $this->_squeezelyDataLayerHelper->fireQueuedEvents();
    }
}
