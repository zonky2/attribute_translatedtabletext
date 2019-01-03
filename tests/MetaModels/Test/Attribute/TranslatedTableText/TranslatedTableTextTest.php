<?php

/**
 * This file is part of MetaModels/attribute_translatedtabletext.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_translatedtabletext
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedtabletext/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\Test\Attribute\TranslatedTableText;

use MetaModels\Attribute\TranslatedTableText\TranslatedTableText;
use MetaModels\IMetaModel;
use MetaModels\IMetaModelsServiceContainer;
use PHPUnit\Framework\TestCase;

// HACKY but we need deserialize() here - change for Contao 4 to \Contao\StringUtil::deserialize().
if (!defined('TL_ROOT')) {
    define('TL_ROOT', __DIR__ . '/../../../../../vendor/contao/core');
}
require_once TL_ROOT . '/system/helper/functions.php';

/**
 * Unit tests to test class GeoProtection.
 */
class TranslatedTableTextTest extends TestCase
{
    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $text = new TranslatedTableText($this->getMockForAbstractClass(IMetaModel::class));
        $this->assertInstanceOf(TranslatedTableText::class, $text);
    }


    /**
     * Test saving with an empty row.
     *
     * @return void
     */
    public function testSavingEmptyRow()
    {
        $mockDB    = $this->getMockBuilder(\stdClass::class)->setMethods(['prepare'])->getMock();
        $container = $this->getMockForAbstractClass(IMetaModelsServiceContainer::class);
        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);

        $metaModel->method('getServiceContainer')->willReturn($container);
        $container->method('getDatabase')->willReturn($mockDB);

        $mockQueries = $this
            ->getMockBuilder(\stdClass::class)
            ->setMethods(['execute', 'set'])
            ->getMock();

        $mockQueries->expects($this->exactly(5))->method('set')->withConsecutive(
            [$this->langRow(\time(), '1', 42, 0, 0, 21, 'en')],
            [$this->langRow(\time(), '2', 42, 0, 1, 21, 'en')],
            [$this->langRow(\time(), '3', 42, 0, 2, 21, 'en')],
            [$this->langRow(\time(), '4', 42, 2, 0, 21, 'en')],
            [$this->langRow(\time(), '6', 42, 2, 2, 21, 'en')]
        )->willReturn($mockQueries);

        $mockDB->expects($this->exactly(6))->method('prepare')->withConsecutive(
            ['DELETE FROM tl_metamodel_translatedtabletext WHERE att_id=? AND item_id IN (?) AND langcode=?'],
            ['INSERT INTO tl_metamodel_translatedtabletext %s'],
            ['INSERT INTO tl_metamodel_translatedtabletext %s'],
            ['INSERT INTO tl_metamodel_translatedtabletext %s'],
            ['INSERT INTO tl_metamodel_translatedtabletext %s'],
            ['INSERT INTO tl_metamodel_translatedtabletext %s']
        )->willReturn($mockQueries);

        $text = new TranslatedTableText($metaModel, ['id' => 42]);

        $text->setTranslatedDataFor(
            [
                21 => [
                    0 => [
                        0 => ['value' => '1', 'row' => 0, 'col' => 0],
                        1 => ['value' => '2', 'row' => 0, 'col' => 1],
                        2 => ['value' => '3', 'row' => 0, 'col' => 2],
                    ],
                    1 => [
                        0 => ['value' => '', 'row' => 1, 'col' => 0],
                        1 => ['value' => '', 'row' => 1, 'col' => 1],
                        2 => ['value' => '', 'row' => 1, 'col' => 2],
                    ],
                    2 => [
                        0 => ['value' => '4', 'row' => 2, 'col' => 0],
                        1 => ['value' => '', 'row' => 2, 'col' => 1],
                        2 => ['value' => '6', 'row' => 2, 'col' => 2],
                    ],
                ]
            ],
            'en'
        );
    }

    /**
     * Test retrieving of data with "holes".
     *
     * @return void
     */
    public function testRetrievingEmptyRow()
    {
        $mockDB    = $this->getMockBuilder(\stdClass::class)->setMethods(['prepare'])->getMock();
        $container = $this->getMockForAbstractClass(IMetaModelsServiceContainer::class);
        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);

        $metaModel->method('getServiceContainer')->willReturn($container);
        $container->method('getDatabase')->willReturn($mockDB);

        $mockResult = $this->getMockBuilder(\stdClass::class)->setMethods(['next', 'row'])->getMock();

        $mockResult->expects($this->exactly(6))->method('next')
            ->willReturnOnConsecutiveCalls(true, true, true, true, true, false);
        $mockResult->method('row')->willReturnOnConsecutiveCalls(
            $this->langRow(1, '1', 2, 0, 0, 21, 'en'),
            $this->langRow(1, '2', 2, 0, 1, 21, 'en'),
            $this->langRow(1, '3', 2, 0, 2, 21, 'en'),
            $this->langRow(1, '4', 2, 2, 0, 21, 'en'),
            $this->langRow(1, '6', 2, 2, 2, 21, 'en')
        );

        $mockQueries = $this
            ->getMockBuilder(\stdClass::class)
            ->setMethods(['execute', 'set'])
            ->getMock();
        $mockQueries->method('execute')->with([2, 21, 'en'])->willReturn($mockResult);

        $mockDB
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM tl_metamodel_translatedtabletext WHERE att_id=? AND item_id IN (?) AND langcode=?' .
                ' ORDER BY item_id ASC, row ASC, col ASC')
            ->willReturn($mockQueries);

        $text = new TranslatedTableText(
            $metaModel,
            [
                'id' => 2,
                'tabletext_quantity_cols' => 3
            ]
        );

        $this->assertEquals(
            [21 => [
                0 => [
                    $this->langRow(1, '1', 2, 0, 0, 21, 'en'),
                    $this->langRow(1, '2', 2, 0, 1, 21, 'en'),
                    $this->langRow(1, '3', 2, 0, 2, 21, 'en'),
                ],
                1 => [
                    $this->langRow(0, '', 2, 1, 0, 21, 'en'),
                    $this->langRow(0, '', 2, 1, 1, 21, 'en'),
                    $this->langRow(0, '', 2, 1, 2, 21, 'en'),
                ],
                2 => [
                    $this->langRow(1, '4', 2, 2, 0, 21, 'en'),
                    $this->langRow(0, '', 2, 2, 1, 21, 'en'),
                    $this->langRow(1, '6', 2, 2, 2, 21, 'en'),
                ]
            ]],
            $text->getTranslatedDataFor([21], 'en')
        );
    }

    /**
     * Test that the value to widget method works correctly.
     *
     * @return void
     */
    public function testValueToWidget()
    {
        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);

        $text = new TranslatedTableText(
            $metaModel,
            [
                'id' => 42,
                'tabletext_quantity_cols' => 3
            ]
        );

        $this->assertEquals(
            [
                0 => ['col_0' => '1', 'col_1' => '2', 'col_2' => '3'],
                1 => ['col_0' => '',  'col_1' => '',  'col_2' => ''],
                2 => ['col_0' => '4', 'col_1' => '5', 'col_2' => '6'],
            ],
            $text->valueToWidget([
                0 => [
                    $this->langRow(1, '1', 42, 0, 0, 21, 'en'),
                    $this->langRow(1, '2', 42, 0, 1, 21, 'en'),
                    $this->langRow(1, '3', 42, 0, 2, 21, 'en'),
                ],
                1 => [
                    $this->langRow(1, '', 42, 1, 0, 21, 'en'),
                    $this->langRow(1, '', 42, 1, 1, 21, 'en'),
                    $this->langRow(1, '', 42, 1, 2, 21, 'en'),
                ],
                2 => [
                    $this->langRow(1, '4', 42, 2, 0, 21, 'en'),
                    $this->langRow(1, '5', 42, 2, 1, 21, 'en'),
                    $this->langRow(1, '6', 42, 2, 2, 21, 'en'),
                ]
            ])
        );
    }

    /**
     * Build a database row from the passed values.
     *
     * @param int    $tstamp   The timestamp.
     * @param string $value    The value.
     * @param string $attId    The attribute id.
     * @param int    $row      The row index.
     * @param int    $col      The column index.
     * @param string $itemId   The item id.
     * @param string $langcode The language code.
     *
     * @return array
     */
    private function langRow($tstamp, $value, $attId, $row, $col, $itemId, $langcode)
    {
        return [
            'tstamp'   => $tstamp,
            'value'    => $value,
            'att_id'   => $attId,
            'row'      => $row,
            'col'      => $col,
            'item_id'  => $itemId,
            'langcode' => $langcode,
        ];
    }
}
