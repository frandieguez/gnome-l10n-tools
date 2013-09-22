<?php
/**
 * This file is part of the Onm package.
 *
 * (c)  OpenHost S.L. <developers@openhost.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 **/
namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleTranslate extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:translate')
            ->setDescription('Explanation not completed')
            ->setDefinition(
                array(
                    new InputArgument('module', InputArgument::REQUIRED),
                    new InputOption('branch', 'b', InputOption::VALUE_REQUIRED, 'The module\'s branch to translate', 'master'),
                )
            )
            ->setHelp(
                <<<EOF
The <info>translate:module</info> translates a module given the name and branch.

<info>php app/consoletranslate:module --module=MODULE_NAME --branch=BRANCH_NAME</info>

EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $module = $this->input->getArgument('module');
        $branch = $this->input->getOption('branch');

        $this->output->writeln("\tTranslating $module [$branch] -> NOT IMPLEMENTED");
    }
}
