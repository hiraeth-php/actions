<?php

namespace Hiraeth\Actions;

use Hiraeth\Routing\Resolver;
use Psr\Http\Message\StreamFactoryInterface as StreamFactory;


/**
 *
 */
interface Action
{
	/**
	 *
	 */
	public function setResolver(Resolver $resolver): Action;


	/**
	 *
	 */
	public function setStreamFactory(StreamFactory $stream_factory): Action;
}
