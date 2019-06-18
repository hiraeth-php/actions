<?php

namespace Hiraeth\Actions;

use Hiraeth\Routing\ResolverInterface as Resolver;
use Psr\Http\Message\StreamFactoryInterface;


/**
 *
 */
interface ActionInterface
{
	/**
	 *
	 */
	public function setResolver(Resolver $resolver): object;


	/**
	 *
	 */
	public function setStreamFactory(StreamFactoryInterface $stream_factory): object;
}
