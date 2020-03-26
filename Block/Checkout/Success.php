<?php
namespace SR\Stackexchange\Block\Checkout;


class Success extends \Magento\Checkout\Block\Onepage\Success
{
    protected $order;

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if(!$this->order) {
            /** @var \Magento\Sales\Model\Order $order */
            $this->order = $this->_checkoutSession->getLastRealOrder();
        }

        return $this->order;
    }
}