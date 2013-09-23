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
            ->setDescription('Clones the repository for a given module')
            ->setDefinition(
                array(
                    new InputArgument('module', InputArgument::REQUIRED, 'The module name to download'),
                )
            )
            ->setHelp(<<<EOF
The <info>module:download</info> clones the GNOME repository
for a given module.

Executes a git clone against the module repository in the
GNOME servers and initializes the available submodules in
the cloned repository.

If the repository is already cloned it gets the latest changes
from the external repository.
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

        if (is_dir(getcwd()."/$module")) {
            $this->output->writeln("Fetching latest changes for '$module'");

            chdir($this->config['base_dir'].'/modules/'.$module);
            shell_exec("git pull --rebase");
        } else {
            $this->output->writeln("Downloading '$module' from GNOME");

            $username = $this->config['repository']['username'];

            shell_exec("git clone ssh://$username@git.gnome.org/git/$module");
        }

        chdir($this->config['base_dir'].'/modules/'.$module);
        exec('git submodule update --init');

        return true;
    }
}
