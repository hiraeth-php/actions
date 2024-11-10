<?php

namespace Hiraeth\Actions;

use Hiraeth;

/**
 * {@inheritDoc}
 */
class ApplicationProvider implements Hiraeth\Provider
{
	/**
	 * {@inheritDoc}
	 */
	static public function getInterfaces(): array
	{
		return [
			Hiraeth\Application::class
		];
	}


	/**
	 * {@inheritDoc}
	 *
	 * @param Hiraeth\Application $instance The application instance
	 */
	public function __invoke($instance, Hiraeth\Application $app): object
	{
		if (!$app->has(Hiraeth\Routing\Resolver::class)) {
			$app->void(RoutingTrait::class, $app::DEF_TRAIT);
			$app->void(RoutingInterface::class, $app::DEF_IFACE);
		}

		if (!$app->has(Hiraeth\Session\Manager::class)) {
			$app->void(SessionTrait::class, $app::DEF_TRAIT);
			$app->void(SessionInterface::class, $app::DEF_IFACE);
		}

		if (!$app->has(Hiraeth\Templates\Manager::class)) {
			$app->void(TemplatesTrait::class, $app::DEF_TRAIT);
			$app->void(TemplatesInterface::class, $app::DEF_IFACE);
		}

		return $instance;
	}
}
