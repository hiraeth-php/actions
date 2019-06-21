<?php

namespace Hiraeth\Actions;

use Hiraeth\Routing\ResolverInterface as Resolver;
use Hiraeth\Routing\UrlGeneratorInterface as UrlGenerator;
use Hiraeth\Session\ManagerInterface as SessionManager;
use Hiraeth\Templates\ManagerInterface as TemplateManager;
use Hiraeth\Templates\TemplateInterface as Template;

use Psr\Http\Message\StreamFactoryInterface as StreamFactory;
use Psr\Http\Message\ResponseInterface as Response;

use RuntimeException;

/**
 *
 */
abstract class AbstractAction implements ActionInterface
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
	protected $response = NULL;


	/**
	 *
	 */
	protected $sessionManager = NULL;


	/**
	 *
	 */
	protected $streamFactory = NULL;


	/**
	 *
	 */
	protected $templateManager = NULL;


	/**
	 *
	 */
	protected $urlGenerator = NULL;


	/**
	 *
	 */
	protected function redirect($location, array $params = array(), $type = 303): Response
	{
		if (!$this->urlGenerator) {
			throw new RuntimeException(sprintf(
				'Redirect is not supported, no implementation for "%s" is registered',
				UrlGenerator::class
			));
		}

		return $this->response($type, NULL, [
			'Location' => $this->urlGenerator->anchor($location, $params)
		]);
	}


	/**
	 *
	 */
	protected function response(int $status, string $content = NULL, array $headers = array()): Response
	{
		$response = $this->response;
		$stream   = $this->streamFactory->createStream($content);

		foreach ($headers as $header => $value) {
			$response = $response->withHeader($header, $value);
		}

		return $response->withStatus($status)->withBody($stream);
	}


	/**
	 *
	 */
	protected function template(string $template_path, array $data = array()): Template
	{
		if (!$this->templateManager) {
			throw new RuntimeException(sprintf(
				'Render is not supported, no implementation for "%s" is registered',
				TemplateManager::class
			));
		}

		return $this->templateManager->load($template_path, $data);
	}


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
	public function set(string $name, $value = NULL): object
	{
		$this->request = $this->request->withAttribute($name, $value);

		return $this;
	}


	/**
	 *
	 */
	public function setResolver(Resolver $resolver): object
	{
		$this->request  = $resolver->getRequest();
		$this->response = $resolver->getResponse();
		$this->resolver = $resolver;

		return $this;
	}


	/**
	 *
	 */
	public function setSessionManager(SessionManager $session_manager): object
	{
		$this->sessionManager = $session_manager;

		return $this;
	}


	/**
	 *
	 */
	public function setStreamFactory(StreamFactory $stream_factory): object
	{
		$this->streamFactory = $stream_factory;

		return $this;
	}


	/**
	 *
	 */
	public function setTemplateManager(TemplateManager $template_manager): object
	{
		$this->templateManager = $template_manager;

		return $this;
	}


	/**
	 *
	 */
	public function setUrlGenerator(UrlGenerator $url_generator): object
	{
		$this->urlGenerator = $url_generator;

		return $this;
	}
}
