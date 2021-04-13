<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Console\Command\Product;

use Squeezely\Plugin\Model\Command\Product\SyncInvalidated as SyncInvalidatedProducts;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sync invalidated products via command line
 */
class SyncInvalidated extends Command
{

    /**
     * Command call name
     */
    const COMMAND_NAME = 'squeezely:product:sync-invalidated';
    /**
     * @var SyncInvalidatedProducts
     */
    private $syncInvalidatedProducts;

    /**
     * SyncInvalidated constructor.
     * @param SyncInvalidatedProducts $syncInvalidatedProducts
     */
    public function __construct(
        SyncInvalidatedProducts $syncInvalidatedProducts
    ) {
        $this->syncInvalidatedProducts = $syncInvalidatedProducts;
        parent::__construct();
    }

    /**
     *  {@inheritdoc}
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Squeezely: Sync product');
        parent::configure();
    }

    /**
     *  {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $results = $this->syncInvalidatedProducts->execute();
        foreach ($results as $result) {
            $output->writeln($result['msg']);
        }

        return 0;
    }
}
