<?php

namespace Hiraeth\Actions;

use Hiraeth;

use Hiraeth\Routing\ResolverInterface as Resolver;
use Hiraeth\Routing\UrlGeneratorInterface as UrlGenerator;

use Hiraeth\Templates\TemplateManagerInterface as TemplateManager;


/**
 * Providers add additional dependencies or configuration for objects of certain interfaces.
 */
class ActionProvider implements Hiraeth\Provider
{
	/**
	 *
	 */
	protected $app = NULL;


	/**
	 *
	 */
	public function __construct(Hiraeth\Application $app)
	{
		$this->app = $app;
	}


	/**
	 * Get the interfaces for which the provider operates.
	 *
	 * @access public
	 * @return array A list of interfaces for which the provider operates
	 */
	static public function getInterfaces(): array
	{
		return [
			ActionInterface::class
		];
	}


	/**
	 * Prepare the instance.
	 *
	 * @access public
	 * @return Object The prepared instance
	 */
	public function __invoke(object $instance, Hiraeth\Broker $broker): object
	{
		$instance->setResolver($broker->make(Resolver::class));

		if ($this->app->has(UrlGenerator::class)) {
			$instance->setUrlGenerator($broker->make(UrlGenerator::class));
		}

		if ($this->app->has(TemplateManager::class)) {
			$instance->setTemplateManager($broker->make(TemplateManager::class));
		}

		return $instance;
	}
}
