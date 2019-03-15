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

namespace MetaModels\AttributeTranslatedTableTextBundle;

use Doctrine\DBAL\Connection;

/**
 * This encapsulates the database access for translated table text attributes.
 */
class DatabaseAccessor
{
    /**
     * Database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Create a new instance.
     *
     * @param Connection $connection The connection to use.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Store a cell in the database.
     *
     * @param string $attributeId The attribute id for which to set.
     * @param string $itemId      The id list for which to set.
     * @param string $language    The language for which to set.
     * @param int    $row         The row index.
     * @param int    $col         The column index.
     * @param string $value       The value to use.
     *
     * @return void
     */
    public function setDataRow(
        string $attributeId,
        string $itemId,
        string $language,
        int $row,
        int $col,
        string $value
    ): void {
        $queryBuilder = $this->connection->createQueryBuilder()->insert('tl_metamodel_translatedtabletext');

        $queryBuilder
            ->values([
                'tstamp'   => ':tstamp',
                'value'    => ':value',
                'att_id'   => ':att_id',
                'row'      => ':row',
                'col'      => ':col',
                'item_id'  => ':item_id',
                'langcode' => ':langcode',
            ])
            ->setParameters([
                'tstamp'   => \time(),
                'value'    => $value,
                'att_id'   => $attributeId,
                'row'      => $row,
                'col'      => $col,
                'item_id'  => $itemId,
                'langcode' => $language,
            ]);

        $queryBuilder->execute();
    }

    /**
     * Remove all rows for the passed id list.
     *
     * @param string $attributeId The attribute id for which to unset.
     * @param array  $idsList     The id list for which to unset.
     * @param string $language    The language for which to unset (optional, null means all languages).
     *
     * @return void
     */
    public function removeDataForIds(string $attributeId, array $idsList, string $language = null): void
    {
        $queryBuilder = $this->connection->createQueryBuilder()->delete('tl_metamodel_translatedtabletext');
        $queryBuilder
            ->andWhere('att_id=:att_id')
            ->setParameter('att_id', $attributeId);
        $queryBuilder
            ->andWhere('item_id IN (:item_ids)')
            ->setParameter('item_ids', $idsList, Connection::PARAM_STR_ARRAY);
        if (null !== $language) {
            $queryBuilder
                ->andWhere('langcode=:langcode')
                ->setParameter('langcode', $language);
        }

        $queryBuilder->execute();
    }

    /**
     * Remove all rows for the passed id list.
     *
     * @param string $attributeId The attribute id to fetch.
     * @param array  $idsList     The id list to fetch.
     * @param string $language    The language to fetch.
     * @param int    $columnCount The amount of columns per row.
     *
     * @return array
     */
    public function fetchDataFor(string $attributeId, array $idsList, string $language, int $columnCount): array
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('tl_metamodel_translatedtabletext')
            ->orderBy('item_id', 'ASC')
            ->addOrderBy('row', 'ASC')
            ->addOrderBy('col', 'ASC');

        $queryBuilder
            ->andWhere('att_id=:att_id')
            ->setParameter('att_id', $attributeId);
        $queryBuilder
            ->andWhere('item_id IN (:item_ids)')
            ->setParameter('item_ids', $idsList, Connection::PARAM_STR_ARRAY);
        $queryBuilder
            ->andWhere('langcode=:langcode')
            ->setParameter('langcode', $language);

        $statement = $queryBuilder->execute();
        $result    = [];

        while ($value = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $this->pushValue($attributeId, $value, $result, $columnCount, $language);
        }

        return $result;
    }

    /**
     * Push a database value to the passed array.
     *
     * @param string $attributeId  The attribute id to fetch.
     * @param array  $value        The value from the database.
     * @param array  $result       The result list.
     * @param int    $countCol     The count of columns per row.
     * @param string $languageCode The language code to use for empty cells.
     *
     * @return void
     */
    private function pushValue(string $attributeId, $value, &$result, $countCol, $languageCode)
    {
        $buildRow = function (&$list, $itemId, $row) use ($countCol, $languageCode, $attributeId) {
            for ($i = \count($list); $i < $countCol; $i++) {
                $list[$i] = [
                    'tstamp'   => 0,
                    'value'    => '',
                    'att_id'   => $attributeId,
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
        $row = \count($result[$itemId]);
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
