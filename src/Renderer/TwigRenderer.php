<?php
/**
 * Twig renderer for Behat report
 */

namespace elkan\BehatFormatter\Renderer;

use elkan\BehatFormatter\Formatter\BehatFormatter;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_Filter_Function;

class TwigRenderer implements RendererInterface {

	/**
	 * Renders before an exercice.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeExercise(BehatFormatter $obj): string
    {
        return '';
    }

	/**
	 * Renders after an exercice.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 * @throws \Twig\Error\LoaderError
	 * @throws \Twig\Error\RuntimeError
	 * @throws \Twig\Error\SyntaxError
	 */
    public function renderAfterExercise(BehatFormatter $obj): string
    {

        $templatePath = dirname(__FILE__).'/../../templates';
        $loader = new Twig_Loader_Filesystem($templatePath);
        $twig = new Twig_Environment($loader, array());
        $twig->addFilter('var_dump', new Twig_Filter_Function('var_dump'));
        $print = $twig->render('index.html.twig',
            array(
                'projectname'           => $obj->getProjectName(),
                'projectdescription'    => $obj->getProjectDescription(),
                'projectimage'          => $obj->getProjectImage(),
                'suites'                => $obj->getSuites(),
                'failedScenarios'       => $obj->getFailedScenarios(),
                'passedScenarios'       => $obj->getPassedScenarios(),
                'failedSteps'           => $obj->getFailedSteps(),
                'passedSteps'           => $obj->getPassedSteps(),
                'failedFeatures'        => $obj->getFailedFeatures(),
                'passedFeatures'        => $obj->getPassedFeatures(),
                'printStepArgs'         => $obj->getPrintArguments(),
                'printStepOuts'         => $obj->getPrintOutputs(),
                'printLoopBreak'        => $obj->getPrintLoopBreak(),
                'printShowTags'         => $obj->getPrintShowTags(),
                'buildDate'             => $obj->getBuildDate(),
            )
        );
        $obj->copyTempScreenshotDirectory();
        return $print;

    }

	/**
	 * Renders before a suite.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeSuite(BehatFormatter $obj): string
    {
        return '';
    }

	/**
	 * Renders after a suite.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterSuite(BehatFormatter $obj): string
    {
        return '';
    }

	/**
	 * Renders before a feature.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeFeature(BehatFormatter $obj): string
    {
        return '';
    }

	/**
	 * Renders after a feature.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterFeature(BehatFormatter $obj): string
    {
        return '';
    }

	/**
	 * Renders before a scenario.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeScenario(BehatFormatter $obj): string
    {
        return '';
    }

	/**
	 * Renders after a scenario.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterScenario(BehatFormatter $obj): string
    {
        return '';
    }

	/**
	 * Renders before an outline.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeOutline(BehatFormatter $obj): string
    {
        return '';
    }

	/**
	 * Renders after an outline.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterOutline(BehatFormatter $obj): string
    {
        return '';
    }

	/**
	 * Renders before a step.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeStep(BehatFormatter $obj): string
    {
        return '';
    }

	/**
	 * Renders after a step.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterStep(BehatFormatter $obj): string
    {
        return '';
    }

    /**
     * To include CSS
     * @return string  : HTML generated
     */
    public function getCSS(): string
    {
        return '';

    }

    /**
     * To include JS
     * @return string  : HTML generated
     */
    public function getJS(): string
    {
        return '';
    }
}
