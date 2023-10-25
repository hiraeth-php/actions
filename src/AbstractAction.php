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
 *
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
	 * @var Request|null
	 */
	protected $request = NULL;


	/**
	 * @var Routing\Resolver|null
	 */
	protected $resolver = NULL;


	/**
	 * @var StreamFactory|null
	 */
	protected $streamFactory = NULL;


	/**
	 * @var Routing\UrlGenerator|null
	 */
	protected $urlGenerator = NULL;


	/**
	 * @param mixed $default
	 * @return mixed|mixed[]
	 */
	public function get(string $name = NULL, $default = NULL)
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
	 *
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
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function set(string $name, $value = NULL): Action
	{
		$this->data[$name] = $value;

		return $this;
	}


	/**
	 *
	 */
	public function setResolver(Routing\Resolver $resolver): Action
	{
		$this->request  = $resolver->getRequest();
		$this->resolver = $resolver;

		return $this;
	}


	/**
	 *
	 */
	public function setStreamFactory(StreamFactory $stream_factory): Action
	{
		$this->streamFactory = $stream_factory;

		return $this;
	}


	/**
	 *
	 */
	public function setUrlGenerator(Routing\UrlGenerator $url_generator): Action
	{
		$this->urlGenerator = $url_generator;

		return $this;
	}


	/**
	 *
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
	 * @param mixed $location
	 * @param mixed[] $params
	 */
	protected function redirect($location, array $params = array()): Response
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
	 * @param int $status
	 * @param string $content
	 * @param array<string, string> $headers
	 */
	protected function response(int $status, string $content = NULL, array $headers = array()): Response
	{
		$response = $this->resolver->getResponse();

		foreach ($headers as $header => $value) {
			$response = $response->withHeader($header, $value);
		}

		if ($content) {
			$stream   = $this->streamFactory->createStream($content ?: '');
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
	 * @param string $template_path
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
