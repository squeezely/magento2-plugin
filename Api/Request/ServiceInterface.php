<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\Request;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;

/**
 * API request service interface
 */
interface ServiceInterface
{
    /**
     * Prepare data for API call
     *
     * @param array $fields
     * @param string $endpoint
     * @param null $storeId
     * @param string $method
     * @param bool $allowSoftFail
     * @return mixed
     * @throws LocalizedException
     * @throws AuthenticationException
     */
    public function execute(
        array $fields,
        string $endpoint,
        $storeId = null,
        string $method = 'POST',
        bool $allowSoftFail = true
    );
}
