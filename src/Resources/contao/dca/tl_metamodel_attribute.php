<?php

/**
 * This file is part of MetaModels/attribute_translatedtabletext.
 *
 * (c) 2012-2018 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedTableText
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedtabletext/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['translatedtabletext extends _complexattribute_'] = array(
    '+advanced' => array('tabletext_quantity_cols', 'translatedtabletext_cols'),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['dca_config']['data_provider']['tl_metamodel_translatedtabletext'] = array
(
    'source' => 'tl_metamodel_translatedtabletext'
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['dca_config']['childCondition'][] = array
(
    'from'   => 'tl_metamodel_attribute',
    'to'     => 'tl_metamodel_translatedtabletext',
    'setOn'  => array
    (
        array
        (
            'to_field'   => 'att_id',
            'from_field' => 'id',
        ),
    ),
    'filter' => array
    (
        array
        (
            'local'     => 'att_id',
            'remote'    => 'id',
            'operation' => '=',
        ),
    )
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tabletext_quantity_cols'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tabletext_quantity_cols'],
    'exclude'   => true,
    'inputType' => 'select',
    'default'   => 1,
    'options'   => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    'eval'      => ['tl_class' => 'clr m12', 'alwaysSave' => true, 'submitOnChange' => true],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['translatedtabletext_cols'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tabletext_cols'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => array(
        'disableSorting'                     => true,
        'tl_class'                           => 'clr',
        'columnFields'                       => array(
            'langcode'                       => array(
                'exclude'                    => true,
                'inputType'                  => 'justtextoption',
                'eval'                       => array(
                    'style'                  => 'min-width:75px;display:block;padding-top:28px;',
                    'valign'                 => 'top',
                ),
            ),
            'rowLabels'                     => array(
                'exclude'                    => true,
                'inputType'                  => 'multiColumnWizard',
                'eval'                       => array(
                    'disableSorting'         => true,
                    'tl_class'               => 'clr',
                    'columnFields'           => array(
                        'rowLabel'           => array(
                            'exclude'        => true,
                            'inputType'      => 'text',
                            'eval'           => array(
                                'style'      => 'width:400px;',
                                'rows'       => 2,
                            ),
                        ),
                        'rowStyle'           => array(
                            'inputType'      => 'text',
                            'eval'           => array(
                                'allowHtml'  => false,
                                'style'      => 'width: 90px;',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
