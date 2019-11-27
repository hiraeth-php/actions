<?php

namespace Hiraeth\Actions;

use Hiraeth\Routing;
use Hiraeth\Session;
use Hiraeth\Templates;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamFactoryInterface as StreamFactory;

use RuntimeException;

/**
 *
 */
abstract class AbstractAction implements Action
{
	/**
	 *
	 */
	protected $request = NULL;


	/**
	 *
	 */
	protected $resolver = NULL;


	/**
	 *
	 */
	protected $session = NULL;


	/**
	 *
	 */
	protected $streamFactory = NULL;


	/**
	 *
	 */
	protected $templates = NULL;


	/**
	 *
	 */
	protected $urlGenerator = NULL;


	/**
	 *
	 */
	public function get(string $name = NULL, $default = NULL)
	{
		if (!$name) {
			return $this->request->getAttributes()
				+  $this->request->getUploadedFiles()
				+  $this->request->getParsedBody()
				+  $this->request->getQueryParams();
		}

		if (array_key_exists($name, $this->request->getAttributes())) {
			$value = $this->request->getAttributes()[$name];

		} elseif (array_key_exists($name, $this->request->getUploadedFiles())) {
			$value = $this->request->getUploadedFiles()[$name];

		} elseif (array_key_exists($name, $this->request->getParsedBody())) {
			$value = $this->request->getParsedBody()[$name];

		} elseif (array_key_exists($name, $this->request->getQueryParams())) {
			$value = $this->request->getQueryParams()[$name];

		} else {
			return $default;
		}

		if ($default !== NULL && !is_object($default)) {
			settype($value, gettype($default));
		}

		return $value;
	}


	/**
	 *
	 */
	public function has(string $name): bool
	{
		return array_key_exists($name, $this->request->getAttributes())
			|| array_key_exists($name, $this->request->getUploadedFiles())
			|| array_key_exists($name, $this->request->getParsedBody())
			|| array_key_exists($name, $this->request->getQueryParams());
	}


	/**
	 *
	 */
	public function set(string $name, $value = NULL): Action
	{
		$this->request = $this->request->withAttribute($name, $value);

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
	public function setSessionManager(Session\Manager $session): Action
	{
		$this->session = $session;

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
	public function setTemplatesManager(Templates\Manager $templates): Action
	{
		$this->templates = $templates;

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
	protected function flash($type, $message, array $context = array()): Action
	{
		if (!$this->session) {
			throw new RuntimeException(sprintf(
				'Flash is not supported, no implementation for "%s" is registered',
				Session\Manager::class
			));
		}

		if ($this->templates && $message[0] == '@') {
			$message = $this->templates->load($message, ['type' => $type] + $context)->render();
		}

		$this->session->getSegment('messages')->setFlashNow($type, $message);
		$this->session->getSegment('context')->setFlashNow($type, $context);

		return $this;
	}


	/**
	 *
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
	 *
	 */
	protected function response(int $status, string $content = NULL, array $headers = array()): Response
	{
		$response = $this->resolver->getResponse();
		$stream   = $this->streamFactory->createStream($content ?: '');

		foreach ($headers as $header => $value) {
			$response = $response->withHeader($header, $value);
		}

		return $response->withStatus($status)->withBody($stream);
	}


	/**
	 *
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
