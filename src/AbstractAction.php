<?php

namespace Hiraeth\Actions;

use Json;
use Hiraeth\Http;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ReflectionMethod;
use RuntimeException;
use Stringable;

/**
 * Provides simple helper methods and ingestion methods action resolution and response
 */
abstract class AbstractAction implements Http\Action, ExtensibleInterface
{
	use Http\ActionTrait;
	use ExtensibleTrait;

	/**
	 * @var mixed[]
	 */
	protected $data = [];

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;


	/**
	 * @param array<string, mixed> $properties
	 * @param array<string, mixed> $parameters
	 * @return ?array<string, mixed>
	 */
	public function call(array $properties = [], array $parameters = []): ?array
	{
		foreach ($properties as $key => $value) {
			$this->$key = $value;
		}

		if (!is_callable($this)) {
			throw new RuntimeException(sprintf(
				'Unable to execute call on class "%s", must implement __invoke()',
				static::class
			));
		}

		$method    = new ReflectionMethod($this, '__invoke');
		$arguments = array_map(
			fn($argument) => $argument->getName(),
			$method->getParameters()
		);

		foreach ($parameters as $key => $value) {
			if (!in_array($key, $arguments)) {
				unset($parameters[$key]);
			}
		}

		$result = call_user_func_array($this, $parameters);

		if ($result instanceof Response) {
			throw Http\Interrupt::response($result);
		}

		return $result;
	}


	/**
	 * Get data with a default that will be used for implicit type casting
	 *
	 * @return mixed
	 */
	public function get(?string $name = NULL, mixed $default = NULL): mixed
	{
		$this->load();

		if (!$name) {
			return $this->data;
		}

		if (!array_key_exists($name, $this->data)) {
			return $default;
		}

		$value = $this->data[$name];

		if (is_object($default)) {
			if (!is_object($value)) {
				$class = $default::class;
				$value = new $class($value);
			}

		} elseif (!is_null($default)) {
			settype($value, gettype($default));

		}

		return $value;
	}


	/**
	 * Determine whether or not the data has a value
	 */
	public function has(?string $name = NULL): bool
	{
		$this->load();

		if (!$name) {
			return (bool) $this->data;
		}

		return array_key_exists($name, $this->data);
	}


	/**
	 *
	 */
	public function init(int $code)
	{
		if (property_exists($this, 'resolver')) {
			$this->resolver->init($code);
		}

		if (property_exists($this, 'template')) {
			$this->template->set('_status_', $code);
		}
	}


	/**
	 * Set a custom value in the data
	 */
	public function set(string $name, mixed $value = NULL): static
	{
		$this->data[$name] = $value;

		return $this;
	}


	/**
	 * {@inheritDoc}
	 */
	protected function getTemplateContext(): array
	{
		return [
			'request'  => $this->request,
			'response' => $this->response
		];
	}

	/**
	 * Load the request data for use with `get()` in order of: query, body, files, attributes
	 */
	protected function load(bool $strict = FALSE): self
	{
		if (!$this->data) {
			if ($this->request->getMethod() == 'GET') {
				$this->data = array_replace_recursive(
					$strict ? [] : (array) $this->request->getParsedBody(),
					$strict ? [] : (array) $this->request->getUploadedFiles(),
					(array) $this->request->getQueryParams(),
					(array) $this->request->getAttributes()
				);

			} else {
				$this->data = array_replace_recursive(
					$strict ? [] : (array) $this->request->getQueryParams(),
					(array) $this->request->getParsedBody(),
					(array) $this->request->getUploadedFiles(),
					(array) $this->request->getAttributes()
				);
			}
		}

		return $this;
	}


	/**
	 *
	 */
	protected function object(mixed $data): Json\Normalizer
	{
		return Json\Prepare($data);
	}


	/**
	 * Get a PSR-7 response object constructed as a redirect using the url generator
	 *
	 * @param array<string, mixed> $params
	 */
	protected function redirect(mixed $location, array $params = []): Response
	{
		$status   = $this->response->getStatusCode();
		$location = $this->route(...func_get_args());

		if (!in_array(floor($status / 100), [3])) {
			$status = 303;
		}

		return $this->response($status, NULL, [
			'Location' => $location
		]);
	}


	/**
	 * Get a PSR-7 response with optional content and headers
	 *
	 * @param array<string, string> $headers
	 */
	protected function response(int $status, Response|Stringable|string|null $content = NULL, array $headers = []): Response
	{
		$response = !$content instanceof Response
			? $this->response
			: $content
		;

		foreach ($headers as $header => $value) {
			$response = $response->withHeader($header, $value);
		}

		if (is_string($content) || $content instanceof Stringable) {
			$stream   = $this->streamFactory->createStream((string) $content);
			$response = $response->withBody($stream);

			if (!isset(array_change_key_case($headers)['content-type'])) {
				$finfo = finfo_open();

				if ($finfo) {
					$mime_type = finfo_buffer($finfo, $stream, FILEINFO_MIME_TYPE);

					finfo_close($finfo);
				}

				if (empty($mime_type)) {
					$mime_type = 'text/plain;charset=UTF-8';
				}

				$response  = $response->withHeader('Content-Type', $mime_type);
			}
		}

		if (!isset(array_change_key_case($headers)['content-length'])) {
			$response = $response->withHeader(
				'Content-Length', (string) $response->getBody()->getSize()
			);
		}

		return $response->withStatus($status);
	}


	/**
	 * @param array<string, mixed> $params
	 */
	protected function route(mixed $location, array $params = []): string
	{
		return $this->urlGenerator->call(...func_get_args());
	}
}
