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

class WorkflowFull extends Command
{
    protected function configure()
    {
        $this
            ->setName('workflow:full')
            ->setDefinition(
                array(
                    new InputOption('release-set', 'r', InputOption::VALUE_REQUIRED, 'The release set to translate', null),
                    new InputOption('language', 'l', InputOption::VALUE_REQUIRED, 'The language to translate into', null),
                )
            )
            ->setDescription('Allows to translate a release set into a language')
            ->setHelp(<<<EOF
The <info>damned:lies</info> checks the GNOME Damned Lies web service to
fetch real-time translation status for a given release set and language.

If there are new untranslated strings it shows the list of modules
and allows the user to select one of them to translate.
When the user selects one and pofile editor will raise to complete
those strings.
After that it will ask the user to accept changes, it will commit
them to the local repository and finally it will push them to the
GNOME Git repository.

All the workflow will start at the beggining until all the modules
are completed.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        if (!$this->checkEnvironment()) {
            die();
        }

        $config = $this->getConfig();

        $releaseSet = $this->input->getOption('release-set');
        if (!is_null($releaseSet)) {
            $config['release_set'] = $releaseSet;
        }

        $releaseSet = $this->input->getOption('language');
        if (!is_null($releaseSet)) {
            $config['language'] = $releaseSet;
        }

        $this->output->writeln("<comment>Full workflow for {$config['release_set']} [{$config['language']}]...</comment>");

        $stats = $this->fetchStatsForReleaseAndLang($config['release_set'], $config['language']);
        $untranslatedModules = $this->getUntranslatedModules($stats);

        $dialog = $this->getHelperSet()->get('dialog');

        if (count($untranslatedModules) <= 0) {
            $this->output->writeln('All modules translated! Go to rest!');

            return false;
        }

        $rows = array();
        $autoComplete = array();
        foreach ($untranslatedModules as $key => $module) {
            $rows []= array(
                $module['name'],
                $module['branch'],
                $module['stats']['untranslated'],
                $module['stats']['fuzzy'],
                sprintf('%3d%%', ($module['stats']['total'] - $module['stats']['untranslated'] - $module['stats']['fuzzy']) / ($module['stats']['total']) * 100)
            );

            $autoComplete []= $module['name'];
        }

        $table = $this->getHelperSet()->get('table');
        $table
            ->setLayout(\Symfony\Component\Console\Helper\TableHelper::LAYOUT_BORDERLESS)
            ->setHeaders(array('Name', 'Branch', 'Untr.', 'Fuzzy', '%'))
            ->setRows($rows);

        while (true) {
            $this->output->writeln("   Modules with translations needed in {$config['language']}/{$config['release_set']} (".count($untranslatedModules).")");

            $tableRender = $table->render($output);

            $selection = (string) $dialog->ask(
                $output,
                $tableRender.
                '   Which module do you want to translate [0]: ',
                null,
                $autoComplete
            );

            if (is_string($selection) && $this->validModule($autoComplete, $selection)) {
                $command = $this->getApplication()->find('module:translate');
                $returnCode = $command->run(
                    new ArrayInput(
                        array(
                            'command'  => 'module:translate',
                            'module' => $untranslatedModules[$selection]['name'],
                            '--branch' => $untranslatedModules[$selection]['branch']
                        )
                    ),
                    $output
                );

                $command = $this->getApplication()->find('module:commit');
                $returnCode = $command->run(
                    new ArrayInput(
                        array(
                            'command'  => 'module:commit',
                            'module' => $untranslatedModules[$selection]['name'],
                        )
                    ),
                    $output
                );

                $command = $this->getApplication()->find('module:push');
                $returnCode = $command->run(
                    new ArrayInput(
                        array(
                            'command'  => 'module:push',
                            'module' => $untranslatedModules[$selection]['name'],
                        )
                    ),
                    $output
                );
            }
        }

    }

    protected function getConfig()
    {
        return $this->getApplication()->config;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    protected function validModule($modules, $selection)
    {
        if (!in_array($selection, $modules)) {
            return false;
        }

        return true;
    }

    protected function fetchStatsForReleaseAndLang($releaseSet, $lang)
    {
        $this->output->write("   Fetching DL stats...");

        $url = "https://l10n.gnome.org/languages/$lang/$releaseSet/xml";
        $serverContents = @simplexml_load_file($url);

        if (!$serverContents) {
            $this->output->writeln("\t<error>Release set '$releaseSet' or language '$lang' not valid</error>");
            die();
        }

        $categories = $serverContents->xpath('category');

        $modules = array();
        foreach ($categories as $category) {
            $rawModules   = $category->module;

            foreach ($rawModules as $module) {
                $modules [(string) $module->attributes()['id']]= array(
                    'name'   => (string) $module->attributes()['id'],
                    'branch' => (string) $module->attributes()['branch'],
                    'stats'  => array(
                        'translated'   => (int) $module->domain->translated,
                        'untranslated' => (int) $module->domain->untranslated,
                        'fuzzy'        => (int) $module->domain->fuzzy,
                        'total'        =>
                            (int) $module->domain->translated
                            + (int) $module->domain->fuzzy
                            + (int) $module->domain->untranslated,
                    )
                );
            }

        }
        $this->output->writeln("<fg=green;> DONE</fg=green;>");

        return $modules;
    }

    protected function getUntranslatedModules($stats)
    {
        $modules = array_filter(
            $stats,
            function ($module) {
                return (($module['stats']['untranslated'] + $module['stats']['fuzzy']) > 0);
            }
        );

        uasort(
            $modules,
            function ($a, $b) {
                $aNotCompleted = $a['stats']['untranslated'] + $a['stats']['fuzzy'];
                $bNotCompleted = $b['stats']['untranslated'] + $b['stats']['fuzzy'];

                if ($aNotCompleted == $bNotCompleted) {
                    return 0;
                }

                return ($aNotCompleted < $bNotCompleted) ? -1 : 1;
            }
        );

        return $modules;
    }

    protected function checkEnvironment()
    {
        // Checks for configuration
        // if not configured run the setup wizard
        $configFile = __DIR__.'/../config.yaml';
        if (file_exists($configFile)) {
            $configuration = file_get_contents($configFile);
            return true;
        } else {
            $this->output->writeln("\t<error>Not configured... Running Setup Wizard.. TODO</error>");
            return false;
        }
    }
}
