<?php

namespace Hiraeth\Actions;

use Hiraeth\Routing;
use Psr\Http\Message\StreamFactoryInterface as StreamFactory;


/**
 *
 */
interface Action
{
	/**
	 *
	 */
	public function setResolver(Routing\Resolver $resolver): Action;


	/**
	 *
	 */
	public function setStreamFactory(StreamFactory $stream_factory): Action;
}
