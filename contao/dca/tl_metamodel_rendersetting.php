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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedtabletext/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['metapalettes']['translatedtabletext extends default'] = [
    '+advanced' => ['translatedtabletext_hide_tablehead'],
];


$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['translatedtabletext_hide_tablehead'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['translatedtabletext_hide_tablehead'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'clr w50'
    ],
    'sql'      => 'varchar(1) NOT NULL default \'0\''
];
