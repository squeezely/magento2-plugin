<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source of option values in a form of value-label pairs
 */
class Frequency implements OptionSourceInterface
{
    /**
     * Options array
     *
     * @var array
     */
    public $options = null;

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if (!$this->options) {
            $this->options = [
                ['value' => '0 * * * *', 'label' => __('Hourly')],
                ['value' => '0 4 * * *', 'label' => __('Daily')],
                ['value' => '0 4 * * MON', 'label' => __('Weekly')],
                ['value' => '0 4 1 * *', 'label' => __('Monthly')],
                ['value' => '0 4 1 1 *', 'label' => __('Yearly')],
                ['value' => '-', 'label' => __('Never')]
            ];
        }

        return $this->options;
    }
}
