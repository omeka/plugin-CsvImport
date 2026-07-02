<?php
/**
 * CsvImport_ColumnMap_Contribution class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_Contribution extends CsvImport_ColumnMap
{
    const CONTRIBUTION_DELIMITER_OPTION_NAME = 'csv_import_tag_delimiter';
    const DEFAULT_CONTRIBUTION_DELIMITER = ',';

    private $_contributionDelimiter;

    /**
     * @param string $columnName
     * @param string $tagDelimiter
     */
    public function __construct($columnName, $tagDelimiter = null)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_CONTRIBUTION;
        if ($tagDelimiter !== null) {
            $this->_contributionDelimiter = $tagDelimiter;
        } else {
            $this->_contributionDelimiter = self::getDefaultContributionDelimiter();
        }
    }

    /**
     * Map a row to an array of contribution values.
     *
     * @param array $row The row to map
     * @param array $result
     * @return array The array of contribution values
     */
    public function map($row, $result)
    {
        if ($this->_contributionDelimiter == '') {
            $rawContribution = array($row[$this->_columnName]);
        } else {
            $rawContribution = explode($this->_contributionDelimiter, $row[$this->_columnName]);
        }
        $trimmed = array_map('trim', $rawContribution);
        $cleaned = array_diff($trimmed, array(''));
        return array_merge($result, $cleaned);
    }

    /**
     * Return the contribution delimiter.
     *
     * @return string The contribution delimiter
     */
    public function getContributionDelimiter()
    {
        return $this->_contributionDelimiter;
    }

    /**
     * Returns the default contribution delimiter.
     * Uses the default delimiter specified in the options table if available.
     *
     * @return string The default contribution delimiter
     */
    static public function getDefaultContributionDelimiter()
    {
        if (!($delimiter = get_option(self::CONTRIBUTION_DELIMITER_OPTION_NAME))) {
            $delimiter = self::DEFAULT_CONTRIBUTION_DELIMITER;
        }
        return $delimiter;
    }
}
