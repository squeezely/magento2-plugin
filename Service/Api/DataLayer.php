<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\Api;

use Magento\Checkout\Model\SessionFactory as Session;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use stdClass;

/**
 * Class DataLayer
 */
class DataLayer implements DataLayerInterface
{

    /**
     * @var array
     */
    private $queuedEvents = [];
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * DataLayer constructor.
     *
     * @param Session $checkoutSession
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        Session $checkoutSession,
        JsonSerializer $jsonSerializer
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->jsonSerializer = $jsonSerializer;
        $this->setQueuedEvents();
    }

    /**
     *  Set Queued Events
     */
    protected function setQueuedEvents()
    {
        $sessionEvents = $this->checkoutSession->create()->getSqueezelyQueuedEvents();
        if ($sessionEvents) {
            $this->queuedEvents = $this->jsonSerializer->unserialize($sessionEvents) ?? [];
        }
    }

    /**
     * @inheritDoc
     */
    public function addEventToQueue(string $eventName, array $data)
    {
        $data['event'] = $eventName;
        $this->queuedEvents[] = $data; //It can be several events with same name, e.g. AddToCart
        $this->checkoutSession->create()->setSqueezelyQueuedEvents(
            $this->jsonSerializer->serialize($this->queuedEvents)
        );
    }

    /**
     * @inheritDoc
     */
    public function getQueuedEvents()
    {
        $queuedEvents = $this->queuedEvents;
        return $queuedEvents;
    }

    /**
     * @inheritDoc
     */
    public function clearQueuedEvents(string $type = 'all'): bool
    {
        if ($type == 'all') {
            $this->queuedEvents = [];
            $this->checkoutSession->create()->setSqueezelyQueuedEvents(false);
        } else {
            $queuedEvents = $this->getQueuedEvents();
            foreach ($queuedEvents as $key => $event) {
                if ($event['event'] == $type) {
                    unset($queuedEvents[$key]);
                }
            }

            $this->checkoutSession->create()->setSqueezelyQueuedEvents($queuedEvents);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function fireQueuedEvents()
    {
        $queuedEvents = $this->getQueuedEvents();
        if (!$queuedEvents) {
            return '';
        }

        $dataScript = '<script type="text/javascript">' . PHP_EOL;
        $dataScript .= 'window._sqzl = _sqzl || [];';

        foreach ($queuedEvents as $event) {
            $dataScript .= '_sqzl.push('
                . $this->jsonSerializer->serialize($event)
                . ')' . PHP_EOL;
        }

        $dataScript .= '</script>' . PHP_EOL;
        $this->clearQueuedEvents();

        return $dataScript;
    }

    /**
     * @inheritDoc
     */
    public function generateDataScript(stdClass $object)
    {
        $dataScript = PHP_EOL;
        $dataScript .= '<script type="text/javascript">'
            . PHP_EOL
            . 'window._sqzl = _sqzl || []; _sqzl.push('
            . $this->jsonSerializer->serialize($object) . ')'
            . PHP_EOL
            . '</script>';

        return $dataScript;
    }
}
