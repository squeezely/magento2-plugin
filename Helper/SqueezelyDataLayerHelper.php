<?php
namespace Squeezely\Plugin\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class SqueezelyDataLayerHelper extends AbstractHelper {
    /**
     * @var array
     */
    private $_queuedEvents = [];
    /**
     * @var Session
     */
    private $_checkoutSession;

    public function __construct(Context $context, Session $checkoutSession) {
        $this->_checkoutSession = $checkoutSession;

        $sessionEvents = $this->_checkoutSession->getSqueezelyQueuedEvents();
        if($sessionEvents) {
            $this->_queuedEvents = json_decode($sessionEvents, true) ?? [];
        }

        parent::__construct($context);
    }

    /**
     * @param string $eventName
     * @param array  $data
     */
    public function addEventToQueue(string $eventName, array $data) {
        $this->_queuedEvents[$eventName] = $data;
        $this->_checkoutSession->setSqueezelyQueuedEvents(json_encode($this->_queuedEvents));
    }

    /**
     * Fire all queued events and reset the queue
     *
     * @return string
     */
    public function fireQueuedEvents() {
        if(!$this->_queuedEvents) {
            return '';
        }

        $dataScript = '<script type="text/javascript">' . PHP_EOL;
        $dataScript .= 'window._sqzl = _sqzl || [];';

        foreach($this->_queuedEvents as $eventName => $data) {
            $event = array_merge(['event' => $eventName], $data);

            $dataScript .= '_sqzl.push(' . json_encode($event, JSON_PRETTY_PRINT) . ')' . PHP_EOL;
        }

        $dataScript .= '</script>' . PHP_EOL;

        $this->_queuedEvents = [];
        $this->_checkoutSession->setSqueezelyQueuedEvents(false);
        return $dataScript;
    }
}