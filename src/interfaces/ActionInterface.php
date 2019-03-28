<?php

namespace Hiraeth\Actions;

use Hiraeth\Routing\ResolverInterface as Resolver;


/**
 *
 */
interface ActionInterface
{
	/**
	 *
	 */
	public function setResolver(Resolver $resolver): object;
}
