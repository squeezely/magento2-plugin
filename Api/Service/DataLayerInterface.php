<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\Service;

use stdClass;

/**
 * Interface DataLayerInterface
 */
interface DataLayerInterface
{
    /**
     * Add event to checkout session queue
     *
     * @param string $eventName
     * @param array $data
     *
     * @return mixed
     */
    public function addEventToQueue(string $eventName, array $data);

    /**
     * Fire all queued events and reset the queue
     *
     * @return mixed
     */
    public function fireQueuedEvents();

    /**
     * Prepare script for frontend
     *
     * @param stdClass $object
     *
     * @return string
     */
    public function generateDataScript(stdClass $object);

    /**
     * Clear queuedevents by type
     *
     * @param string $type
     *
     * @return bool
     */
    public function clearQueuedEvents(string $type = 'all'): bool;
}
