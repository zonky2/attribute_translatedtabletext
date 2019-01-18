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

namespace MetaModels\AttributeTranslatedTableTextBundle\Test\DependencyInjection;

use MetaModels\AttributeTranslatedTableTextBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTranslatedTableTextBundle\DatabaseAccessor;
use MetaModels\AttributeTranslatedTableTextBundle\DependencyInjection\MetaModelsAttributeTranslatedTableTextExtension;
use MetaModels\AttributeTranslatedTableTextBundle\EventListener\DcGeneral\Table\BackendTableListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This test case test the extension.
 */
class AttributeTranslatedTableTextExtensionTest extends TestCase
{
    /**
     * Test that extension can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $extension = new MetaModelsAttributeTranslatedTableTextExtension();

        $this->assertInstanceOf(MetaModelsAttributeTranslatedTableTextExtension::class, $extension);
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }

    /**
     * Test that the services are loaded.
     *
     * @return void
     */
    public function testFactoryIsRegistered()
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();

        $container
            ->expects($this->exactly(3))
            ->method('setDefinition')
            ->withConsecutive(
                [
                    'metamodels.attribute_translatedtabletext.factory',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(AttributeTypeFactory::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('metamodels.attribute_factory'));
                            $this->assertCount(1, $arguments = $value->getArguments());
                            $this->assertInstanceOf(Reference::class, $arguments[0]);
                            $this->assertSame(DatabaseAccessor::class, (string) $arguments[0]);

                            return true;
                        }
                    )
                ],
                [
                    DatabaseAccessor::class,
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertCount(1, $arguments = $value->getArguments());
                            $this->assertInstanceOf(Reference::class, $arguments[0]);
                            $this->assertSame('database_connection', (string) $arguments[0]);

                            return true;
                        }
                    )
                ],
                [
                    'metamodels.attribute_translatedtabletext.listeners.translated_alias_options',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(BackendTableListener::class, $value->getClass());
                            $this->assertCount(3, $value->getTag('kernel.event_listener'));

                            return true;
                        }
                    )
                ]
            );

        $extension = new MetaModelsAttributeTranslatedTableTextExtension();
        $extension->load([], $container);
    }
}
