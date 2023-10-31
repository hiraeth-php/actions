<?php

namespace Hiraeth\Actions;

use Hiraeth\Routing;
use Hiraeth\Session;
use Hiraeth\Templates;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamFactoryInterface as StreamFactory;

use RuntimeException;

/**
 * Provides simple helper methods and ingestion methods action resolution and response
 */
abstract class AbstractAction implements Action, Templates\ManagedInterface, Session\ManagedInterface
{
	use Templates\ManagedTrait;
	use Session\ManagedTrait;
	use Session\FlashTrait;

	/**
	 * @var mixed[]
	 */
	protected $data = array();

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Routing\Resolver
	 */
	protected $resolver;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var ?StreamFactory
	 */
	protected $streamFactory;

	/**
	 * @var ?Routing\UrlGenerator
	 */
	protected $urlGenerator;


	/**
	 *
	 */
	public function call(Request $request, mixed ...$params): void
	{
		$this->request = $request;

		if (is_callable($this)) {
			$result = call_user_func_array($this, ...$params);

			if ($result instanceof Response) {
				throw Routing\Interrupt::response($result);;
			}
		}

		throw new RuntimeException(sprintf(
			'Unable to execute call on class "%s", must implement __invoke()',
			static::class
		));
	}


	/**
	 * Get data with a default that will be used for implicit type casting
	 *
	 * @return mixed
	 */
	public function get(string $name = NULL, mixed $default = NULL): mixed
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
				$class = get_class($default);
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
	public function has(string $name = NULL): bool
	{
		$this->load();

		if (!$name) {
			return (bool) $this->data;
		}

		return array_key_exists($name, $this->data);
	}


	/**
	 * Set a custom value in the data
	 */
	public function set(string $name, mixed $value = NULL): Action
	{
		$this->data[$name] = $value;

		return $this;
	}


	/**
	 * Set the resolver (and implicitely the default request/response)
	 */
	public function setResolver(Routing\Resolver $resolver): Action
	{
		$this->request  = $resolver->getRequest();
		$this->response = $resolver->getResponse();
		$this->resolver = $resolver;

		return $this;
	}


	/**
	 * Set a stream factory for use with `response()`
	 */
	public function setStreamFactory(StreamFactory $stream_factory): Action
	{
		$this->streamFactory = $stream_factory;

		return $this;
	}


	/**
	 * Set a url generator for use with `redirect()`
	 */
	public function setUrlGenerator(Routing\UrlGenerator $url_generator): Action
	{
		$this->urlGenerator = $url_generator;

		return $this;
	}


	/**
	 * Load the request data for use with `get()` in order of: query, body, files, attributes
	 */
	protected function load(): self
	{
		if (!$this->data) {
			$this->data = array_replace_recursive(
				(array) $this->request->getQueryParams(),
				(array) $this->request->getParsedBody(),
				(array) $this->request->getUploadedFiles(),
				(array) $this->request->getAttributes()
			);
		}

		return $this;
	}


	/**
	 * Get a PSR-7 response object constructed as a redirect using the url generator
	 *
	 * @param array<string, mixed> $params
	 */
	protected function redirect(mixed $location, array $params = array()): Response
	{
		if (!$this->urlGenerator) {
			throw new RuntimeException(sprintf(
				'Redirect is not supported, no implementation for "%s" is registered',
				Routing\UrlGenerator::class
			));
		}

		return $this->response(303, NULL, [
			'Location' => ($this->urlGenerator)(...func_get_args())
		]);
	}


	/**
	 * Get a PSR-7 response with optional content and headers
	 *
	 * @param array<string, string> $headers
	 */
	protected function response(int $status, string $content = NULL, array $headers = array()): Response
	{
		$response = $this->response;

		foreach ($headers as $header => $value) {
			$response = $response->withHeader($header, $value);
		}

		if ($content) {
			$stream   = $this->streamFactory->createStream((string) $content);
			$response = $response->withBody($stream);

			if (!isset(array_change_key_case($headers)['content-type'])) {
				if ($finfo = finfo_open()) {
					$mime_type = finfo_buffer($finfo, $stream, FILEINFO_MIME_TYPE);
					finfo_close($finfo);
				}

				if (empty($mime_type)) {
					$mime_type = 'text/plain';
				}

				$response = $response->withHeader('Content-Type', $mime_type);
			}

			if (!isset(array_change_key_case($headers)['content-length'])) {
				$response = $response->withHeader('Content-Length', (string) $stream->getSize());
			}
		}

		return $response->withStatus($status);
	}


	/**
	 * Get a loaded template with data
	 *
	 * @param array<string, mixed> $data
	 */
	protected function template(string $template_path, array $data = array()): Templates\Template
	{
		if (!$this->templates) {
			throw new RuntimeException(sprintf(
				'Render is not supported, no implementation for "%s" is registered',
				Templates\Manager::class
			));
		}

		return $this->templates->load($template_path, $data + [
			'request' => $this->request
		]);
	}
}
