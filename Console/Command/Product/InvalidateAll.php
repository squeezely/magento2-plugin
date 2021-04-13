<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Console\Command\Product;

use Squeezely\Plugin\Model\Command\Product\InvalidateAll as InvalidateAllProducts;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Invalidate all products via command line
 */
class InvalidateAll extends Command
{

    /**
     * Command call name
     */
    const COMMAND_NAME = 'squeezely:product:invalidate-all';
    /**
     * @var InvalidateAllProducts
     */
    private $invalidateAllProducts;

    /**
     * InvalidateAll constructor.
     * @param InvalidateAllProducts $invalidateAllProducts
     */
    public function __construct(
        InvalidateAllProducts $invalidateAllProducts
    ) {
        $this->invalidateAllProducts = $invalidateAllProducts;
        parent::__construct();
    }

    /**
     *  {@inheritdoc}
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Squeezely: Invalidate all products');
        parent::configure();
    }

    /**
     *  {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $results = $this->invalidateAllProducts->execute();
        foreach ($results as $result) {
            $output->writeln($result['msg']);
        }

        return 0;
    }
}
