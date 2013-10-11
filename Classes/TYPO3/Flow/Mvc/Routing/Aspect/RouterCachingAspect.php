<?php
namespace TYPO3\Flow\Mvc\Routing\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * Caching of findMatchResults() and resolve() calls on the web Router.
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class RouterCachingAspect {

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\RouterCachingService
	 * @Flow\Inject
	 */
	protected $routerCachingService;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * Around advice
	 *
	 * @Flow\Around("method(TYPO3\Flow\Mvc\Routing\Router->findMatchResults())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return array the $routeValues returned from Router::findMatchResults() or the cached results if existent
	 */
	public function cacheMatchingCall(JoinPointInterface $joinPoint) {
		/** @var $httpRequest \TYPO3\Flow\Http\Request */
		$httpRequest = $joinPoint->getMethodArgument('httpRequest');
		$cachedMatchResults = $this->routerCachingService->getCachedMatchResults($httpRequest);
		if ($cachedMatchResults !== FALSE) {
			return $cachedMatchResults;
		}

		$matchResults = $joinPoint->getAdviceChain()->proceed($joinPoint);
		$matchedRoute = $joinPoint->getProxy()->getLastMatchedRoute();

		if ($matchedRoute !== NULL) {
			$this->systemLogger->log(sprintf('Router route(): Route "%s" matched the path "%s".', $matchedRoute->getName(), $httpRequest->getRelativePath()), LOG_DEBUG);
		} else {
			$this->systemLogger->log(sprintf('Router route(): No route matched the route path "%s".', $httpRequest->getRelativePath()), LOG_NOTICE);
		}
		if ($matchResults !== NULL) {
			$this->routerCachingService->storeMatchResults($httpRequest, $matchResults);
		}
		return $matchResults;
	}

	/**
	 * Around advice
	 *
	 * @Flow\Around("method(TYPO3\Flow\Mvc\Routing\Router->resolve())")
	 * @param JoinPointInterface $joinPoint The current join point
	 * @return string the request path returned by Router::resolve() or the cached result if existent
	 */
	public function cacheResolveCall(JoinPointInterface $joinPoint) {
		$routeValues = $joinPoint->getMethodArgument('routeValues');
		$cachedResolvedUriPath = $this->routerCachingService->getCachedResolvedUriPath($routeValues);
		if ($cachedResolvedUriPath !== FALSE) {
			return $cachedResolvedUriPath;
		}

		$matchingUriPath = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($matchingUriPath !== NULL) {
			$this->routerCachingService->storeResolvedUriPath($matchingUriPath, $routeValues);
		}
		return $matchingUriPath;
	}

}
?>