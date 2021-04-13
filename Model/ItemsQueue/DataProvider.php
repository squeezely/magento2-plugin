<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\ItemsQueue;

use Magento\Framework\App\Request\Http;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Squeezely\Plugin\Model\ItemsQueue\CollectionFactory as ItemsQueueCollectionFactory;

/**
 * Class DataProvider
 *
 * Form data provider
 */
class DataProvider extends AbstractDataProvider
{

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $_loadedData;

    /**
     * @var Http
     */
    private $request;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ItemsQueueCollectionFactory $accountCollectionFactory
     * @param Http $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ItemsQueueCollectionFactory $accountCollectionFactory,
        Http $request,
        array $meta = [],
        array $data = []
    ) {
        $this->request = $request;
        $this->collection = $accountCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }

        $data = [];
        $items = $this->collection->getItems();
        if (count($items) == 0) {
            return $data;
        }
        /** @var Data $item */
        foreach ($items as $item) {
            $this->_loadedData[$item->getEntityId()] = $item->getData();
        }

        return $this->_loadedData;
    }
}
