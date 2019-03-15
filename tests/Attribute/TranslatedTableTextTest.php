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
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedtabletext/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedTableTextBundle\Test\Attribute;

use MetaModels\AttributeTranslatedTableTextBundle\Attribute\TranslatedTableText;
use MetaModels\AttributeTranslatedTableTextBundle\DatabaseAccessor;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests to test class GeoProtection.
 */
class TranslatedTableTextTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $language         The language.
     *
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($language, $fallbackLanguage): IMetaModel
    {
        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);

        $metaModel
            ->method('getTableName')
            ->willReturn('mm_unittest');

        $metaModel
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|DatabaseAccessor
     */
    private function mockAccessor(): DatabaseAccessor
    {
        return $this->getMockBuilder(DatabaseAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiation(): void
    {
        $text = new TranslatedTableText(
            $this->mockMetaModel('en', 'en'),
            [],
            $this->mockAccessor()
        );
        $this->assertInstanceOf(TranslatedTableText::class, $text);
    }

    /**
     * Test saving with a empty cells only saves the non empty cells.
     *
     * @return void
     */
    public function testSavingEmptyRow(): void
    {
        $accessor = $this->mockAccessor();
        $text     = new TranslatedTableText(
            $this->mockMetaModel('en', 'en'),
            ['id' => 42],
            $accessor
        );

        $accessor->expects($this->once())->method('removeDataForIds')->with(42, [21], 'en');

        $accessor
            ->expects($this->exactly(5))
            ->method('setDataRow')
            ->withConsecutive(
                [42, 21, 'en', 0, 0, '1'],
                [42, 21, 'en', 0, 1, '2'],
                [42, 21, 'en', 0, 2, '3'],
                [42, 21, 'en', 2, 0, '4'],
                [42, 21, 'en', 2, 2, '6']
            );

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
     * Test retrieving of data.
     *
     * @return void
     */
    public function testGetTranslatedDataFor(): void
    {
        $accessor  = $this->mockAccessor();
        $attribute = new TranslatedTableText(
            $this->mockMetaModel('en', 'en'),
            [
                'id' => 42,
                'tabletext_quantity_cols' => 3
            ],
            $accessor
        );

        $accessor
            ->expects($this->once())
            ->method('fetchDataFor')
            ->with(42, [21], 'en', 3)
            ->willReturn($data = [21 => [
                0 => [
                    $this->langRow(1, '1', '42', 0, 0, 21, 'en'),
                    $this->langRow(1, '2', '42', 0, 1, 21, 'en'),
                    $this->langRow(1, '3', '42', 0, 2, 21, 'en'),
                ],
                1 => [
                    $this->langRow(0, '', '42', 1, 0, 21, 'en'),
                    $this->langRow(0, '', '42', 1, 1, 21, 'en'),
                    $this->langRow(0, '', '42', 1, 2, 21, 'en'),
                ],
                2 => [
                    $this->langRow(1, '4', '42', 2, 0, 21, 'en'),
                    $this->langRow(0, '', '42', 2, 1, 21, 'en'),
                    $this->langRow(1, '6', '42', 2, 2, 21, 'en'),
                ]
            ]]);

        $this->assertSame($data, $attribute->getTranslatedDataFor([21], 'en'));
    }

    /**
     * Test retrieving of data fetches missing data from fallback language.
     *
     * @return void
     */
    public function testGetDataFor(): void
    {
        $accessor  = $this->mockAccessor();
        $attribute = new TranslatedTableText(
            $this->mockMetaModel('de', 'en'),
            [
                'id' => 42,
                'tabletext_quantity_cols' => 1
            ],
            $accessor
        );

        $accessor
            ->expects($this->exactly(2))
            ->method('fetchDataFor')
            ->withConsecutive([42, [21, 23], 'de', 1], [42, [23], 'en', 1])
            ->willReturnOnConsecutiveCalls(
                [21 => [
                    0 => [$this->langRow(1, '1', '42', 0, 0, 21, 'de')],
                    1 => [$this->langRow(0, '', '42', 1, 0, 21, 'de')],
                    2 => [$this->langRow(1, '4', '42', 2, 0, 21, 'de')]
                ]],
                [23 => [
                    0 => [$this->langRow(1, '1', '42', 0, 0, 21, 'en')],
                    1 => [$this->langRow(0, '', '42', 1, 0, 21, 'en')],
                    2 => [$this->langRow(1, '4', '42', 2, 0, 21, 'en')]
                ]]
            );

        $this->assertSame(
            [
                21 => [
                    0 => [$this->langRow(1, '1', '42', 0, 0, 21, 'de')],
                    1 => [$this->langRow(0, '', '42', 1, 0, 21, 'de')],
                    2 => [$this->langRow(1, '4', '42', 2, 0, 21, 'de')]
                ],
                23 => [
                    0 => [$this->langRow(1, '1', '42', 0, 0, 21, 'en')],
                    1 => [$this->langRow(0, '', '42', 1, 0, 21, 'en')],
                    2 => [$this->langRow(1, '4', '42', 2, 0, 21, 'en')]
                ],
            ],
            $attribute->getDataFor([21, 23])
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
            ],
            $this->mockAccessor()
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
