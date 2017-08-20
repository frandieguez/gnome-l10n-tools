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
use Symfony\Component\Console\Output\OutputInterface;

class TranslationMemoryRelease extends Command
{
    protected function configure()
    {
        $this
            ->setName('tm:release')
            ->setDescription('Creates a translation memory for a given release')
            ->setDefinition(
                [
                    new InputArgument('release-set', InputArgument::REQUIRED, 'The release set name to use'),
                    new InputOption('part', 'p', InputOption::VALUE_REQUIRED, 'Which part to generate: ui or doc', 'ui'),
                    new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force download of source files'),
                ]
            )
            ->setHelp(<<<'EOF'
The <info>tm:release</info> creates a translation memory for a given release.

Downloads the tarball compendium for a given release set and part (default: ui),
and creates the po and tmx compendiums.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->config = $this->getApplication()->config;

        $releaseSet = $this->input->getArgument('release-set');
        $part = $this->input->getOption('part');
        $force = $this->input->getOption('force');

        $this->output->writeln("<comment>Creating translation memory for '$releaseSet'</comment>");

        // Fetch latest changes from module or download it if not present
        chdir($this->config['base_dir']);

        $this->output->writeln("\tCreating working folder");

        $baseReleaseSetDir = $this->config['base_dir']."/final-products/$releaseSet";
        $sourcesFolder = $baseReleaseSetDir.'/sources';
        $compendiumsFolder = $baseReleaseSetDir.'/compendiums';
        $tmxFolder = $baseReleaseSetDir.'/tmx';

        $this->filesystem = new \Symfony\Component\Filesystem\Filesystem();

        $this->filesystem->mkdir($baseReleaseSetDir);
        $this->filesystem->mkdir($sourcesFolder);
        $this->filesystem->mkdir($compendiumsFolder);
        $this->filesystem->mkdir($tmxFolder);

        try {
            $translationPackageFolder = $this->downloadTranslationPackage($sourcesFolder, $releaseSet, $part, $force);

            $compendiumFile = $compendiumsFolder."/$releaseSet-$part.compendium.po";
            $this->createPoCompendium($translationPackageFolder, $compendiumFile);

            $cleanedCompendiumFile = $this->cleanpoCompendium($compendiumFile);

            $this->checkCompendiumFile($cleanedCompendiumFile);

            $targetTMXFile = $tmxFolder."/$releaseSet-$part.tmx";
            $this->createFinalTMXfile($cleanedCompendiumFile, $targetTMXFile);
        } catch (\Exception $e) {
            var_dump($e);
            die();
        }
    }

    /**
     * undocumented function.
     *
     * @return void
     *
     * @author
     **/
    public function downloadTranslationPackage($targetFolder, $releaseSet, $part = 'ui', $force = false)
    {
        $this->output->writeln("\tDownloading and extracting files from DL");

        $url = "https://l10n.gnome.org/languages/gl/$releaseSet/$part.tar.gz";
        $finalFile = $targetFolder."/$releaseSet-$part.tar.gz";

        if (!$this->filesystem->exists($finalFile) || $force) {
            file_put_contents($finalFile, file_get_contents($url));
        }

        $currentDir = getcwd();

        $finalFolder = $targetFolder."/$part-files";
        $this->filesystem->mkdir($finalFolder);
        chdir($finalFolder);

        shell_exec("tar xvf $finalFile");

        chdir($currentDir);

        return $finalFolder;
    }

    /**
     * undocumented function.
     *
     * @return void
     *
     * @author
     **/
    public function createPoCompendium($translationsFolderPath, $outputFilePath)
    {
        $this->output->writeln("\tCreating compendium file");
        shell_exec("pocompendium $outputFilePath -d $translationsFolderPath");
    }

    /**
     * undocumented function.
     *
     * @return void
     *
     * @author
     **/
    public function cleanpoCompendium($compendiumFile)
    {
        $this->output->writeln("\tCleaning compendium file");
        $cleanFileName = str_replace('.po', '.clean.po', $compendiumFile);
        shell_exec("poclean $compendiumFile -o $cleanFileName");

        return $cleanFileName;
    }

    /**
     * undocumented function.
     *
     * @return void
     *
     * @author
     **/
    public function checkCompendiumFile($translationsFilePath)
    {
        $this->output->writeln("\tChecking compendium file");
        shell_exec("msgfmt -vc $translationsFilePath");
    }

    /**
     * undocumented function.
     *
     * @return void
     *
     * @author
     **/
    public function createFinalTMXfile($originalPoFile, $targetTMXFile)
    {
        $this->output->writeln("\tCreating TMX file");
        $language = $this->config['language'];
        shell_exec("po2tmx --language=$language $originalPoFile $targetTMXFile");
    }
}
