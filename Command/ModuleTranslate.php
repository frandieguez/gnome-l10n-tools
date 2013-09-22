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
The <info>translate:module</info> translates a module given the module and branch name.

<info>php app/consoletranslate:module MODULE_NAME --branch=BRANCH_NAME</info>

EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->config = $this->getApplication()->config['parameters'];

        $module = $this->input->getArgument('module');
        $branch = $this->input->getOption('branch');

        $this->output->writeln("\tTranslating $module [$branch] -> NOT IMPLEMENTED");
        $workingPath = self::searchForModule($module);
        if (!$workingPath) {
            $this->output->writeln("<error>Unable to find the module '$module'.</error>");
        }

        $this->output->writeln("<comment>Translating module $module [$branch]</comment>");

        chdir($workingPath);

        // Checkout branch
        $this->checkoutBranch($branch);

        // Execute pull if user wants to
        $this->getLatestRepositoryChanges();

        // Execute intltool-update
        $this->updateLatestTranslations();

        // Open pofile editor
        $this->openPofileEditor();

        // $this->output->writeln("\tTranslating $module [$branch] -> NOT IMPLEMENTED");
    }

    /**
     * Search module path from its name
     *
     * @return void
     * @author
     **/
    public function searchForModule($moduleName)
    {
        return realpath(getcwd()."/modules/$moduleName/");
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
        }

        return false;
    }

    /**
     * Makes a git pull over one path
     *
     * @return boolean
     **/
    public function getLatestRepositoryChanges()
    {
        $this->output->writeln("\t<info>Getting latest repository changes</info>");

        exec('git pull --rebase');
        exec('git submodule update --init');
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
     * @author
     **/
    public function openPofileEditor()
    {
        $this->output->writeln("\t<info>Launching pofile editor</info>");
        $output = shell_exec("xdg-open ".getcwd()."/".$this->config['language'].".po");
        $this->output->writeln($output);
    }
}
