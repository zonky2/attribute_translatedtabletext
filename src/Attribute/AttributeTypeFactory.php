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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedtabletext/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedTableTextBundle\Attribute;

use MetaModels\Attribute\AbstractAttributeTypeFactory;
use MetaModels\AttributeTranslatedTableTextBundle\DatabaseAccessor;

/**
 * Attribute type factory for translated table text attributes.
 */
class AttributeTypeFactory extends AbstractAttributeTypeFactory
{
    /**
     * Database connection.
     *
     * @var DatabaseAccessor
     */
    private $accessor;

    /**
     * Create an instance.
     *
     * @param DatabaseAccessor $accessor Database accessor.
     */
    public function __construct(DatabaseAccessor $accessor)
    {
        parent::__construct();

        $this->typeName  = 'translatedtabletext';
        $this->typeIcon  = 'bundles/metamodelsattributetranslatedtabletext/translatedtabletext.png';
        $this->typeClass = TranslatedTableText::class;
        $this->accessor  = $accessor;
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance($information, $metaModel)
    {
        return new $this->typeClass($metaModel, $information, $this->accessor);
    }
}
