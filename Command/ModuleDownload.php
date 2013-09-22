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

class ModuleDownload extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:download')
            ->setDescription('Explanation not completed')
            ->setDefinition(
                array(
                    new InputArgument('module', InputArgument::REQUIRED),
                )
            )
            ->setHelp(
                <<<EOF
The <info>translate:module</info> clones the GNOME repository for a given module

<info>php app/console translate:download MODULE_NAME</info>

EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->config = $this->getApplication()->config;

        chdir($this->config['base_dir'].'/modules');

        $module = $this->input->getArgument('module');

        $this->output->writeln("<comment>Downloading module $module</comment>");

        $username = $this->config['repository']['username'];

        shell_exec("git clone ssh://$username@git.gnome.org/git/$module");
    }
}
