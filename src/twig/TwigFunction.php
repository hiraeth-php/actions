<?php

namespace Hiraeth\Actions;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class TwigFunction
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;


	/**
	 *
	 */
	public function __construct(ContainerInterface $app)
	{
		$this->container = $app;
	}


	/**
	 * @param array<string, mixed> $context
	 * @param class-string $class
	 * @param array<string, mixed> $parameters
	 */
	public function __invoke(array &$context, string $class, array $parameters = []): void
	{
		/**
		 * @var AbstractAction
		 */
		$action   = $this->container->get(str_replace(':', '\\', $class));
		$response = $this->container->get(ResponseInterface::class);

		if (isset($context['route'])) {
			$parameters += $context['route']->getParameters();
		} else {
			$parameters += $context['parameters'] ?? [];
		}

		$context = array_merge(
			$context,
			$action->call($context['request'], $response, $parameters)
		);
	}
}
