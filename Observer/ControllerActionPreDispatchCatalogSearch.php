<?php
namespace Squeezely\Plugin\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Squeezely\Plugin\Helper\Data;
use Squeezely\Plugin\Helper\SqueezelyDataLayerHelper;

class ControllerActionPreDispatchCatalogSearch implements ObserverInterface {
    /**
     * @var Subscriber
     */
    protected $_subscriber;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var SqueezelyDataLayerHelper
     */
    private $_squeezelyDataLayerHelper;

    public function __construct(
        LoggerInterface $logger,
        SqueezelyDataLayerHelper $squeezelyDataLayerHelper
    ) {
        $this->_logger = $logger;
        $this->_squeezelyDataLayerHelper = $squeezelyDataLayerHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer) {
        /** @var RequestInterface $request */
        $request = $observer->getControllerAction()->getRequest();

        $searchKey = $request->getParam('q');
        $this->_squeezelyDataLayerHelper->addEventToQueue('Search', [
            'keyword' => $searchKey
        ]);
    }
}
