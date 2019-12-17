<?php
namespace Squeezely\Plugin\Block;

use Braintree\Exception;
use Magento\Framework\View\Element\Template;
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
        array $data = []
    )
    {
        $this->_sqzlyHelper = $sqzlyHelper;
        $this->_helperApi = $_helperApi;
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
        return $this->_sqzlyHelper->getIsEnable();
    }

    /**
     * Check if data layered is enabled or not
     *
     * @return boolean 0 or 1
     */
    public function getIsEnableDataLayer() {
        return $this->_sqzlyHelper->getIsEnableDataLayer();
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
        // https://magento.stackexchange.com/questions/265001/how-to-get-current-product-in-phtml-without-registry?atw=1#answer-265004 TODO: Refactor Registry (current product) after Magento has an alternative
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


            $objEcommerce = new stdClass();
            $objEcommerce->event = 'ViewContent';
            $objEcommerce->products = $objProduct;

            $pageCategory = json_encode(array('pageCategory' => 'product-detail'), JSON_PRETTY_PRINT);

            $dataScript = PHP_EOL;

            $dataScript .= '<script type="text/javascript">'.PHP_EOL.'dataLayer = [' . $pageCategory . ']'.PHP_EOL.'</script>';

            $dataScript .= PHP_EOL.PHP_EOL;

            $dataScript .= '<script type="text/javascript">'.PHP_EOL.'_sqzl.push('. json_encode($objEcommerce, JSON_PRETTY_PRINT) . ')'.PHP_EOL.'</script>';


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
            $objOrder->products = $productItems;

            $pageCategory = json_encode(array('pageCategory' => 'order-success'), JSON_PRETTY_PRINT);

            $dataScript = PHP_EOL;

            $dataScript .= '<script type="text/javascript">'.PHP_EOL.'dataLayer = [' . $pageCategory . ']'.PHP_EOL.'</script>';

            $dataScript .= PHP_EOL.PHP_EOL;

            $dataScript .= '<script type="text/javascript">'.PHP_EOL.'_sqzl.push('. json_encode($objOrder, JSON_PRETTY_PRINT) . ')'.PHP_EOL.'</script>';

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

        $dataScript .= '<script type="text/javascript">'.PHP_EOL.'_sqzl.push('. json_encode($objViewCategory, JSON_PRETTY_PRINT) . ')'.PHP_EOL.'</script>';

        return $dataScript;
    }
}
