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

class WebServiceClient extends Command
{
    public $supportedLanguages = array('es_ES', 'gl_ES', 'pt_BR');

    public $localeFolder = '/Resources/locale';

    protected function configure()
    {
        $this
            ->setName('damnedlies:work')
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

        $this->showInitializationBanner();
        if (!$this->checkEnvironment()) {
            die();
        }

        $output->write("<comment> - Fetching DL stats...</comment>");
        $this->serverContents = simplexml_load_file('https://l10n.gnome.org/languages/gl/gnome-3-10/xml');

        var_dump(count($this->serverContents->xpath('category')));die();

        $output->writeln("<fg=green;> DONE</fg=green;>");

        // var_dump($serverContents);die();
    }

    protected function showInitializationBanner()
    {
        $this->output->writeln("GNOME Damned Lies worker");
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
