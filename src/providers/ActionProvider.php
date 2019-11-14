<?php

namespace Hiraeth\Actions;

use Hiraeth;

use Hiraeth\Routing;
use Hiraeth\Session;
use Hiraeth\Templates;

use Psr\Http\Message\StreamFactoryInterface as StreamFactory;


/**
 * Providers add additional dependencies or configuration for objects of certain interfaces.
 */
class ActionProvider implements Hiraeth\Provider
{
	/**
	 * Get the interfaces for which the provider operates.
	 *
	 * @access public
	 * @return array A list of interfaces for which the provider operates
	 */
	static public function getInterfaces(): array
	{
		return [
			Action::class
		];
	}


	/**
	 * Prepare the instance.
	 *
	 * @access public
	 * @param Hiraeth\Application $app The application instance for which the delegate operates
	 * @return Object The prepared instance
	 */
	public function __invoke(object $instance, Hiraeth\Application $app): object
	{
		$instance->setResolver($app->get(Routing\Resolver::class));
		$instance->setStreamFactory($app->get(StreamFactory::class));

		if ($app->has(Session\Manager::class)) {
			$instance->setSessionManager($app->get(Session\Manager::class));
		}

		if ($app->has(Templates\Manager::class)) {
			$instance->setTemplateManager($app->get(Templates\Manager::class));
		}

		if ($app->has(Routing\UrlGenerator::class)) {
			$instance->setUrlGenerator($app->get(Routing\UrlGenerator::class));
		}


		return $instance;
	}
}
