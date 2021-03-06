<?php

namespace elkan\BehatFormatter\Formatter;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Behat\Tester\Result\SkippedStepResult;
use Behat\Behat\Tester\Result\UndefinedStepResult;
use Behat\Testwork\Counter\Memory;
use Behat\Testwork\Counter\Timer;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteTested;
use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\BeforeSuiteTested;
use Behat\Testwork\Output\Exception\BadOutputPathException;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use elkan\BehatFormatter\Classes\Feature;
use elkan\BehatFormatter\Classes\Scenario;
use elkan\BehatFormatter\Classes\Step;
use elkan\BehatFormatter\Classes\Suite;
use elkan\BehatFormatter\Printer\FileOutputPrinter;
use elkan\BehatFormatter\Renderer\BaseRenderer;
use elkan\BehatFormatter\Context;
use RuntimeException;


/**
 * Class BehatFormatter
 * @package tests\features\formatter
 */
class BehatFormatter implements Formatter
{

    //<editor-fold desc="Variables">
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var
     */
    private $name;

    /**
     * @var
     */
    private $timer;

    /**
     * @var
     */
    private $memory;

    /**
     * where to save the generated report file
     *
     * @var string
     */
    private $outputPath;

    /**
     * Behat base path
     *
     * @var string
     */
    private $base_path;

    /**
     * Printer used by this Formatter and Context
     *
     * @var OutputPrinter
     */
    private $printer;

    /**
     * Renderer used by this Formatter
     *
     * @var BaseRenderer
     */
    private $renderer;

    /**
     * Flag used by this Formatter
     *
     * @var bool
     */
    private $print_args;

    /**
     * Flag used by this Formatter
     *
     * @var bool
     */
    private $print_outp;

    /**
     * Flag used by this Formatter
     *
     * @var bool
     */
    private $loop_break;

    /**
     * Flag used by this Formatter
     *
     * @var bool
     */
    private $show_tags;

    /**
     * Flag used by this Formatter
     * @var string
     */
    private $projectname;

    /**
     * Flag used by this Formatter
     * @var string
     */
    private $projectdescription;

    /**
     * Flag used by this Formatter
     * @var string
     */
    private $projectimage;

    /**
     * @var array
     */
    private $suites;

    /**
     * @var Suite
     */
    private $currentSuite;

    /**
     * @var int
     */
    private $featureCounter = 1;

    /**
     * @var Feature
     */
    private $currentFeature;

    /**
     * @var Scenario
     */
    private $currentScenario;

    /**
     * @var int
     */
    private $currentExampleCount;

    /**
     * @var array|null
     */
    private $currentExampleLines;

    /**
     * @var Scenario[]
     */
    private $failedScenarios;

    /**
     * @var Scenario[]
     */
    private $passedScenarios;

    /**
     * @var Feature[]
     */
    private $failedFeatures;

    /**
     * @var Feature[]
     */
    private $passedFeatures;

    /**
     * @var Step[]
     */
    private $failedSteps;

    /**
     * @var Step[]
     */
    private $passedSteps;

    /**
     * @var Step[]
     */
    private $pendingSteps;

    /**
     * @var Step[]
     */
    private $skippedSteps;


    //</editor-fold>

    //<editor-fold desc="Formatter functions">
    /**
     * @param $name
     * @param $projectName
     * @param $projectImage
     * @param $projectDescription
     * @param $renderer
     * @param $filename
     * @param $print_args
     * @param $print_outp
     * @param $loop_break
     * @param $show_tags
     * @param $twig_template_file
     * @param $twig_template_path
     * @param $html_source_error_output
     * @param $base_path
     */
    public function __construct(
        $name, $projectName, $projectImage, $projectDescription, $renderer,
        $filename, $print_args, $print_outp, $loop_break, $show_tags,
        $twig_template_file, $twig_template_path, $html_source_error_output, $base_path
    )
    {
        $this->projectname = $projectName;
        $this->projectimage = $projectImage;
        $this->projectdescription = $projectDescription;
        $this->name = $name;
        $this->base_path = $base_path;
        $this->print_args = $print_args;
        $this->print_outp = $print_outp;
        $this->loop_break = $loop_break;
        $this->show_tags = $show_tags;
        $this->setParameter('twig_template_file', $twig_template_file);
        $this->setParameter('twig_template_path', $twig_template_path);
        $this->setParameter('html_source_error_output', $html_source_error_output);

        $this->renderer = new BaseRenderer($renderer, $base_path);
        $this->printer = new FileOutputPrinter($this->renderer->getNameList(), $filename, $base_path);
        $this->timer = new Timer();
        $this->memory = new Memory();

    }

    /**
     * Sets formatter parameter.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            'tester.exercise_completed.before' => 'onBeforeExercise',
            'tester.exercise_completed.after' => 'onAfterExercise',
            'tester.suite_tested.before' => 'onBeforeSuiteTested',
            'tester.suite_tested.after' => 'onAfterSuiteTested',
            'tester.feature_tested.before' => 'onBeforeFeatureTested',
            'tester.feature_tested.after' => 'onAfterFeatureTested',
            'tester.scenario_tested.before' => 'onBeforeScenarioTested',
            'tester.scenario_tested.after' => 'onAfterScenarioTested',
            'tester.outline_tested.before' => 'onBeforeOutlineTested',
            'tester.outline_tested.after' => 'onAfterOutlineTested',
            'tester.step_tested.after' => 'onAfterStepTested',
        );
    }

    /**
     * Returns formatter name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getProjectName()
    {
        return $this->projectname;
    }

    /**
     * Copy temporary screenshot folder to the assets folder
     */
    public function copyTempScreenshotDirectory()
    {
        $source = getcwd() . DIRECTORY_SEPARATOR . '.tmp_behatFormatter';
        $destination = $this->printer->getOutputPath() . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'screenshots';

        if (is_dir($destination)) {
            $this->delTree($destination);
        }
        if (is_dir($source)) {
            rename($source, $destination);
        }
    }

    /**
     * Delete recursive directory
     *
     * @param string $dir
     *
     * @return bool
     */
    private function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file") && !is_link($dir)) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * @return string
     */
    public function getProjectImage()
    {
        $imagepath = realpath($this->projectimage);
        if ($imagepath === false || $this->projectimage === null) {
            //There is no image to copy
            return null;
        }

        // Copy project image to assets folder

        //first create the assets dir
        $destination = $this->printer->getOutputPath() . DIRECTORY_SEPARATOR . 'assets';
        if (!mkdir($destination) && !is_dir($destination)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $destination));
        }

        $filename = basename($imagepath);
        copy($imagepath, $destination . DIRECTORY_SEPARATOR . $filename);

        return 'assets/' . $filename;
    }

    /**
     * @return string
     */
    public function getProjectDescription()
    {
        return $this->projectdescription;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->base_path;
    }

    /**
     * Returns formatter description.
     * @return string
     */
    public function getDescription()
    {
        return "Elkan's behat Formatter";
    }

    /**
     * Returns formatter output printer.
     * @return OutputPrinter
     */
    public function getOutputPrinter()
    {
        return $this->printer;
    }

    /**
     * Returns output path
     * @return String output path
     */
    public function getOutputPath()
    {
        return $this->outputPath;
    }

    /**
     * Verify that the specified output path exists or can be created,
     * then sets the output path.
     *
     * @param String $path Output path relative to %paths.base%
     *
     * @throws BadOutputPathException
     */
    public function setOutputPath($path)
    {
        $outpath = realpath($this->base_path . DIRECTORY_SEPARATOR . $path);
        if (!file_exists($outpath) && !mkdir($outpath, 0755, true) && !is_dir($outpath)) {
            throw new BadOutputPathException(
                sprintf(
                    'Output path %s does not exist and could not be created!',
                    $outpath
                ),
                $outpath
            );
        }
        $this->outputPath = $outpath;
    }

    /**
     * Returns if it should print the step arguments
     * @return boolean
     */
    public function getPrintArguments()
    {
        return $this->print_args;
    }

    /**
     * Returns if it should print the step outputs
     * @return boolean
     */
    public function getPrintOutputs()
    {
        return $this->print_outp;
    }

    /**
     * Returns if it should print scenario loop break
     * @return boolean
     */
    public function getPrintLoopBreak()
    {
        return $this->loop_break;
    }

    /**
     * Returns date and time
     * @return string
     */
    public function getBuildDate()
    {
        $datetime = (date('I')) ? 7200 : 3600;
        return gmdate('D d M Y H:i', time() + $datetime);
    }

    /**
     * Returns if it should print tags
     * @return boolean
     */
    public function getPrintShowTags()
    {
        return $this->show_tags;
    }

    /**
     * @return Timer
     */
    public function getTimer()
    {
        return $this->timer;
    }

    /**
     * @return Memory
     */
    public function getMemory()
    {
        return $this->memory;
    }

    /**
     * @return array
     */
    public function getSuites()
    {
        return $this->suites;
    }

    /**
     * @return Suite
     */
    public function getCurrentSuite()
    {
        return $this->currentSuite;
    }

    /**
     * @return int
     */
    public function getFeatureCounter()
    {
        return $this->featureCounter;
    }

    /**
     * @return Feature
     */
    public function getCurrentFeature()
    {
        return $this->currentFeature;
    }

    /**
     * @return Scenario
     */
    public function getCurrentScenario()
    {
        return $this->currentScenario;
    }

    /**
     * @return Scenario[]
     */
    public function getFailedScenarios()
    {
        return $this->failedScenarios;
    }

    /**
     * @return Scenario[]
     */
    public function getPassedScenarios()
    {
        return $this->passedScenarios;
    }

    /**
     * @return Feature[]
     */
    public function getFailedFeatures()
    {
        return $this->failedFeatures;
    }

    /**
     * @return Feature[]
     */
    public function getPassedFeatures()
    {
        return $this->passedFeatures;
    }

    /**
     * @return Step[]
     */
    public function getFailedSteps()
    {
        return $this->failedSteps;
    }

    /**
     * @return Step[]
     */
    public function getPassedSteps()
    {
        return $this->passedSteps;
    }

    /**
     * @return Step[]
     */
    public function getPendingSteps()
    {
        return $this->pendingSteps;
    }

    /**
     * @return Step[]
     */
    public function getSkippedSteps()
    {
        return $this->skippedSteps;
    }

    /**
     * @param BeforeExerciseCompleted $event
     */
    public function onBeforeExercise(BeforeExerciseCompleted $event)
    {
        $this->timer->start();
        Context\BehatFormatterContext::setUpHtmlSourceErrorOutput4ElkanBehatFormatter($this->getParameter('html_source_error_output'));

        $print = $this->renderer->renderBeforeExercise($this);
        $this->printer->write($print);
    }
    //</editor-fold>

    //<editor-fold desc="Event functions">

    /**
     * Returns parameter name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->parameters[$name];
    }

    /**
     * @param AfterExerciseCompleted $event
     */
    public function onAfterExercise(AfterExerciseCompleted $event)
    {

        $this->timer->stop();

        $print = $this->renderer->renderAfterExercise($this);
        $this->printer->writeln($print);
    }

    /**
     * @param BeforeSuiteTested $event
     */
    public function onBeforeSuiteTested(BeforeSuiteTested $event)
    {
        $this->currentSuite = new Suite();
        $this->currentSuite->setName($event->getSuite()->getName());

        $print = $this->renderer->renderBeforeSuite($this);
        $this->printer->writeln($print);
    }

    /**
     * @param AfterSuiteTested $event
     */
    public function onAfterSuiteTested(AfterSuiteTested $event)
    {

        $this->suites[] = $this->currentSuite;

        $print = $this->renderer->renderAfterSuite($this);
        $this->printer->writeln($print);
    }

    /**
     * @param BeforeFeatureTested $event
     */
    public function onBeforeFeatureTested(BeforeFeatureTested $event)
    {
        $feature = new Feature();
        $feature->setId($this->featureCounter);
        $this->featureCounter++;
        $feature->setName($event->getFeature()->getTitle());
        $feature->setDescription($event->getFeature()->getDescription());
        $feature->setTags($event->getFeature()->getTags());
        $feature->setFile($event->getFeature()->getFile());
        $this->currentFeature = $feature;

        $print = $this->renderer->renderBeforeFeature($this);
        $this->printer->writeln($print);
    }

    /**
     * @param AfterFeatureTested $event
     */
    public function onAfterFeatureTested(AfterFeatureTested $event)
    {
        $this->currentSuite->addFeature($this->currentFeature);
        if ($this->currentFeature->allPassed()) {
            $this->passedFeatures[] = $this->currentFeature;
        } else {
            $this->failedFeatures[] = $this->currentFeature;
        }

        $print = $this->renderer->renderAfterFeature($this);
        $this->printer->writeln($print);
    }

    /**
     * @param BeforeScenarioTested $event
     */
    public function onBeforeScenarioTested(BeforeScenarioTested $event)
    {
        $scenario = new Scenario();
        $eventScenario = $event->getScenario();

        $scenario->setName($eventScenario->getTitle());
        $scenario->setTags($eventScenario->getTags());
        $scenario->setLine($eventScenario->getLine());
        $this->currentScenario = $scenario;

        $print = $this->renderer->renderBeforeScenario($this);
        $this->printer->writeln($print);
    }

    /**
     * @param AfterScenarioTested $event
     */
    public function onAfterScenarioTested(AfterScenarioTested $event)
    {
        $scenarioPassed = $event->getTestResult()->isPassed();

        if ($scenarioPassed) {
            $this->passedScenarios[] = $this->currentScenario;
            $this->currentFeature->addPassedScenario();
        } else {
            $this->failedScenarios[] = $this->currentScenario;
            $this->currentFeature->addFailedScenario();
        }

        $this->currentScenario->setLoopCount(1);
        $this->currentScenario->setPassed($event->getTestResult()->isPassed());
        $this->currentFeature->addScenario($this->currentScenario);

        $print = $this->renderer->renderAfterScenario($this);
        $this->printer->writeln($print);
    }

    /**
     * @param BeforeOutlineTested $event
     */
    public function onBeforeOutlineTested(BeforeOutlineTested $event)
    {
        $scenario = new Scenario();
        $scenario->setName($event->getOutline()->getTitle());
        $scenario->setTags($event->getOutline()->getTags());
        $scenario->setLine($event->getOutline()->getLine());
        $this->currentScenario = $scenario;
        $this->currentExampleCount = 0;
        $this->currentExampleLines = array_map(function ($example) {
            return $example->getLine();
        }, $event->getOutline()->getExamples());

        $print = $this->renderer->renderBeforeOutline($this);
        $this->printer->writeln($print);
    }

    /**
     * @param AfterOutlineTested $event
     */
    public function onAfterOutlineTested(AfterOutlineTested $event)
    {
        $scenarioPassed = $event->getTestResult()->isPassed();

        if ($scenarioPassed) {
            $this->passedScenarios[] = $this->currentScenario;
            $this->currentFeature->addPassedScenario();
        } else {
            $this->failedScenarios[] = $this->currentScenario;
            $this->currentFeature->addFailedScenario();
        }

        $this->currentScenario->setLoopCount(count($event->getTestResult()));
        $this->currentScenario->setPassed($event->getTestResult()->isPassed());
        $this->currentFeature->addScenario($this->currentScenario);
        $this->currentExampleLines = null;

        $print = $this->renderer->renderAfterOutline($this);
        $this->printer->writeln($print);
    }

    /**
     * @param BeforeStepTested $event
     */
    public function onBeforeStepTested(BeforeStepTested $event)
    {
        $print = $this->renderer->renderBeforeStep($this);
        $this->printer->writeln($print);
    }


    /**
     * @param AfterStepTested $event
     */
    public function onAfterStepTested(AfterStepTested $event)
    {
        $result = $event->getTestResult();

        /** @var Step $step */
        $step = new Step();
        $step->setKeyword($event->getStep()->getKeyword());
        $step->setText(Context\BehatFormatterContext::transform($event->getStep()->getText()));
        $step->setLine($event->getStep()->getLine());
        $step->setArguments($event->getStep()->getArguments());
        $step->setResult($result);
        $step->setResultCode($result->getResultCode());

        //What is the result of this step ?
        if ($result instanceof UndefinedStepResult) {
            //pending step -> no definition to load
            $this->pendingSteps[] = $step;
        } elseif ($result instanceof SkippedStepResult) {
            //skipped step
            /** @var ExecutedStepResult $result */
            $step->setDefinition($result->getStepDefinition());
            $this->skippedSteps[] = $step;
        } elseif ($result instanceof ExecutedStepResult) {
            $step->setDefinition($result->getStepDefinition());
            $exception = $result->getException();
            if ($exception) {
                $step->setException($exception->getMessage());
                $this->failedSteps[] = $step;
            } else {
                $step->setOutput($result->getCallResult()->getStdOut());
                $this->passedSteps[] = $step;
            }
        }

        if ($step->isFailed() || ($step->getResult()->isPassed() && $step->getKeyword() === 'Then')) {
            $screenshot = self::buildScreenshotName(
                $event->getSuite()->getName(),
                $event->getFeature()->getTitle(),
                empty($this->currentExampleLines) ? $this->currentScenario->getLine() : $this->currentExampleLines[$this->currentExampleCount++],
                $event->getStep()->getLine()
            );

            if (file_exists(getcwd() . DIRECTORY_SEPARATOR . '.tmp_behatFormatter' . DIRECTORY_SEPARATOR . $screenshot)) {
                $screenshot = 'assets' . DIRECTORY_SEPARATOR . 'screenshots' . DIRECTORY_SEPARATOR . $screenshot;
                $step->setScreenshot($screenshot);
            }
        }
        $this->currentScenario->addStep($step);

        $print = $this->renderer->renderAfterStep($this);
        $this->printer->writeln($print);

    }
    //</editor-fold>

    /**
     * @param string $suite
     * @param string $feature
     * @param int    $scenarioLine
     * @param int    $stepLine
     *
     * @return string
     */
    public static function buildScreenshotName($suite, $feature, $scenarioLine, $stepLine = null)
    {
        return $suite . '.' . str_replace(' ', '_', $feature) . '.ScenarioLine' . $scenarioLine . '.StepLine' . $stepLine . '.png';
    }

    /**
     * @param $obj
     */
    public function dumpObj($obj)
    {
        ob_start();
        var_dump($obj);
        $result = ob_get_clean();
        $this->printText($result);
    }

    /**
     * @param $text
     */
    public function printText($text)
    {
        file_put_contents('php://stdout', $text);
    }
}
