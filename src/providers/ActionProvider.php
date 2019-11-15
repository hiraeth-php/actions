<?php

namespace Hiraeth\Actions;

use Hiraeth;

use Hiraeth\Routing;
use Hiraeth\Session;
use Hiraeth\Templates;

use Psr\Http\Message\StreamFactoryInterface as StreamFactory;

/**
 * Provides required and optional dependencies for actions post-instantiation via setter-injection
 */
class ActionProvider implements Hiraeth\Provider
{
	/**
	 * {@inheritDoc}
	 */
	static public function getInterfaces(): array
	{
		return [
			Action::class
		];
	}


	/**
	 * {@inheritDoc}
	 */
	public function __invoke(object $instance, Hiraeth\Application $app): object
	{
		$instance->setResolver($app->get(Routing\Resolver::class));
		$instance->setStreamFactory($app->get(StreamFactory::class));

		if ($instance instanceof AbstractAction) {
			if ($app->has(Session\Manager::class)) {
				$instance->setSessionManager($app->get(Session\Manager::class));
			}

			if ($app->has(Templates\Manager::class)) {
				$instance->setTemplatesManager($app->get(Templates\Manager::class));
			}

			if ($app->has(Routing\UrlGenerator::class)) {
				$instance->setUrlGenerator($app->get(Routing\UrlGenerator::class));
			}
		}

		return $instance;
	}
}
