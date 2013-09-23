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
            ->setDescription('Opens editor for the updated translations file of a module')
            ->setDefinition(
                array(
                    new InputArgument('module', InputArgument::REQUIRED, 'The module to translate'),
                    new InputOption('branch', 'b', InputOption::VALUE_REQUIRED, 'The module\'s branch to translate', 'master'),
                )
            )
            ->setHelp(<<<EOF
The <info>module:translate</info> translates a module given the module and branch name.

Fetches the latest changes in module repository, updates transaltions against
current code and opens the pofile editor to complete untranslated strings.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->config = $this->getApplication()->config;

        $module = $this->input->getArgument('module');
        $branch = $this->input->getOption('branch');

        $this->output->writeln("<comment>Translating module $module [$branch]</comment>");

        // Fetch latest changes from module or download it if not present
        chdir($this->config['base_dir']);
        $command = $this->getApplication()->find('module:download')->run(
            new ArrayInput(
                array(
                    'command' => 'module:download',
                    'module'  => $module,
                )
            ),
            $output
        );

        $workingPath = self::searchForModule($module);
        chdir($workingPath);

        // Checkout to the desired branch
        $this->checkoutBranch($branch);

        // Update translations file against current code
        $this->updateLatestTranslations();

        // Open pofile editor with the translations file to complete it
        $this->openPofileEditor();
    }

    /**
     * Search module path from its name
     *
     * @return void
     **/
    public function searchForModule($moduleName)
    {
        return realpath($this->config['base_dir']."/modules/$moduleName/");
    }

    /**
     * Checks to a specific branch given by a argument
     *
     * @return boolean
     **/
    public function checkoutBranch($branch)
    {
        if (!is_null($branch)) {
            $this->output->writeln("\t<info>Checking out to branch $branch</info>");
            exec('git checkout '.$branch);
        }

        return false;
    }

    /**
     * Updates galician translations for a module
     *
     * @return boolean
     **/
    public function updateLatestTranslations()
    {
        $this->output->writeln("\t<info>Updating latest translations</info>");

        chdir("./po/");
        $this->output->writeln(
            shell_exec('LC_ALL=C intltool-update '.$this->config['language'])
        );
    }

    /**
     * Launches the pofile editor
     *
     * @return void
     **/
    public function openPofileEditor()
    {
        $this->output->writeln("\t<info>Launching pofile editor</info>");

        $this->output->writeln(
            shell_exec("xdg-open ".getcwd()."/".$this->config['language'].".po")
        );
    }
}
