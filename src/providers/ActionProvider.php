<?php

namespace Hiraeth\Actions;

use Hiraeth;

use Hiraeth\Routing;
use Psr\Http\Message\StreamFactoryInterface as StreamFactory;

/**
 * {@inheritDoc}
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
	 *
	 * @param Action $instance
	 */
	public function __invoke(object $instance, Hiraeth\Application $app): object
	{
		$instance->setStreamFactory($app->get(StreamFactory::class));

		if ($instance instanceof AbstractAction) {
			if ($app->has(Routing\Resolver::class)) {
				$instance->setResolver($app->get(Routing\Resolver::class));
			}

			if ($app->has(Routing\UrlGenerator::class)) {
				$instance->setUrlGenerator($app->get(Routing\UrlGenerator::class));
			}
		}

		return $instance;
	}
}
