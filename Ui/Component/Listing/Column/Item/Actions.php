<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Squeezely\Plugin\Ui\Component\Listing\Column\Item;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Actions
 */
class Actions extends Column
{

    public const ROUTE = 'sqzl/item/update';

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                $item[$name]['update'] = [
                    'href' => $this->context->getUrl(
                        self::ROUTE,
                        ['item_id' => $item['entity_id']]
                    ),
                    'label' => __('Run Update')
                ];
            }
        }

        return $dataSource;
    }
}
