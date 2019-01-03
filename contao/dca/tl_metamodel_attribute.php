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
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedtabletext/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

/**
 * Table tl_metamodel_attribute
 */

/**
 * Add palette configuration.
 */
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['translatedtabletext extends _complexattribute_'] = [
    '+advanced' => ['tabletext_quantity_cols', 'translatedtabletext_cols'],
];

/**
 * Add data provider.
 */
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['dca_config']['data_provider']['tl_metamodel_translatedtabletext'] = [
    'source' => 'tl_metamodel_translatedtabletext'
];

/**
 * Add child condition.
 */
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['dca_config']['childCondition'][] = [
    'from'   => 'tl_metamodel_attribute',
    'to'     => 'tl_metamodel_translatedtabletext',
    'setOn'  => [
        [
            'to_field'   => 'att_id',
            'from_field' => 'id',
        ],
    ],
    'filter' => [
        [
            'local'     => 'att_id',
            'remote'    => 'id',
            'operation' => '=',
        ],
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tabletext_quantity_cols'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tabletext_quantity_cols'],
    'exclude'   => true,
    'inputType' => 'select',
    'default'   => 1,
    'options'   => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    'eval'      => ['tl_class' => 'clr m12', 'alwaysSave' => true, 'submitOnChange' => true],
    'sql'       => 'varchar(2) NOT NULL default \'\''
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['translatedtabletext_cols'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tabletext_cols'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => [
        'disableSorting'                     => true,
        'tl_class'                           => 'clr',
        'columnFields'                       => [
            'langcode'                       => [
                'exclude'                    => true,
                'inputType'                  => 'justtextoption',
                'eval'                       => [
                    'style'                  => 'min-width:75px;display:block;padding-top:28px;',
                    'valign'                 => 'top',
                ],
            ],
            'rowLabels'                     => [
                'exclude'                    => true,
                'inputType'                  => 'multiColumnWizard',
                'eval'                       => [
                    'disableSorting'         => true,
                    'tl_class'               => 'clr',
                    'columnFields'           => [
                        'rowLabel'           => [
                            'exclude'        => true,
                            'inputType'      => 'text',
                            'eval'           => [
                                'style'      => 'width:400px;',
                                'rows'       => 2,
                            ],
                        ],
                        'rowStyle'           => [
                            'inputType'      => 'text',
                            'eval'           => [
                                'allowHtml'  => false,
                                'style'      => 'width: 90px;',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'sql'   => 'blob NULL'
];
