<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\DataTable\Filter;

use Piwik\DataTable\BaseFilter;
use Piwik\DataTable\Row;
use Piwik\DataTable\Simple;
use Piwik\DataTable;
use Piwik\Metrics;

/**
 * Sorts a {@link DataTable} based on the value of a specific column.
 *
 * It is possible to specify a natural sorting (see [php.net/natsort](http://php.net/natsort) for details).
 *
 * @api
 */
class Sort extends BaseFilter
{
    protected $columnToSort;
    protected $order;
    protected $sign;

    const ORDER_DESC = 'desc';
    const ORDER_ASC  = 'asc';

    /**
     * Constructor.
     *
     * @param DataTable $table The table to eventually filter.
     * @param string $columnToSort The name of the column to sort by.
     * @param string $order order `'asc'` or `'desc'`.
     * @param bool $naturalSort Whether to use a natural sort or not (see {@link http://php.net/natsort}).
     * @param bool $recursiveSort Whether to sort all subtables or not.
     */
    public function __construct($table, $columnToSort, $order = 'desc', $naturalSort = true, $recursiveSort = true)
    {
        parent::__construct($table);

        if ($recursiveSort) {
            $table->enableRecursiveSort();
        }

        $this->columnToSort = $columnToSort;
        $this->naturalSort  = $naturalSort;
        $this->setOrder($order);
    }

    /**
     * Updates the order
     *
     * @param string $order asc|desc
     */
    public function setOrder($order)
    {
        if ($order == 'asc') {
            $this->order = 'asc';
            $this->sign  = 1;
        } else {
            $this->order = 'desc';
            $this->sign  = -1;
        }
    }

    /**
     * Sorting method used for sorting numbers
     *
     * @param array $rowA  array[0 => value of column to sort, 1 => label, 2 => key]
     * @param array $rowB  array[0 => value of column to sort, 1 => label, 2 => key]
     * @return int
     */
    public function numberSort($rowA, $rowB)
    {
        if (isset($rowA[0]) && isset($rowB[0])) {
            if ($rowA[0] != $rowB[0] || !isset($rowA[1])) {
                return $this->sign * ($rowA[0] < $rowB[0] ? -1 : 1);
            } else {
                $cmp = -1 * $this->sign * strnatcasecmp($rowA[1], $rowB[1]);

                if ($cmp == 0) {
                    return ($rowA[2] > $rowB[2] ? 1 : -1);
                }

                return $cmp;
            }
        } elseif (!isset($rowB[0]) && !isset($rowA[0])) {
            $cmp = -1 * $this->sign * strnatcasecmp($rowA[1], $rowB[1]);

            if ($cmp == 0) {
                return ($rowA[2] > $rowB[2] ? 1 : -1);
            }

            return $cmp;
        } elseif (!isset($rowA[0])) {
            return 1;
        }

        return -1;
    }

    /**
     * Sorting method used for sorting values natural
     *
     * @param array $rowA  array[0 => value of column to sort, 1 => key]
     * @param array $rowB  array[0 => value of column to sort, 1 => key]
     * @return int
     */
    public function naturalSort($valA, $valB)
    {
        if (!isset($valA[0]) && !isset($valB[0])) {
            return ($valA[1] > $valB[1] ? 1 : -1);
        }

        if (!isset($valA[0])) {
            return 1;
        }

        if (!isset($valB[0])) {
            return -1;
        }

        $cmp = $this->sign * strnatcasecmp($valA[0], $valB[0]);

        if ($cmp == 0) {
            return ($valA[1] > $valB[1] ? 1 : -1);
        }

        return $cmp;
    }

    /**
     * Sorting method used for sorting values
     *
     * @param array $rowA  array[0 => value of column to sort, 1 => key]
     * @param array $rowB  array[0 => value of column to sort, 1 => key]
     * @return int
     */
    public function sortString($valA, $valB)
    {
        if (!isset($valA[0]) && !isset($valB[0])) {
            return ($valA[1] > $valB[1] ? 1 : -1);
        }

        if (!isset($valA[0])) {
            return 1;
        }

        if (!isset($valB[0])) {
            return -1;
        }

        $cmp = $this->sign * strcasecmp($valA[0], $valB[0]);

        if ($cmp == 0) {
            return ($valA[1] > $valB[1] ? 1 : -1);
        }

        return $cmp;
    }

    protected function getColumnValue(Row $row)
    {
        $value = $row->getColumn($this->columnToSort);

        if ($value === false || is_array($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Sets the column to be used for sorting
     *
     * @param Row $row
     * @return int
     */
    protected function selectColumnToSort($row)
    {
        $value = $row->hasColumn($this->columnToSort);
        if ($value) {
            return $this->columnToSort;
        }

        $columnIdToName = Metrics::getMappingFromNameToId();
        // sorting by "nb_visits" but the index is Metrics::INDEX_NB_VISITS in the table
        if (isset($columnIdToName[$this->columnToSort])) {
            $column = $columnIdToName[$this->columnToSort];
            $value = $row->hasColumn($column);

            if ($value) {
                return $column;
            }
        }

        // eg. was previously sorted by revenue_per_visit, but this table
        // doesn't have this column; defaults with nb_visits
        $column = Metrics::INDEX_NB_VISITS;
        $value = $row->hasColumn($column);
        if ($value) {
            return $column;
        }

        // even though this column is not set properly in the table,
        // we select it for the sort, so that the table's internal state is set properly
        return $this->columnToSort;
    }

    /**
     * See {@link Sort}.
     *
     * @param DataTable $table
     * @return mixed
     */
    public function filter($table)
    {
        if ($table instanceof Simple) {
            return;
        }

        if (empty($this->columnToSort)) {
            return;
        }

        if (!$table->getRowsCount()) {
            return;
        }

        $row = $table->getFirstRow();
        if ($row === false) {
            return;
        }

        $this->columnToSort = $this->selectColumnToSort($row);

        $value = $this->getFirstValueFromDataTable($table);

        if (is_numeric($value) && $this->columnToSort !== 'label') {
            $methodToUse = "numberSort";
        } else {
            if ($this->naturalSort) {
                $methodToUse = "naturalSort";
            } else {
                $methodToUse = "sortString";
            }
        }

        $this->sort($table, $methodToUse);
    }

    private function getFirstValueFromDataTable($table)
    {
        foreach ($table->getRowsWithoutSummaryRow() as $row) {
            $value = $this->getColumnValue($row);
            if (!is_null($value)) {
                return $value;
            }
        }
    }

    /**
     * Sorts the DataTable rows using the supplied callback function.
     *
     * @param string $functionCallback A comparison callback compatible with {@link usort}.
     * @param string $columnSortedBy The column name `$functionCallback` sorts by. This is stored
     *                               so we can determine how the DataTable was sorted in the future.
     */
    private function sort(DataTable $table, $functionCallback)
    {
        $table->setTableSortedBy($this->columnToSort);

        $rows = $table->getRowsWithoutSummaryRow();

        // get column value and label only once for performance tweak
        $values = array();
        if ($functionCallback === 'numberSort') {
            foreach ($rows as $key => $row) {
                $values[$key] = array($this->getColumnValue($row), $row->getColumn('label'), $key);
            }
        } else {
            foreach ($rows as $key => $row) {
                $values[$key] = array($this->getColumnValue($row), $key);
            }
        }

        uasort($values, array($this, $functionCallback));

        $sortedRows = array();
        foreach ($values as $key => $value) {
            $sortedRows[] = $rows[$key];
        }

        $table->setRows($sortedRows);

        unset($rows);
        unset($sortedRows);

        if ($table->isSortRecursiveEnabled()) {
            foreach ($table->getRowsWithoutSummaryRow() as $row) {
                $subTable = $row->getSubtable();
                if ($subTable) {
                    $subTable->enableRecursiveSort();
                    $this->sort($subTable, $functionCallback);
                }
            }
        }
    }
}
