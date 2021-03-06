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
use Symfony\Component\Console\Output\OutputInterface;

class ModulePush extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:push')
            ->setDescription('Pushes changes to GNOME repository')
            ->setDefinition(
                [
                    new InputArgument('module', InputArgument::REQUIRED),
                ]
            )
            ->setHelp(<<<'EOF'
The <info>module:push</info> commits available changes to
the local repository.

Before pushing changes to the external repository, module:push
fetches all the changes from there, in order to avoid push errors.
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

        $this->output->writeln("<comment>Pushing changes to GNOME '$module' repository</comment>");

        shell_exec('git pull --rebase');

        shell_exec('git push');

        $this->output->writeln('Push DONE');
    }
}
