<?php
/**
 * Created by PhpStorm.
 * User: nealv
 * Date: 12/08/15
 * Time: 10:45
 */

namespace elkan\BehatFormatter\Renderer;

use elkan\BehatFormatter\Formatter\BehatFormatter;

interface RendererInterface {

	/**
	 * Renders before an exercice.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeExercise(BehatFormatter $obj): string;

	/**
	 * Renders after an exercice.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterExercise(BehatFormatter $obj): string;

	/**
	 * Renders before a suite.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeSuite(BehatFormatter $obj): string;

	/**
	 * Renders after a suite.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterSuite(BehatFormatter $obj): string;

	/**
	 * Renders before a feature.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeFeature(BehatFormatter $obj): string;

	/**
	 * Renders after a feature.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterFeature(BehatFormatter $obj): string;

	/**
	 * Renders before a scenario.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeScenario(BehatFormatter $obj): string;

	/**
	 * Renders after a scenario.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterScenario(BehatFormatter $obj): string;

	/**
	 * Renders before an outline.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeOutline(BehatFormatter $obj): string;

	/**
	 * Renders after an outline.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterOutline(BehatFormatter $obj): string;

	/**
	 * Renders before a step.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderBeforeStep(BehatFormatter $obj): string;

	/**
	 * Renders after a step.
	 * @param BehatFormatter $obj
	 * @return string  : HTML generated
	 */
    public function renderAfterStep(BehatFormatter $obj): string;

    /**
     * To include CSS
     * @return string  : HTML generated
     */
    public function getCSS(): string;

    /**
     * To include JS
     * @return string  : HTML generated
     */
    public function getJS(): string;
}
