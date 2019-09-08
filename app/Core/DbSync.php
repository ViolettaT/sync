<?php

namespace app\Core;

use app\Exceptions\NoConnectionsException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;

class DbSync
{
    private $connections;
    private $primaryContent;
    private $secondaryContent = [];
    private $connectionErrors = [];
    private $logger;
    private $errorLogger;
    private $unstaged = [];
    private $currentConnectionName;

    public function __construct(array $primaryParams, array $params = null)
    {
        $this->connections = Connections::getInstance();
        $this->connections::setPrimaryConnection($primaryParams);
        if (!is_null($params)) {
            foreach ($params as $param) {
                $this->connections::addConnection($param);
                $this->logger[$param['name']] = new Logger($param["name"]);
            }
        }
    }

    /**
     * @return array
     */
    public function getConnections()
    {
        return $this->connections::getConnections();
    }

    private function checkConnections()
    {
        $connections = $this->getConnections();
        $primaryConnection = $this->connections::getPrimaryConnection();
        foreach ($connections as $name => $connection) {
            try {
                $primaryConnection->connect();
                $connection->connect();
            } catch (\Exception $e) {
                $this->connectionErrors[$name] = [$name, $e->getMessage()];
            }
        }
    }

    public function syncBases()
    {
        $this->syncBasesIteration();
        if (!empty($this->unstaged)) {
            $this->unstaged = [];
            $this->syncBasesIteration();
        }
    }

    /**
     * @throws NoConnectionsException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function syncBasesIteration()
    {
        $errors = 0;
        $this->errorLogger = new Logger('error');
        $this->checkConnections();
        if (!empty($this->connectionErrors)) {
            //log error + user logic            
            $errorMessage = $this->connectionErrors;
            $out = implode(array_map(function ($a) {
                return implode(". ", $a);
            }, $errorMessage));
            $this->errorLogger->logMessage($out);
        } else {
            $connections = $this->connections::getConnections();
            if (empty($connections)) {
                throw new NoConnectionsException();
            }
            $primaryConnection = $this->connections::getPrimaryConnection();
            $this->primaryContent = $this->retrieveDatabase($primaryConnection);

            foreach ($connections as $name => $connection) {
                $this->currentConnectionName = $name;
                $this->secondaryContent[$name] = $this->retrieveDatabase($connection);
                $connection->beginTransaction();
                try {
                    if (empty($this->secondaryContent[$name])) {
                        $this->fillSecondaryDB($connection);
                    } else {
                        $this->compareAndSync($name, $connection);
                    }
                } catch (\Exception $e) {
                    $errors++;
                    // log error                     
                    $this->errorLogger->logMessage($e->getMessage());
                }
            }

            foreach ($connections as $name => $connection) {
                if ($errors > 0) {
                    $connection->rollBack();
                    $logDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'logger' . DIRECTORY_SEPARATOR;
                    if (file_exists($logDir . $name . '.log')) {
                        unlink($logDir . $name . '.log');
                    }
                } else {
                    $connection->commit();
                }
            }
        }
    }

    /**
     * @param Connection $connection
     * @return array
     */
    private function retrieveDatabase(Connection $connection)
    {
        $schemeManager = $connection->getSchemaManager();
        $tablesList = $schemeManager->listTables();
        $database = [];
        if (!empty($tablesList)) {
            foreach ($tablesList as $table) {
                $queryBuilder = $connection->createQueryBuilder();
                $items = $connection->fetchAll($queryBuilder->select('*')->from($table->getName()));
                $database[strtolower($table->getName())] = $items;
            }
        }
        return $database;
    }

    /**
     * @param Connection $secondaryConnection
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fillSecondaryDB(Connection $secondaryConnection)
    {
        $primaryConnection = $this->connections::getPrimaryConnection();
        $primarySchemeManager = $primaryConnection->getSchemaManager();

        foreach ($primarySchemeManager->listTables() as $table) {
            $this->createTable($table, $secondaryConnection);
            $this->fillTable($table, $secondaryConnection);
        }
        $this->updateKeys($secondaryConnection);
    }

    /**
     * @param array $primary
     * @param array $secondary
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function compareAndSync(string $name, Connection $connection)
    {
        $primary = $this->primaryContent;
        $secondary = $this->secondaryContent[$name];
        $primaryConnection = $this->connections::getPrimaryConnection();
        $primarySchemeManager = $primaryConnection->getSchemaManager();
        $tables = $primarySchemeManager->listTables();
        $secondaryTables = $connection->getSchemaManager()->listTables();
        $primaryTablesList = [];
        foreach ($tables as $table) {
            $tableName = strtolower($table->getName());
            foreach ($secondaryTables as $stable) {
                if (strtolower($stable->getName()) == strtolower($table->getName())) {
                    $secondaryTable = $stable;
                }
            }
            $primaryTablesList[] = $tableName;
            $primaryTableContent = $primary[$tableName];
            $secondaryTableContent = $secondary[$tableName] ?? null;
            $proceedRows = [];
            if (is_null($secondaryTableContent)) {
                $this->createTable($table, $connection);
                $this->fillTable($table, $connection);
            } elseif (empty($secondaryTableContent)) {
                $this->fillTable($table, $connection);
            } else {
                $isAffected = $this->compareColumns($connection, $table);
                if ($isAffected) {
                    $secondary = $this->retrieveDatabase($connection);
                    $this->secondaryContent[$name] = $secondary;
                    $secondaryTableContent = $secondary[$tableName];
                }
                $primaryKey = $table->getPrimaryKeyColumns()[0];
                $primaryKeyType = $table->getColumn($primaryKey)->getType()->getName();
                $secondaryKey = strtolower($primaryKey);
                $rowUpdate = 0;
                foreach ($primaryTableContent as $row) {
                    foreach ($secondaryTableContent as $secondaryRow) {
                        $primaryKeyValue = trim($row[$primaryKey]);
                        $secondaryKeyValue = trim($secondaryRow[$secondaryKey] ?? $secondaryRow[$primaryKey]);
                        if (empty($secondaryKeyValue)) {
                            foreach ($secondaryRow as $secKey => $sval) {
                                if (strtolower($secKey) == strtolower($primaryKey)) {
                                    $secondaryKey = $secKey;
                                }
                            }
                            $secondaryKeyValue = $secondaryRow[$secondaryKey];
                        }
                        if ($primaryKeyType == 'datetime') {
                            $datePrime = new \DateTime($primaryKeyValue);
                            $dateSecPrime = new \DateTime($secondaryKeyValue);
                            $primaryKeyValue = $datePrime->format('Y-m-d H:i:s');
                            $secondaryKeyValue = $dateSecPrime->format('Y-m-d H:i:s');
                        }

                        if ($primaryKeyValue == $secondaryKeyValue) {
                            $proceedRows[] = $row[$primaryKey];
                            foreach ($row as $column => $value) {
                                if (!in_array($column, $this->unstaged)) {
                                    $isMsSqlDate = ($this->connections::getPrimaryConnection()->getDatabasePlatform()->getName() == 'mssql' || $connection->getDatabasePlatform()->getName() == 'mssql') && $secondaryTable->getColumn($column)->getType()->getName() == 'datetime';
                                    $secondaryValue = trim($secondaryRow[strtolower($column)] ?? $secondaryRow[$column]);
                                    if ($isMsSqlDate) {
                                        $date = new \DateTime($value);
                                        $dateSecondary = new \DateTime($secondaryValue);
                                        $secondaryValue = $dateSecondary->format('Y-m-d H:i:s');
                                    }
                                    if (trim($value) == trim($secondaryValue) || ($isMsSqlDate && $date->format('Y-m-d H:i:s') == $dateSecondary->format('Y-m-d H:i:s'))) {
                                        continue;
                                    } else {
                                        $foundDuplicates = [];
                                        if ($isMsSqlDate) {
                                            $value = $date->format('Ymd H:i:s');
                                        }
                                        foreach ($secondaryTableContent as $sRow) {
                                            $primaryKeyValue = trim($row[$primaryKey]);
                                            $secondaryKeyValue = trim($secondaryRow[$secondaryKey] ?? $secondaryRow[$primaryKey]);

                                            if ($primaryKeyType == 'datetime') {
                                                $datePrime = new \DateTime($primaryKeyValue);
                                                $dateSecPrime = new \DateTime($secondaryKeyValue);
                                                $primaryKeyValue = $datePrime->format('Y-m-d H:i:s');
                                                $secondaryKeyValue = $dateSecPrime->format('Y-m-d H:i:s');
                                            }

                                            if ($primaryKeyValue == $secondaryKeyValue) {
                                                $foundDuplicates[] = $sRow;
                                            } else if ($isMsSqlDate && $date->format('Y-m-d H:i:s') == $dateSecondary->format('Y-m-d H:i:s')) {
                                                $foundDuplicates[] = $sRow;
                                            }
                                        }
                                        $presentValue = false;
                                        foreach ($foundDuplicates as $dRow) {
                                            foreach ($dRow as $dColumn => $dValue) {
                                                if (strtolower($dColumn) == strtolower($column)) {
                                                    $isMsSqlDate = ($this->connections::getPrimaryConnection()->getDatabasePlatform()->getName() == 'mssql' || $connection->getDatabasePlatform()->getName() == 'mssql') && $secondaryTable->getColumn($dColumn)->getType()->getName() == 'datetime';
                                                    $secondaryValue = trim($dValue);
                                                    if ($isMsSqlDate) {
                                                        $date = new \DateTime($value);
                                                        $dateSecondary = new \DateTime($secondaryValue);
                                                        $secondaryValue = $dateSecondary->format('Y-m-d H:i:s');
                                                    }
                                                    if ($value == $secondaryValue) {
                                                        $presentValue = true;
                                                    } else if ($isMsSqlDate && $date->format('Y-m-d H:i:s') == $dateSecondary->format('Y-m-d H:i:s')) {
                                                        $presentValue = true;
                                                    }
                                                }
                                            }
                                        }

                                        if (!$presentValue) {
											if ($table->getColumn($column)->getType()->getName() == 'decimal') {
												$value = floatval(str_replace(',', '.', $value));												
											}
                                            $this->updateCell($connection, $table->getName(), $column, $value, "{$secondaryKey} = '{$row[$primaryKey]}'");
                                            $rowUpdate++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ($rowUpdate > 0) {
                    $this->logger[$this->currentConnectionName]->logMessage('UPDATE ' . $rowUpdate . ' rows IN ' . $table->getName() . ';');
                }

                $countRowIn = 0;
                foreach ($primaryTableContent as $row) {
                    if (!in_array($row[$primaryKey], $proceedRows)) {
                        $this->insertRow($connection, $table, $row);
                        $proceedRows[] = $row[$primaryKey];
                        $countRowIn++;
                    }
                }
                if ($countRowIn > 0) {
                    $this->logger[$this->currentConnectionName]->logMessage('INSERT INTO ' . $table->getName() . ' ' . $countRowIn . ' rows;');
                }

                $countRowDl = 0;
                foreach ($secondaryTableContent as $row) {
                    $isDeleteRow = false;
                    $additionalParamsForDelete = null;
                    if (!in_array($row[$secondaryKey], $proceedRows) && !in_array($row[$primaryKey], $proceedRows)) {
                        $foundInSecondary = false;
                        $primaryColumn = $table->getColumn($primaryKey) ?? $table->getColumn($secondaryKey);
                        $secondaryTables = $connection->getSchemaManager()->listTables();
                        $secondaryTable = null;
                        foreach ($secondaryTables as $sTable) {
                            if (strtolower($sTable->getName()) == $tableName) {
                                $secondaryTable = $sTable;
                                break;
                            }
                        }
                        $secondaryColumn = $secondaryTable->getColumn($primaryColumn->getName()) ?? $secondaryTable->getColumn(strtolower($primaryColumn->getName()));
                        $isMsSqlDate = ($this->connections::getPrimaryConnection()->getDatabasePlatform()->getName() == 'mssql' || $connection->getDatabasePlatform()->getName() == 'mssql') && ($secondaryColumn->getType()->getName() == 'datetime' || $primaryColumn->getType()->getName() == 'datetime');
                        if ($isMsSqlDate) {
                            $thisDate = $row[$primaryKey] ?? $row[$secondaryKey];
                            $unifyDate = (new \DateTime($thisDate))->format('Y-m-d H:i:s');
                            foreach ($proceedRows as $pRow) {
                                $unifyProceedRow = (new \DateTime($pRow))->format('Y-m-d H:i:s');
                                if ($unifyProceedRow == $unifyDate) {
                                    $foundInSecondary = true;
                                }
                            }
                        }
                        if (!$foundInSecondary) {
                            $isDeleteRow = true;
                            $additionalParamsForDelete = $row;
                            unset($additionalParamsForDelete[$secondaryKey]);
                        }
                    }

                    if ($isDeleteRow) {
                        $arg = $row[$primaryKey] ?? $row[$secondaryKey];
                        $this->deleteRow($connection, $table->getName(), "{$secondaryKey} = '{$arg}'", $additionalParamsForDelete);
                        $countRowDl++;
                    }
                }
                if ($countRowDl > 0) {
                    $this->logger[$this->currentConnectionName]->logMessage('DELETE ' . $countRowDl . ' rows FROM ' . $table->getName() . ';');
                }
            }
        }
        foreach ($secondaryTables as $table) {
            if (!in_array(strtolower($table->getName()), $primaryTablesList)) {
                $this->dropTable($table, $connection);
                $this->logger[$this->currentConnectionName]->logMessage('DROP ' . $table->getName());
            }
        }
        $this->updateKeys($connection);
    }

    private function compareColumns(Connection $connection, Table $table)
    {
        $secondarySchemeManager = $connection->getSchemaManager();
        $secondaryTable = null;
        foreach ($secondarySchemeManager->listTables() as $sTable) {
            if (strtolower($sTable->getName()) == strtolower($table->getName())) {
                $secondaryTable = $sTable;
            }
        }
        $secondaryColumns = $secondaryTable->getColumns();
        $columns = $table->getColumns();
        $affected = false;
        foreach ($columns as $column) {
            try {
                $secondaryTable->getColumn($column->getName());
            } catch (SchemaException $e) {
                if ($e->getCode() == 30) { //30 - Exception code 'There is no column with name ...'
                    $this->unstaged[] = $column->getName();
                    $queries = $connection
                        ->getSchemaManager()
                        ->createSchema()
                        ->getMigrateToSql($this->connections::getPrimaryConnection()
                            ->getSchemaManager()
                            ->createSchema(), $connection->getDatabasePlatform());
                    foreach ($queries as $query) {
                        $query = preg_replace('/(\bCOLLATE\b\s\S+)([^(\s|,)]+)/', '', $query);
                        if ($this->blockAlterTable($query)) {
                            continue;
                        } else {
                            $connection->query($query);
                        }
                    }
                    $affected = true;
                    $this->logger[$this->currentConnectionName]->logMessage('INSERT ' . $column->getName() . ' column INTO ' . $table->getName());
                }
            }
        }

        foreach ($secondaryColumns as $column) {
            try {
                $table->getColumn($column->getName());
            } catch (SchemaException $e) {
                if ($e->getCode() == 30) { //30 - Exception code 'There is no column with name ...'
                    $secondaryTable->dropColumn($column->getName());
                    $this->logger[$this->currentConnectionName]->logMessage('DROP ' . $column->getName() . ' column FROM ' . $table->getName());
                }
            }
        }
        return $affected;
    }

    /**
     * @param Table $primary
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createTable(Table $primary, Connection $connection)
    {
        $connection->getDatabasePlatform()->getName();
        $secondarySchemeManager = $connection->getSchemaManager();
        foreach ($primary->getColumns() as $column) {
            $collationCurrent = $column->getPlatformOptions()['collation'] ?? null;
            if ($this->connections::getPrimaryConnection()->getDatabasePlatform()->getName() === 'mysql' && $column->getType()->getName() === 'boolean') {
                $column->setType(\Doctrine\DBAL\Types\Type::getType('smallint'));
            }
        }
        foreach ($secondarySchemeManager->getDatabasePlatform()->getCreateTableSQL($primary) as $query) {
            $query = preg_replace('/(\bCOLLATE\b\s\S+)([^(\s|,)]+)/', '', $query);
            $connection->query($query);
            $this->logger[$this->currentConnectionName]->logMessage($query);
        }
    }

    /**
     * @param Table $table
     * @param Connection $secondaryConnection
     */
    private function fillTable(Table $table, Connection $secondaryConnection)
    {
        $this->compareColumns($secondaryConnection, $table);
        $primaryConnection = $this->connections::getPrimaryConnection();
        $queryBuilder = $primaryConnection->createQueryBuilder();
        $selectQuery = $queryBuilder->select('*')->from($table->getName());
        $tableItems = $primaryConnection->fetchAll($selectQuery);
        $columns = $table->getColumns();
        if (!empty($tableItems[0])) {
            $insertQuery = $secondaryConnection->createQueryBuilder();
            $keys = array_keys($tableItems[0]);
            $preparedKeys = [];
            foreach ($keys as $key) {
                if (!in_array($key, $this->unstaged)) $preparedKeys[$key] = '?';
            }
            $insertQuery->insert($table->getName())->values($preparedKeys);
            $row = 0;
            foreach ($tableItems as $item) {
                $counter = 0;
                foreach ($item as $key => $value) {
                    if (!in_array($key, $this->unstaged)) {
                        foreach ($columns as $column) {
                            $typeName = $column->getType()->getName();							
                            if ($column->getName() == $key && ($typeName == 'decimal' || $typeName == 'float')) {
                                $value = floatval($value);
                            }
                        }
                        $insertQuery->setParameter($counter, $value);
                        $counter++;
                    }
                }
                $insertQuery->execute();
                $row++;
            }
            $this->logger[$this->currentConnectionName]->logMessage('INSERT INTO ' . $table->getName() . ' ' . $row . ' rows;');
        }
    }


    /**
     * @param Connection $connection
     * @param string $tableName
     * @param string $field
     * @param $value
     * @param string $condition (... WHERE '<fieldName> = <condition value>')
     */
    private function updateCell(Connection $connection, string $tableName, string $field, $value, string $condition)
    {
        $connection
            ->createQueryBuilder()
            ->update($tableName)
            ->set($field, '?')
            ->where($condition)
            ->setParameter(0, $value)
            ->execute();
    }

    private function insertRow(Connection $connection, Table $table, array $row)
    {
        $query = $connection->createQueryBuilder();
        $keys = array_keys($row);
        $preparedKeys = [];
        foreach ($keys as $key) {
            if (!in_array($key, $this->unstaged)) $preparedKeys[$key] = '?';
        }
        $columns = $table->getColumns();
        $query->insert($table->getName())->values($preparedKeys);
        $counter = 0;
        foreach ($row as $key => $value) {
            if (!in_array($key, $this->unstaged)) {
                foreach ($columns as $column) {
                    $typeName = $column->getType()->getName();				
                    if ($column->getName() == $key && ($typeName == 'decimal' || $typeName == 'float')) {
                        $value = floatval($value);
                    }
                }
                $query->setParameter($counter, $value);
                $counter++;
            }
        }
        $query->execute();
    }

    private function deleteRow(Connection $connection, string $tableName, string $condition, array $additionalConditions = null)
    {
        $query = $connection->createQueryBuilder();
        $query->delete($tableName)->where($condition);
        if (!empty($additionalConditions)) {
            foreach ($additionalConditions as $column => $value) {
                $query->andWhere("{$column} = '{$value}'");
            }
        }
        $query->execute();
    }

    private function dropTable(Table $table, Connection $connection)
    {
        $connection->query($connection->getSchemaManager()->getDatabasePlatform()->getDropTableSQL($table->getName()));
    }

    private function updateKeys(Connection $secondaryConnection)
    {
        $primarySchemeManager = $this->connections::getPrimaryConnection()->getSchemaManager();
        $secondarySchemeManager = $secondaryConnection->getSchemaManager();
        $comparator = new \Doctrine\DBAL\Schema\Comparator();
        $schemaDiff = $comparator->compare($secondarySchemeManager->createSchema(), $primarySchemeManager->createSchema());
        $query = $schemaDiff->toSaveSql($secondarySchemeManager->getDatabasePlatform());
        if (is_array($query)) {
            foreach ($query as $part) {
                if ($this->blockAlterTable($part) || strpos($part, 'CREATE TABLE')
                    || ($secondaryConnection->getDatabasePlatform()->getName() === 'mysql' && strpos($part, 'BOOLEAN'))
                    || ($secondaryConnection->getDatabasePlatform()->getName() === 'postgresql' && strpos($part, 'BOOLEAN'))) {
                    continue;
                } else {
                    $secondaryConnection->query($part);
                }
            }
        } else {
            $secondaryConnection->query($query);
        }
    }

    private function blockAlterTable(string $query)
    {
        return ($this->connections::getPrimaryConnection()->getDatabasePlatform()->getName() === 'mysql' && strpos($query, 'BOOLEAN'))
            || strpos($query, 'COLLATE')
            || ($this->connections::getPrimaryConnection()->getDatabasePlatform()->getName() === 'mysql' && strpos($query, 'NUMBER(1)'))
            || ($this->connections::getPrimaryConnection()->getDatabasePlatform()->getName() === 'mysql' && strpos($query, 'BIT'))
            || strpos($query, 'CREATE SCHEMA') === 0;
    }
}