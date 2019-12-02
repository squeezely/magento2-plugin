<?php

namespace Squeezely\Plugin\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \stdClass;


class OrderSaveAfter implements ObserverInterface
{

    private $_squeezelyHelperApi;

    protected $_subscriber;

    public function __construct(
        \Squeezely\Plugin\Helper\SqueezelyApiHelper $squeezelyHelperApi,
        \Magento\Newsletter\Model\Subscriber $subscriber
    )
    {
        $this->_squeezelyHelperApi = $squeezelyHelperApi;
        $this->_subscriber= $subscriber;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->_squeezelyHelperApi->sendPurchases($this->transformOrderData($order));
    }

    /**
     * @param $order
     *
     * @return stdClass
     */
    private function transformOrderData($order)
    {
        $formattedProduct = new stdClass();

        $formattedProduct->event = "Purchase";
        $formattedProduct->email = $order->getCustomerEmail();
        $formattedProduct->firstname = $order->getCustomerFirstname();
        $formattedProduct->lastname = $order->getCustomerLastname();
        $formattedProduct->orderid = $order->getRealOrderId();
        $formattedProduct->timestamp = $order->getCreatedAt();

        if($order->getCustomerIsGuest()) {
            $formattedProduct->userid =  $order->getCustomerFirstname() . " " . $order->getCustomerLastname();
        } else {
            $formattedProduct->userid = $order->getCustomerId();
        }

        $formattedProduct->gender = $order->getCustomerGender();
        $formattedProduct->birthdate = $order->getCustomerDob();
        $formattedProduct->phone = $order->getShippingAddress()->getTelephone();
        $formattedProduct->postcode = $order->getShippingAddress()->getPostcode();
        $formattedProduct->city = $order->getShippingAddress()->getCity();
        $formattedProduct->country = $order->getShippingAddress()->getCountryId();
        $formattedProduct->currency = $order->getOrderCurrencyCode();

        $checkSubscriber = $this->_subscriber->loadByEmail($order->getCustomerEmail());
        $formattedProduct->newsletter = $checkSubscriber->isSubscribed() ? 'yes' : 'no';

        $formattedProduct->products = new stdClass();
        $formattedProduct->products = $this->retrieveProductsFromOrder($order->getAllItems());

        return $formattedProduct;
    }

    /**
     * @param array $products
     *
     * @return array
     */
    private function retrieveProductsFromOrder(Array $products)
    {
        $formattedProducts = array();
        foreach($products as $product){
            if($product->getData('has_children') !== true) {
                continue;
            }

            $productFormat = new stdClass();
            $productFormat->id = $product->getSku();
            $productFormat->name = $product->getName();
            $productFormat->price = $product->getPrice();
            $productFormat->quantity = $product->getQtyOrdered();

            array_push($formattedProducts, $productFormat);
        }
        return $formattedProducts;
    }

}