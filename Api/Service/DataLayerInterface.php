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
     * Prepare script for frontend
     *
     * @param stdClass $object
     *
     * @return string
     */
    public function generateDataScript(stdClass $object);
}
