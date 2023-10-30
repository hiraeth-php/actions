<?php

namespace Hiraeth\Actions;

use Hiraeth\Routing;
use Psr\Http\Message\StreamFactoryInterface as StreamFactory;


/**
 * A basic action interface
 */
interface Action
{
	/**
	 * Set the resolver (and implicitely the default request/response)
	 */
	public function setResolver(Routing\Resolver $resolver): self;


	/**
	 * Set a stream factory for use with `response()`
	 */
	public function setStreamFactory(StreamFactory $stream_factory): self;
}
