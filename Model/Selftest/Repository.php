<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Selftest;

use Squeezely\Plugin\Api\Selftest\RepositoryInterface as SelftestRepositoryInterface;

/**
 * Selftest repository class
 */
class Repository implements SelftestRepositoryInterface
{
    /**
     * @var array
     */
    private $testList;

    /**
     * Repository constructor.
     *
     * @param array $testList
     */
    public function __construct(
        $testList
    ) {
        $this->testList = $testList;
    }

    /**
     * @inheritDoc
     */
    public function test($output = true): array
    {
        $result = [];
        foreach ($this->testList as $data) {
            $result[] = $data->execute();
        }
        return $result;
    }
}
