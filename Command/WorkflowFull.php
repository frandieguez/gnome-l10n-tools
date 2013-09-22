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
            ->setDescription('Extracts and updates the localized strings')
            ->setHelp(
                <<<EOF
The <info>damned:lies</info> checks the GNOME Damned Lies web service to
fetch new translation settings.

<info>php app/console damned:lies</info>

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

        $stats = $this->fetchStatsForReleaseAndLang($config['release_set'], $config['language']);
        $untranslatedModules = $this->getUntranslatedModules($stats);


        $dialog = $this->getHelperSet()->get('dialog');

        $pickModules = '';
        foreach ($untranslatedModules as $key => $module) {
            $pickModules .=
                "\t[$key] {$module['name']} ({$module['branch']}) "
                ." {$module['stats']['untranslated']} untranslated, {$module['stats']['fuzzy']} fuzzy\n";
        }

        $this->output->writeln("   Modules with translations needed in {$config['language']}/{$config['release_set']}");
        $selection = (int) $dialog->ask(
            $output,
            $pickModules.
            '   Which module do you want to translate [0]: ',
            0
        );

        if (is_integer($selection)) {
            $arguments = array(
                'command'  => 'module:translate',
                'module' => $untranslatedModules[$selection]['name'],
                '--branch' => $untranslatedModules[$selection]['branch']
            );
            $input = new ArrayInput($arguments);

            $command = $this->getApplication()->find('module:translate');

            $returnCode = $command->run($input, $output);
        }

    }

    protected function getConfig()
    {
        return $this->getApplication()->config['parameters'];
    }

    protected function fetchStatsForReleaseAndLang($releaseSet, $lang)
    {
        $this->output->write("<comment>Fetching DL stats...</comment>");

        $url = "https://l10n.gnome.org/languages/$lang/$releaseSet/xml";
        $serverContents = simplexml_load_file($url);

        $categories = $serverContents->xpath('category');

        $modules = array();
        foreach ($categories as $category) {
            $rawModules   = $category->module;

            foreach ($rawModules as $module) {
                $modules []= array(
                    'name'   => (string) $module->attributes()['id'],
                    'branch' => (string) $module->attributes()['branch'],
                    'stats'  => array(
                        'translated'   => (int) $module->domain->translated,
                        'untranslated' => (int) $module->domain->untranslated,
                        'fuzzy'        => (int) $module->domain->fuzzy,
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
