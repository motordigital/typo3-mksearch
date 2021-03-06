<?php
namespace DMK\Mksearch\Tests\ViewHelpers\Format;

/***************************************************************
 * Copyright notice
 *
 * (c) 2016 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
\tx_rnbase::load('tx_rnbase_util_TYPO3');
\tx_rnbase::load('tx_mksearch_tests_Testcase');

/**
 * DMK\Mksearch\Tests\ViewHelpers$CObjectViewHelperTest
 *
 * @package         TYPO3
 * @subpackage      mksearch
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class HtmlViewHelperTest extends \tx_mksearch_tests_Testcase
{
    /**
     * {@inheritDoc}
     * @see tx_mksearch_tests_Testcase::setUp()
     */
    protected function setUp()
    {
        if (\tx_rnbase_util_TYPO3::isTYPO80OrHigher()) {
            $this->markTestSkipped('Not required for TYPO3 8 or higher');
        }
    }
    /**
     * {@inheritDoc}
     * @see tx_mksearch_tests_Testcase::tearDown()
     */
    protected function tearDown()
    {
        $property = new \ReflectionProperty('tx_mksearch_service_internal_Index', 'indexingInProgress');
        $property->setAccessible(true);
        $property->setValue(null, false);
    }

    /**
     * @group unit
     */
    public function testSimulateFrontendEnvironmentWhenMksearchIndexingIsInProgress()
    {
        $property = new \ReflectionProperty('tx_mksearch_service_internal_Index', 'indexingInProgress');
        $property->setAccessible(true);
        $property->setValue(null, true);

        $GLOBALS['TSFE'] = 'test';

        $viewHelper = $this->getViewHelper();
        $this->callInaccessibleMethod($viewHelper, 'simulateFrontendEnvironment');
        self::assertSame('test', $GLOBALS['TSFE']);
    }

    /**
     * @group unit
     */
    public function testSimulateFrontendEnvironmentWhenMksearchIndexingIsNotInProgress()
    {
        $GLOBALS['TSFE'] = 'test';

        $viewHelper = $this->getViewHelper();
        $this->callInaccessibleMethod($viewHelper, 'simulateFrontendEnvironment');
        self::assertInstanceOf('stdCLass', $GLOBALS['TSFE']);
    }

    /**
     * @group unit
     */
    public function testResetFrontendEnvironmentWhenMksearchIndexingIsInProgress()
    {
        $property = new \ReflectionProperty('tx_mksearch_service_internal_Index', 'indexingInProgress');
        $property->setAccessible(true);
        $property->setValue(null, true);

        $viewHelper = $this->getViewHelper();
        $property = new \ReflectionProperty('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlViewHelper', 'tsfeBackup');
        $property->setAccessible(true);
        $property->setValue($viewHelper, 'tsfeBackup');

        $GLOBALS['TSFE'] = 'test';
        $this->callInaccessibleMethod($viewHelper, 'resetFrontendEnvironment');
        self::assertSame('test', $GLOBALS['TSFE']);
    }

    /**
     * @group unit
     */
    public function testResetFrontendEnvironmentWhenMksearchIndexingIsNotInProgress()
    {
        $GLOBALS['TSFE'] = 'test';

        $viewHelper = $this->getViewHelper();
        $property = new \ReflectionProperty('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlViewHelper', 'tsfeBackup');
        $property->setAccessible(true);
        $property->setValue($viewHelper, 'tsfeBackup');

        $this->callInaccessibleMethod($viewHelper, 'resetFrontendEnvironment');
        self::assertSame('tsfeBackup', $GLOBALS['TSFE']);
    }

    /**
     * @return DMK\Mksearch\ViewHelpers\Format\HtmlViewHelper
     */
    protected function getViewHelper()
    {
        $viewHelper = \tx_rnbase::makeInstance('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlViewHelper');
        if (!\tx_rnbase_util_TYPO3::isTYPO70OrHigher()) {
            $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
            $viewHelper->injectConfigurationManager($configurationManager);
        }

        return $viewHelper;
    }
}
