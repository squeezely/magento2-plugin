<?php
namespace Squeezely\Plugin\Command;

use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Testbed extends Command
{
    protected $om;
    protected $example;

    public function __construct(ObjectManagerInterface $om, \Squeezely\Plugin\Model\Example $ex) {
        $this->om = $om;
        $this->example = $ex;
        return parent::__construct();
    }

    protected function configure() {
        $this->setName("ps:plugin");
        $this->setDescription("The command for testing magento 2 plugins.");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln("Hello Goeie World");
    }
}