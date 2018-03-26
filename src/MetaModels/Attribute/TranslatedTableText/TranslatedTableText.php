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
 * @author     David Maack <david.maack@arcor.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedtabletext/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\Attribute\TranslatedTableText;

use MetaModels\Attribute\Base;
use MetaModels\Attribute\ITranslated;
use MetaModels\Attribute\IComplex;

/**
 * This is the MetaModelAttribute class for handling translated table text fields.
 */
class TranslatedTableText extends Base implements ITranslated, IComplex
{
    /**
     * {@inheritDoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(parent::getAttributeSettingNames(), array(
            'translatedtabletext_cols',
            'tabletext_quantity_cols',
        ));
    }

    /**
     * Retrieve the table name containing the values.
     *
     * @return string
     */
    protected function getValueTable()
    {
        return 'tl_metamodel_translatedtabletext';
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $strActiveLanguage   = $this->getMetaModel()->getActiveLanguage();
        $strFallbackLanguage = $this->getMetaModel()->getFallbackLanguage();
        $arrAllColLabels     = deserialize($this->get('translatedtabletext_cols'), true);
        $arrColLabels        = null;

        if (array_key_exists($strActiveLanguage, $arrAllColLabels)) {
            $arrColLabels = $arrAllColLabels[$strActiveLanguage];
        } elseif (array_key_exists($strFallbackLanguage, $arrAllColLabels)) {
            $arrColLabels = $arrAllColLabels[$strFallbackLanguage];
        } else {
            $arrColLabels = array_shift($arrAllColLabels);
        }

        // Build DCA.
        $arrFieldDef                         = parent::getFieldDefinition($arrOverrides);
        $arrFieldDef['inputType']            = 'multiColumnWizard';
        $arrFieldDef['eval']['columnFields'] = array();

        $countCol = count($arrColLabels);
        for ($i = 0; $i < $countCol; $i++) {
            $arrFieldDef['eval']['columnFields']['col_' . $i] = array(
                'label' => $arrColLabels[$i]['rowLabel'],
                'inputType' => 'text',
                'eval' => array(),
            );

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
     * @return string
     */
    protected function getWhere($mixIds, $strLangCode = null, $intRow = null, $intCol = null)
    {
        $arrReturn = array(
            'procedure' => 'att_id=?',
            'params' => array(intval($this->get('id'))),
        );

        if ($mixIds) {
            if (is_array($mixIds)) {
                $arrReturn['procedure'] .= ' AND item_id IN (' . $this->parameterMask($mixIds) . ')';
                $arrReturn['params']     = array_merge($arrReturn['params'], $mixIds);
            } else {
                $arrReturn['procedure'] .= ' AND item_id=?';
                $arrReturn['params'][]   = $mixIds;
            }
        }

        if (is_int($intRow) && is_int($intCol)) {
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
        if (!is_array($varValue)) {
            return array();
        }

        $countCol    = $this->get('tabletext_quantity_cols');
        $widgetValue = array();

        foreach ($varValue as $k => $row) {
            for ($kk = 0; $kk < $countCol; $kk++) {
                $i = array_search($kk, array_column($row, 'col'));

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
        if (!is_array($varValue)) {
            return null;
        }

        $newValue = array();
        // Start row numerator at 0.
        $intRow = 0;
        foreach ($varValue as $k => $row) {
            foreach ($row as $kk => $col) {
                $kk = str_replace('col_', '', $kk);

                $newValue[$k][$kk]['value'] = $col;
                $newValue[$k][$kk]['col']   = $kk;
                $newValue[$k][$kk]['row']   = $intRow;
            }
            $intRow++;
        }

        return $newValue;
    }

    /**
     * Retrieve the setter array.
     *
     * @param array  $arrCell     The cells of the table.
     *
     * @param int    $intId       The id of the item.
     *
     * @param string $strLangCode The language code.
     *
     * @return array
     */
    protected function getSetValues($arrCell, $intId, $strLangCode)
    {
        return array(
            'tstamp'   => time(),
            'value'    => (string) $arrCell['value'],
            'att_id'   => $this->get('id'),
            'row'      => (int) $arrCell['row'],
            'col'      => (int) $arrCell['col'],
            'item_id'  => $intId,
            'langcode' => $strLangCode,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        $arrWhere = $this->getWhere($arrIds, $strLangCode);
        $strQuery = sprintf(
            'SELECT * FROM %s%s ORDER BY item_id ASC, row ASC, col ASC',
            $this->getValueTable(),
            ($arrWhere ? ' WHERE ' . $arrWhere['procedure'] : '')
        );
        $objValue = $this
            ->getMetaModel()
            ->getServiceContainer()
            ->getDatabase()
            ->prepare($strQuery)
            ->execute(($arrWhere ? $arrWhere['params'] : null));

        $countCol = $this->get('tabletext_quantity_cols');
        $result   = array();
        while ($objValue->next()) {
            $content = $objValue->row();
            $this->pushValue($content, $result, $countCol, $strLangCode);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function searchForInLanguages($strPattern, $arrLanguages = array())
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function setTranslatedDataFor($arrValues, $strLangCode)
    {
        $objDB = $this->getMetaModel()->getServiceContainer()->getDatabase();

        // Get the ids.
        $arrIds = array_keys($arrValues);

        // Reset all data for the ids in language.
        $this->unsetValueFor($arrIds, $strLangCode);

        $strQueryInsert = 'INSERT INTO ' . $this->getValueTable() . ' %s';
        foreach ($arrIds as $intId) {
            // Walk every row.
            foreach ($arrValues[$intId] as $row) {
                // Walk every column and update / insert the value.
                foreach ($row as $col) {
                    $values = $this->getSetValues($col, $intId, $strLangCode);
                    if ($values['value'] === '') {
                        continue;
                    }
                    $objDB
                        ->prepare($strQueryInsert)
                        ->set($values)
                        ->execute();
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unsetValueFor($arrIds, $strLangCode)
    {
        $objDB    = $this->getMetaModel()->getServiceContainer()->getDatabase();
        $arrWhere = $this->getWhere($arrIds, $strLangCode);
        $strQuery = 'DELETE FROM ' . $this->getValueTable() . ($arrWhere ? ' WHERE ' . $arrWhere['procedure'] : '');

        $objDB
            ->prepare($strQuery)
            ->execute(($arrWhere ? $arrWhere['params'] : null));
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        return array();
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDataFor($arrIds)
    {
        $strActiveLanguage   = $this->getMetaModel()->getActiveLanguage();
        $strFallbackLanguage = $this->getMetaModel()->getFallbackLanguage();

        $arrReturn = $this->getTranslatedDataFor($arrIds, $strActiveLanguage);

        // Second round, fetch fallback languages if not all items could be resolved.
        if ((count($arrReturn) < count($arrIds)) && ($strActiveLanguage != $strFallbackLanguage)) {
            $arrFallbackIds = array();
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function unsetDataFor($arrIds)
    {
        if (!is_array($arrIds)) {
            throw new \RuntimeException(
                'TranslatedTableText::unsetDataFor() invalid parameter given! Array of ids is needed.',
                1
            );
        }

        if (empty($arrIds)) {
            return;
        }

        $objDB    = $this->getMetaModel()->getServiceContainer()->getDatabase();
        $arrWhere = $this->getWhere($arrIds);
        $strQuery = 'DELETE FROM ' . $this->getValueTable() . ($arrWhere ? ' WHERE ' . $arrWhere['procedure'] : '');

        $objDB
            ->prepare($strQuery)
            ->execute(($arrWhere ? $arrWhere['params'] : null));
    }

    /**
     * Push a database value to the passed array.
     *
     * @param array  $value        The value from the database.
     * @param array  $result       The result list.
     * @param int    $countCol     The count of columns per row.
     * @param string $languageCode The language code to use for empty cells.
     *
     * @return void
     */
    private function pushValue($value, &$result, $countCol, $languageCode)
    {
        $buildRow = function (&$list, $itemId, $row) use ($countCol, $languageCode) {
            for ($i = count($list); $i < $countCol; $i++) {
                $list[$i] = [
                    'tstamp'   => 0,
                    'value'    => '',
                    'att_id'   => $this->get('id'),
                    'row'      => $row,
                    'col'      => $i,
                    'item_id'  => $itemId,
                    'langcode' => $languageCode
                ];
            }
        };

        $itemId = $value['item_id'];
        if (!isset($result[$itemId])) {
            $result[$itemId] = [];
        }

        // Prepare all rows up until to this item.
        $row = count($result[$itemId]);
        while ($row <= $value['row']) {
            if (!isset($result[$itemId][$row])) {
                $result[$itemId][$row] = [];
            }
            $buildRow($result[$itemId][$row], $itemId, $row);
            $row++;
        }
        $result[$itemId][(int) $value['row']][(int) $value['col']] = $value;
    }
}
