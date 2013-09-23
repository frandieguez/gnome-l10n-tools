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

class ModuleReset extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:reset')
            ->setDescription('Discards local changes in the module repository')
            ->setDefinition(
                array(
                    new InputArgument('module', InputArgument::REQUIRED),
                )
            )
            ->setHelp(<<<EOF
The <info>module:reset</info> discards all the changes
in the repository files for a given module.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->config = $this->getApplication()->config;

        $module = $this->input->getArgument('module');

        chdir($this->config['base_dir'].'/modules/'.$module);

        $this->output->write("<comment>Reseting module $module</comment>");

        shell_exec("git reset --hard");
        $this->output->writeln("<fg=green;> DONE</fg=green;>");
    }
}
