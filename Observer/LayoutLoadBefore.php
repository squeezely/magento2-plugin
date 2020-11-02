<?php
namespace Squeezely\Plugin\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;
use Squeezely\Plugin\Helper\Data;

class LayoutLoadBefore implements ObserverInterface {
    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var CookieManagerInterface
     */
    private $_cookieManager;
    /**
     * @var CookieMetadataFactory
     */
    private $_cookieMetadataFactory;
    /**
     * @var SessionManagerInterface
     */
    private $_sessionManager;

    public function __construct(
        LoggerInterface $logger,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    ) {
        $this->_logger = $logger;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->_sessionManager = $sessionManager;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer) {
        if(($cookieValue = $this->_cookieManager->getCookie(Data::SQUEEZELY_COOKIE_NAME))) {
            $metadata = $this->_cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDurationOneYear()
                ->setPath($this->_sessionManager->getCookiePath())
                ->setDomain($this->_sessionManager->getCookieDomain());

            try {
                $this->_cookieManager->setPublicCookie(Data::SQUEEZELY_COOKIE_NAME, $cookieValue, $metadata);
            }
            catch(\Throwable $throwable) {
                // don't do anything with the exception (for now)
            }
        }
    }
}
