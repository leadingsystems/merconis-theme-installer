<?php

namespace LeadingSystems\MerconisThemeInstallerBundle\EventListener;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\GenerateSymlinksEvent;
use Merconis\Core\ls_shop_generalHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Container;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use Symfony\Component\Filesystem\Filesystem;
use Composer\InstalledVersions;

/**
 * @ServiceTag("kernel.event_listener", event=ContaoCoreEvents::GENERATE_SYMLINKS)
 */
class GenerateSymlinksListener
{
    /**
     * @var string
     */
    private $str_projectDir;

    /**
     * @var Filesystem
     */
    private $obj_filesystem;

    /**
     * @var Container
     */
    private $obj_container;

    /**
     * GenerateSymlinksListener constructor.
     * @param string $str_projectDir
     * @param Filesystem|null $obj_filesystem
     * @param Container $obj_container
     */
    public function __construct(string $str_projectDir, Container $obj_container, Filesystem $obj_filesystem = null)
    {
        $this->str_projectDir = $str_projectDir;
        $this->obj_filesystem = $obj_filesystem ?: new Filesystem();
        $this->obj_container = $obj_container;
    }

    /**
     * @param GenerateSymlinksEvent $event
     */
    public function __invoke(GenerateSymlinksEvent $event)
    {
        if (isset($GLOBALS['merconis-theme']['bln_symlinkEventListenerAlreadyCalled']) && $GLOBALS['merconis-theme']['bln_symlinkEventListenerAlreadyCalled']) {
            return;
        }

        $GLOBALS['merconis-theme']['bln_symlinkEventListenerAlreadyCalled'] = true;

        /*
         * Create the "merconisfiles" folder if it doesn't exist yet
         */
        if (!file_exists($this->str_projectDir . '/files/merconisfiles'))
        {
            $this->obj_filesystem->mkdir($this->str_projectDir . '/files/merconisfiles', 0755);
        }

        /*
         * Unprotect the merconisfiles folder
         */
        if (!file_exists($this->str_projectDir . '/files/merconisfiles/.public'))
        {
            $this->obj_filesystem->touch($this->str_projectDir . '/files/merconisfiles/.public');
        }

        /*
         * Run contao.command.symlinks to make sure that the symlink to the "files" folder is set in the public folder
         */
        $command = $this->obj_container->get('contao.command.symlinks');
        $command->run(new ArgvInput(array()), new NullOutput());

        $this->handleResources($event);
    }

    private function handleResources(GenerateSymlinksEvent $event) {
        $arr_themeResourcesConfig = $this->getThemeResourcesConfig();
        $str_themeResourceFolder =  $this->getInstalledThemeFolder(). '/src/Resources';

        foreach($arr_themeResourcesConfig as $arr_resourceInfo) {
            if (empty($arr_resourceInfo['source']) || empty($arr_resourceInfo['target'])) {
                continue;
            }

            $str_sourcePath = $str_themeResourceFolder . '/' . $arr_resourceInfo['source'];
            $str_targetPath = $arr_resourceInfo['target'];

            if (file_exists($str_sourcePath) && !file_exists($this->str_projectDir . '/' . $str_targetPath)) {
                if (!empty($arr_resourceInfo['symlink'])) {
                    $event->addSymlink(
                        $str_sourcePath,
                        $str_targetPath
                    );
                } else {
                    $this->obj_filesystem->mirror($str_sourcePath, $str_targetPath);
                }
            }
        }
    }

    private function getThemeResourcesConfig()
    {
        $arr_composerConfig = $this->getComposerConfig();
        return $arr_composerConfig['extra']['merconis-theme-resources'] ?? [];
    }

    private function getInstalledThemeFolder(): ?string
    {
        $installedTheme = ls_shop_generalHelper::getInstalledThemeExtensions()[0];
        return InstalledVersions::getInstallPath($installedTheme);
    }

    private function getComposerConfig()
    {
        ls_shop_generalHelper::getInstalledThemeExtensions();

        $str_composerConfigFilePath = $this->getInstalledThemeFolder().'/composer.json';

        if (!file_exists($str_composerConfigFilePath)) {
            throw new \Exception('Composer file is missing (' . $str_composerConfigFilePath . ')');
        }

        $arr_composerConfig = json_decode(file_get_contents($str_composerConfigFilePath), true);
        return $arr_composerConfig;
    }
}