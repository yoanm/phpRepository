<?php
namespace Yoanm\DefaultPhpRepository\Helper;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Yoanm\DefaultPhpRepository\Exception\TargetFileExistsException;

/**
 * Class TemplateFileProcessor
 */
class TemplateHelper
{
    /** @var array */
    private $variableList = [];
    /** @var string[] */
    private $variableNameList = [];
    /** @var string */
    private $filenameResolverRegexp;
    /** @var Filesystem */
    private $fs;

    /**
     * @param array $variableList
     * @param array $extraTemplatePath
     */
    public function __construct(array $variableList, array $extraTemplatePath = [])
    {
        $this->variableList = $variableList;
        foreach($this->variableList as $variableId => $variableValue) {
            $variableId = sprintf('%%%s%%', $variableId);
            $this->variableNameList[$variableId] = $variableId;
        }

        foreach ($extraTemplatePath as $extraKey => $extraValue) {
            $variableId = sprintf('%%%s%%', $extraKey);
            $this->variableNameList[$variableId] = $variableId;
            $this->variableList[$variableId] = $this->loadTemplate($extraValue);
        }

        // compile this regexp at startup (no need to to it each time)
        $this->filenameResolverRegexp = sprintf(
            '#%s?(?:[^%s]+%s)*([^%s]+)\.tmpl$#',
            PathHelper::separator(),
            PathHelper::separator(),
            PathHelper::separator(),
            PathHelper::separator()
        );
        // compile this regexp at startup (no need to to it each time)
        $this->filePathResolverRegexp = sprintf(
            '#%s?(?:[^%s]+%s)*[^%s]+\.tmpl$#',
            PathHelper::separator(),
            PathHelper::separator(),
            PathHelper::separator(),
            PathHelper::separator()
        );


        $this->fs = new Filesystem();
    }

    /**
     * @param string $templateFilePath
     * @param string $outputFilePath
     * @param bool   $overwrite overwrite even if present
     *
     * @throws TargetFileExistsException
     */
    public function dumpTemplate($templateFilePath, $outputFilePath, $overwrite = false)
    {
        $this->fs->dumpFile($outputFilePath, $this->loadTemplate($templateFilePath));
    }

    /**
     * @param string $templateFilePath
     *
     * @return string
     */
    public function resolveOutputFilePath($templateFilePath, $outputDir)
    {
        return sprintf(
            '%s%s',
            PathHelper::appendPathSeparator($outputDir),
            $this->resolveOutputFilename($templateFilePath)
        );
    }

    /**
     * @param string $templateFilePath
     *
     * @return string
     */
    public function resolveOutputFilename($templateFilePath)
    {
        return preg_replace($this->filenameResolverRegexp, '\1', $templateFilePath);
    }

    /**
     * @param string $templateFilePath
     *
     * @return string file content
     */
    public function loadTemplate($templateFilePath)
    {
        return str_replace($this->variableNameList, $this->variableList, file_get_contents($templateFilePath));
    }

    /**@return bool
     */
    protected function doOverwrite()
    {
        $question = new ConfirmationQuestion(
            '<question>Overwrite ? [n]</question>',
            false
        );

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }
}