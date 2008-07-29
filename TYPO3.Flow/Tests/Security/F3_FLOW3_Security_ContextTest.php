<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 */

/**
 * Testcase for the security context
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_ContextTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthenticationTokensReturnsOnlyTokensActiveForThisRequest() {
		$mockConfigurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array(), array(), '', FALSE);
		$configuration = new F3_FLOW3_Configuration_Container();
		$mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($configuration));
		$request = $this->getMock('F3_FLOW3_MVC_Request');

		$matchingRequestPattern = $this->getMock('F3_FLOW3_Security_RequestPatternInterface', array(), array(), 'matchingRequestPattern');
		$matchingRequestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3_FLOW3_Security_RequestPatternInterface', array(), array(), 'notMatchingRequestPattern');
		$notMatchingRequestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3_FLOW3_Security_RequestPatternInterface', array(), array(), 'abstainingRequestPattern');
		$abstainingRequestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$token1 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface', array(), array(), 'authenticationToken1');
		$token1->expects($this->once())->method('hasRequestPattern')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRequestPattern')->will($this->returnValue($matchingRequestPattern));

		$token2 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface', array(), array(), 'authenticationToken2');
		$token2->expects($this->once())->method('hasRequestPattern')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPattern');

		$token3 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface', array(), array(), 'authenticationToken3');
		$token3->expects($this->once())->method('hasRequestPattern')->will($this->returnValue(TRUE));
		$token3->expects($this->once())->method('getRequestPattern')->will($this->returnValue($notMatchingRequestPattern));

		$token4 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface', array(), array(), 'authenticationToken4');
		$token4->expects($this->once())->method('hasRequestPattern')->will($this->returnValue(TRUE));
		$token4->expects($this->once())->method('getRequestPattern')->will($this->returnValue($abstainingRequestPattern));

		$securityContext = new F3_FLOW3_Security_Context($mockConfigurationManager);
		$securityContext->setAuthenticationTokens(array($token1, $token2, $token3, $token4));
		$securityContext->setRequest($request);

		$this->assertEquals(array($token1, $token2), $securityContext->getAuthenticationTokens());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateAllTokensIsSetCorrectlyFromConfiguration() {
		$mockConfigurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array(), array(), '', FALSE);
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->security->authentication->authenticateAllTokens = TRUE;

		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue($configuration));
		$securityContext = new F3_FLOW3_Security_Context($mockConfigurationManager);

		$this->assertTrue($securityContext->authenticateAllTokens());

	}
}
?>