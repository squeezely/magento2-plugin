<?php
namespace Squeezely\Plugin\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class SqueezelyDataLayerHelper extends AbstractHelper {
    /**
     * @var array
     */
    private $_queuedEvents = [];

    /**
     * @param string $eventName
     * @param array  $data
     */
    public function addEventToQueue(string $eventName, array $data) {
        $this->_queuedEvents[$eventName] = $data;
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
        return $dataScript;
    }
}