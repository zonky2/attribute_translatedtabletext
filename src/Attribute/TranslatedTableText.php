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
 * @author     David Maack <david.maack@arcor.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedtabletext/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedTableTextBundle\Attribute;

use Contao\StringUtil;
use Contao\System;
use MetaModels\Attribute\Base;
use MetaModels\Attribute\IComplex;
use MetaModels\AttributeTranslatedTableTextBundle\DatabaseAccessor;
use MetaModels\IMetaModel;
use MetaModels\Attribute\ITranslated;

/**
 * This is the MetaModelAttribute class for handling translated table text fields.
 */
class TranslatedTableText extends Base implements ITranslated, IComplex
{
    /**
     * The database accessor.
     *
     * @var DatabaseAccessor
     */
    private $accessor;

    /**
     * Instantiate an MetaModel attribute.
     *
     * Note that you should not use this directly but use the factory classes to instantiate attributes.
     *
     * @param IMetaModel       $objMetaModel The MetaModel instance this attribute belongs to.
     * @param array            $arrData      The information array, for attribute information, refer to documentation of
     *                                       table tl_metamodel_attribute and documentation of the certain attribute
     *                                       classes for information what values are understood.
     * @param DatabaseAccessor $accessor     Database connection.
     */
    public function __construct(IMetaModel $objMetaModel, array $arrData = [], DatabaseAccessor $accessor = null)
    {
        parent::__construct($objMetaModel, $arrData);

        if (null === $accessor) {
            // @codingStandardsIgnoreStart
            @\trigger_error(
                'DatabaseAccessor is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $accessor = new DatabaseAccessor(System::getContainer()->get('database_connection'));
        }

        $this->accessor = $accessor;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributeSettingNames()
    {
        return \array_merge(
            parent::getAttributeSettingNames(),
            [
                'translatedtabletext_cols',
                'tabletext_quantity_cols',
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldDefinition($arrOverrides = [])
    {
        $strActiveLanguage   = $this->getMetaModel()->getActiveLanguage();
        $strFallbackLanguage = $this->getMetaModel()->getFallbackLanguage();
        $arrAllColLabels     = StringUtil::deserialize($this->get('translatedtabletext_cols'), true);
        $arrColLabels        = null;

        if (\array_key_exists($strActiveLanguage, $arrAllColLabels)) {
            $arrColLabels = $arrAllColLabels[$strActiveLanguage];
        } elseif (\array_key_exists($strFallbackLanguage, $arrAllColLabels)) {
            $arrColLabels = $arrAllColLabels[$strFallbackLanguage];
        } else {
            $arrColLabels = \array_shift($arrAllColLabels);
        }

        // Build DCA.
        $arrFieldDef                         = parent::getFieldDefinition($arrOverrides);
        $arrFieldDef['inputType']            = 'multiColumnWizard';
        $arrFieldDef['eval']['columnFields'] = [];

        $countCol = \count($arrColLabels);
        for ($i = 0; $i < $countCol; $i++) {
            $arrFieldDef['eval']['columnFields']['col_' . $i] = [
                'label'     => $arrColLabels[$i]['rowLabel'],
                'inputType' => 'text',
                'eval'      => [],
            ];

            if ($arrColLabels[$i]['rowStyle']) {
                $arrFieldDef['eval']['columnFields']['col_' . $i]['eval']['style'] =
                    'width:' . $arrColLabels[$i]['rowStyle'];
            }
        }

        return $arrFieldDef;
    }

    /**
     * Build a where clause for the given id(s) and rows/cols.
     *
     * @param mixed  $mixIds      One, none or many ids to use.
     *
     * @param string $strLangCode The language code, optional.
     *
     * @param int    $intRow      The row number, optional.
     *
     * @param int    $intCol      The col number, optional.
     *
     * @return array
     *
     * @deprecated Not used since 2.1 to be removed in 3.0
     */
    protected function getWhere($mixIds, $strLangCode = null, $intRow = null, $intCol = null)
    {
        $arrReturn = [
            'procedure' => 'att_id=?',
            'params'    => [(int) $this->get('id')],
        ];

        if ($mixIds) {
            if (\is_array($mixIds)) {
                $arrReturn['procedure'] .= ' AND item_id IN (' . $this->parameterMask($mixIds) . ')';
                $arrReturn['params']     = \array_merge($arrReturn['params'], $mixIds);
            } else {
                $arrReturn['procedure'] .= ' AND item_id=?';
                $arrReturn['params'][]   = $mixIds;
            }
        }

        if (\is_int($intRow) && \is_int($intCol)) {
            $arrReturn['procedure'] .= ' AND row = ? AND col = ?';
            $arrReturn['params'][]   = $intRow;
            $arrReturn['params'][]   = $intCol;
        }

        if ($strLangCode) {
            $arrReturn['procedure'] .= ' AND langcode=?';
            $arrReturn['params'][]   = $strLangCode;
        }

        return $arrReturn;
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        if (!\is_array($varValue)) {
            return [];
        }

        $countCol    = $this->get('tabletext_quantity_cols');
        $widgetValue = [];

        foreach ($varValue as $k => $row) {
            for ($kk = 0; $kk < $countCol; $kk++) {
                $i = \array_search($kk, \array_column($row, 'col'));

                $widgetValue[$k]['col_' . $kk] = ($i !== false) ? $row[$i]['value'] : '';
            }
        }

        return $widgetValue;
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $itemId)
    {
        if (!\is_array($varValue)) {
            return null;
        }

        $newValue = [];
        // Start row numerator at 0.
        $intRow = 0;
        foreach ($varValue as $k => $row) {
            foreach ($row as $kk => $col) {
                $kk = \str_replace('col_', '', $kk);

                $newValue[$k][$kk]['value'] = $col;
                $newValue[$k][$kk]['col']   = $kk;
                $newValue[$k][$kk]['row']   = $intRow;
            }
            $intRow++;
        }

        return $newValue;
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        return $this->accessor->fetchDataFor(
            $this->get('id'),
            $arrIds,
            $strLangCode,
            $this->get('tabletext_quantity_cols')
        );
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function searchForInLanguages($strPattern, $arrLanguages = [])
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function setTranslatedDataFor($arrValues, $strLangCode)
    {
        // Get the ids.
        $arrIds = \array_keys($arrValues);

        // Reset all data for the ids in language.
        $this->unsetValueFor($arrIds, $strLangCode);

        foreach ($arrIds as $intId) {
            // Walk every row.
            foreach ($arrValues[$intId] as $row) {
                // Walk every column and insert the value.
                foreach ($row as $cell) {
                    if (empty($cell['value'])) {
                        continue;
                    }

                    $this->accessor->setDataRow(
                        $this->get('id'),
                        $intId,
                        $strLangCode,
                        $cell['row'],
                        $cell['col'],
                        $cell['value']
                    );
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unsetValueFor($arrIds, $strLangCode)
    {
        $this->accessor->removeDataForIds($this->get('id'), $arrIds, $strLangCode);
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setDataFor($arrValues)
    {
        $this->setTranslatedDataFor($arrValues, $this->getMetaModel()->getActiveLanguage());
    }

    /**
     * {@inheritDoc}
     */
    public function getDataFor($arrIds)
    {
        $strActiveLanguage   = $this->getMetaModel()->getActiveLanguage();
        $strFallbackLanguage = $this->getMetaModel()->getFallbackLanguage();

        $arrReturn = $this->getTranslatedDataFor($arrIds, $strActiveLanguage);

        // Second round, fetch fallback languages if not all items could be resolved.
        if ((\count($arrReturn) < \count($arrIds)) && ($strActiveLanguage != $strFallbackLanguage)) {
            $arrFallbackIds = [];
            foreach ($arrIds as $intId) {
                if (empty($arrReturn[$intId])) {
                    $arrFallbackIds[] = $intId;
                }
            }

            if ($arrFallbackIds) {
                $arrFallbackData = $this->getTranslatedDataFor($arrFallbackIds, $strFallbackLanguage);
                // Cannot use array_merge here as it would renumber the keys.
                foreach ($arrFallbackData as $intId => $arrValue) {
                    $arrReturn[$intId] = $arrValue;
                }
            }
        }
        return $arrReturn;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException When the passed value is not an array of ids.
     */
    public function unsetDataFor($arrIds)
    {
        if (!\is_array($arrIds)) {
            throw new \RuntimeException(
                'TranslatedTableText::unsetDataFor() invalid parameter given! Array of ids is needed.',
                1
            );
        }

        if (empty($arrIds)) {
            return;
        }

        $this->accessor->removeDataForIds($this->get('id'), $arrIds);
    }
}
