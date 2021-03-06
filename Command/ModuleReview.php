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

class ModuleReview extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:review')
            ->setDescription('Resets the module local repository')
            ->setDefinition(
                [
                    new InputArgument('module', InputArgument::REQUIRED, 'The module name to review'),
                ]
            )
            ->setHelp(<<<'EOF'
The <info>module:review</info> checks all the translations for
a given module and language.

This command uses posieve under the hood and all the rules available
for the selected language inside it.

[WARNING] Check if your language has posieve rules for your language.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->config = $this->getApplication()->config;
        $languageCode = $this->config['language'];

        $module = $this->input->getArgument('module');

        $reviewerExecPath = $this->config['base_dir'].'/tools/pology/bin/posieve';
        $modulePath = $this->config['base_dir']."/modules/{$module}/po/{$languageCode}.po";

        $this->output->writeln("<comment>Reviewing spelling in module $module</comment>");

        $output = shell_exec(
            $reviewerExecPath.' -b --msgfmt-check --no-skip check-rules -slang:'.$languageCode
            .' -s"rulerx:^PT*" -saccel:_ -snorulerx:dual '
            .$modulePath
        );

        $this->output->writeln($output);
    }
}
