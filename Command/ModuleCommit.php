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

class ModuleCommit extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:commit')
            ->setDescription('Commits available changes to local repository')
            ->setDefinition(
                [
                    new InputArgument('module', InputArgument::REQUIRED, 'Module to commit'),
                ]
            )
            ->setHelp(<<<'EOF'
The <info>module:commit</info> commits available changes to the local repository.

Before committing changes it shows the available changes and asks the user to accept them.
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

        $this->output->writeln("<comment>Committing module '$module'</comment>");

        $output = shell_exec('LC_ALL=C git status');

        $dialog = $this->getHelperSet()->get('dialog');
        $selection = (string) $dialog->askConfirmation(
            $this->output,
            "\n".$output."\nDo you accept these changes [no]: ",
            false
        );

        if (empty($selection)) {
            $this->output->writeln('Changes not accepted.');

            return false;
        }

        $languageCode = $this->config['language'];
        $languageName = $this->config['language_name'];

        shell_exec("git add po/{$languageCode}.po");
        shell_exec("git commit -m 'Updated {$languageName} translations'");

        $this->output->writeln('Commit DONE');
    }
}
