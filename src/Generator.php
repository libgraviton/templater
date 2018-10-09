<?php
/**
 * generator
 */
namespace Graviton\Templater;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * @author   List of contributors <https://github.com/libgraviton/templater/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class Generator
{

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var array
     */
    private $configuration;

    /**
     * base dir, where the configuration is
     *
     * @var string
     */
    private $baseDir;

    /**
     * @var string
     */
    private $templateDir;

    /**
     * Generator constructor.
     *
     * @param OutputInterface $output        output
     * @param string          $configuration path to configuration file
     *
     * @throws \Exception
     */
    function __construct(OutputInterface $output, $configuration)
    {
        $this->output = $output;
        $this->fs = new Filesystem();
        $this->configuration = Yaml::parseFile($configuration);
        $this->baseDir = dirname(realpath($configuration)).DIRECTORY_SEPARATOR;

        // set template path
        if (isset($this->configuration['templateDirectory'])) {
            $this->templateDir = $this->baseDir . $this->configuration['templateDirectory'];
        } else {
            throw new \Exception('No templateDirectory specified in configuration');
        }

        $loader = new \Twig_Loader_Filesystem($this->templateDir);
        $this->twig = new \Twig_Environment($loader);
    }

    /**
     * generate
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function generate()
    {
        foreach ($this->configuration['groups'] as $groupName => $data) {
            if (!isset($data['ignoreFiles'])) {
                $data['ignoreFiles'] = null;
            }

            if (!isset($data['templateData'])) {
                $data['templateData'] = [];
            }

            $outputDirectory = $this->baseDir.$data['outputDirectory'];
            if (substr($outputDirectory, -1) != '/') {
                $outputDirectory .= '/';
            }

            $templates = $this->getTemplates($data['ignoreFiles']);
            foreach ($templates as $templatePath) {
                $content = $this->twig->render(basename($templatePath), $data['templateData']);
                $targetFilename = $outputDirectory.basename($templatePath);
                $this->fs->dumpFile($outputDirectory.basename($templatePath), $content);
                $this->output->writeln('Wrote file '.$targetFilename);
            }
        }
    }

    /**
     * gets template for group
     *
     * @param string $ignoreFiles file mask to ignore
     *
     * @return array file path array
     */
    private function getTemplates($ignoreFiles = null)
    {
        $templates = [];
        $finder = Finder::create()
            ->files()
            ->in($this->templateDir)
            ->ignoreDotFiles(true)
            ->exclude('_*');

        if (!is_null($ignoreFiles)) {
            $finder = $finder->exclude($ignoreFiles);
        }

        foreach ($finder as $file) {
            $templates[] = $file->getPathname();
        }

        return $templates;
    }
}
