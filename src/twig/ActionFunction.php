<?php

namespace Hiraeth\Actions\Twig;

use RuntimeException;
use Hiraeth\Application;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class ActionFunction
{
	/**
	 * @var Application
	 */
	protected $app;

	/**
	 *
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}


	/**
	 * @param array<string, mixed> $context
	 * @param class-string $class
	 * @param array<string, mixed> $parameters
	 */
	public function __invoke(array &$context, string $class, array $parameters = []): array|callable
	{
		$class = str_replace(':', '\\', $class);

		if (!$this->app->has($class)) {
			return function() use ($class) {
				if (!$this->app->isDebugging()) {
					throw new RuntimeException(sprintf(
						'Cannot mock result when not debugging, class "%s" is not found.',
						$class
					));
				}

				return [];
			};
		}

		/**
		 * @var AbstractAction
		 */
		$action   = $this->app->get($class);
		$response = $this->app->get(ResponseInterface::class);
		$context  = array_merge(
			$context,
			$action->call($context['request'], $response, $parameters + (
				$context['parameters'] ?? []
			)) ?: []
		);

		return $context;
	}
}
