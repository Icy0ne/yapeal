<?php
/**
 * Contains EveApiToolsTrait Trait.
 *
 * PHP version 5.4
 *
 * LICENSE:
 * This file is part of 1.1.x-WIP
 * Copyright (C) 2014 Michael Cummings
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with this program. If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * You should be able to find a copy of this license in the LICENSE.md file. A copy of the GNU GPL should also be
 * available in the GNU-GPL.md file.
 *
 * @copyright 2014 Michael Cummings
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace Yapeal\Database;

use LogicException;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Trait EveApiToolsTrait
 */
trait EveApiToolsTrait
{
    /**
     * @param CommonSqlQueries $value
     *
     * @return self
     */
    public function setCsq($value)
    {
        $this->csq = $value;
        return $this;
    }
    /**
     * @param PDO $value
     *
     * @return self
     */
    public function setPdo(PDO $value)
    {
        $this->pdo = $value;
        return $this;
    }
    /**
     * @param string[] $columns
     * @param string[] $columnNames
     * @param string   $tableName
     * @param int      $rowCount
     *
     * @throws LogicException
     * @return self
     */
    protected function flush(
        array $columns,
        array $columnNames,
        $tableName,
        $rowCount = 1
    ) {
        if (empty($columns)) {
            return $this;
        }
        $mess = sprintf(
            'Have %1$s row(s) to upsert into %2$s table',
            $rowCount,
            $tableName
        );
        $this->getLogger()
             ->info($mess);
        $sql = $this->getCsq()
                    ->getUpsert(
                        $tableName,
                        $columnNames,
                        $rowCount
                    );
        $mess = preg_replace('/(,\(\?(?:,\?)*\))+/', ',...', $sql);
        $this->getLogger()
             ->info($mess);
        $mess = implode(',', $columns);
        $this->getLogger()
             ->debug(substr($mess, 0, 255));
        $stmt = $this->getPdo()
                     ->prepare($sql);
        $stmt->execute($columns);
        return $this;
    }
    /**
     * @throws LogicException
     * @return CommonSqlQueries
     */
    protected function getCsq()
    {
        if (empty($this->csq)) {
            $mess = 'Tried to use csq before it was set';
            throw new LogicException($mess);
        }
        return $this->csq;
    }
    /**
     * @throws LogicException
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        if (empty($this->logger)) {
            $mess = 'Tried to use logger before it was set';
            throw new LogicException($mess);
        }
        return $this->logger;
    }
    /**
     * @throws LogicException
     * @return PDO
     */
    protected function getPdo()
    {
        if (empty($this->pdo)) {
            $mess = 'Tried to use pdo before it was set';
            throw new LogicException($mess);
        }
        return $this->pdo;
    }
    /**
     * @var CommonSqlQueries
     */
    protected $csq;
    /**
     * @var PDO
     */
    protected $pdo;
}
