<?php
/**
 * Contains CharacterSheet class.
 *
 * PHP version 5.4
 *
 * LICENSE:
 * This file is part of Yet Another Php Eve Api Library also know as Yapeal
 * which can be used to access the Eve Online API data and place it into a
 * database.
 * Copyright (C) 2014 Michael Cummings
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * You should be able to find a copy of this license in the LICENSE.md file. A
 * copy of the GNU GPL should also be available in the GNU-GPL.md file.
 *
 * @copyright 2014 Michael Cummings
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace Yapeal\Database\Char;

use LogicException;
use PDOException;
use Yapeal\Database\AttributesDatabasePreserverTrait;
use Yapeal\Database\EveApiNameTrait;
use Yapeal\Database\ValuesDatabasePreserverTrait;

/**
 * Class CharacterSheet
 */
class CharacterSheet extends AbstractCharSection
{
    use EveApiNameTrait, AttributesDatabasePreserverTrait, ValuesDatabasePreserverTrait;
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @throws LogicException
     * @return bool
     */
    protected function preserve(
        $xml,
        $ownerID
    ) {
        try {
            $this->getPdo()
                 ->beginTransaction();
            $this->preserverToCharacterSheet($xml, $ownerID)
                 ->preserverToAttributes($xml, $ownerID)
                 ->preserverToCertificates($xml, $ownerID)
                 ->preserverToCorporationRoles($xml, $ownerID)
                 ->preserverToCorporationTitles($xml, $ownerID)
                 ->preserverToImplants($xml, $ownerID)
                 ->preserverToJumpCloneImplants($xml, $ownerID)
                 ->preserverToJumpClones($xml, $ownerID)
                 ->preserverToSkills($xml, $ownerID);
            $this->getPdo()
                 ->commit();
        } catch (PDOException $exc) {
            $mess = sprintf(
                'Failed to upsert data from Eve API %1$s/%2$s for %3$s',
                strtolower($this->getSectionName()),
                $this->getApiName(),
                $ownerID
            );
            $this->getLogger()
                 ->warning($mess, ['exception' => $exc]);
            $this->getPdo()
                 ->rollBack();
            return false;
        }
        return true;
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @return self
     */
    protected function preserverToAttributes(
        $xml,
        $ownerID
    ) {
        $columnDefaults = [
            'charisma' => null,
            'intelligence' => null,
            'memory' => null,
            'ownerID' => $ownerID,
            'perception' => null,
            'willpower' => null
        ];
        $this->valuesPreserveData(
            $xml,
            $columnDefaults,
            'charAttributes',
            '//attributes/*'
        );
        return $this;
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @throws LogicException
     * @return self
     */
    protected function preserverToCertificates(
        $xml,
        $ownerID
    ) {
        $columnDefaults = [
            'ownerID' => $ownerID,
            'certificateID' => null
        ];
        $tableName = 'charCertificates';
        $sql = $this->getCsq()
                    ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
        $this->getLogger()
             ->info($sql);
        $this->getPdo()
             ->exec($sql);
        $this->attributePreserveData(
            $xml,
            $columnDefaults,
            $tableName,
            '//certificates/row'
        );
        return $this;
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @return self
     */
    protected function preserverToCharacterSheet(
        $xml,
        $ownerID
    ) {
        $columnDefaults = [
            'allianceID' => '0',
            'allianceName' => null,
            'ancestry' => null,
            'balance' => null,
            'bloodLine' => null,
            'characterID' => $ownerID,
            'cloneJumpDate' => null,
            'cloneName' => '',
            'cloneSkillPoints' => null,
            'cloneTypeID' => null,
            'corporationID' => null,
            'corporationName' => null,
            'DoB' => null,
            'factionID' => '0',
            'factionName' => null,
            'freeRespecs' => null,
            'freeSkillPoints' => null,
            'gender' => null,
            'homeStationID' => null,
            'lastRespecDate' => null,
            'lastTimedRespec' => null,
            'name' => null,
            'race' => null
        ];
        $this->valuesPreserveData(
            $xml,
            $columnDefaults,
            'charCharacterSheet'
        );
        return $this;
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @throws LogicException
     * @return self
     */
    protected function preserverToCorporationRoles(
        $xml,
        $ownerID
    ) {
        $columnDefaults = [
            'ownerID' => $ownerID,
            'roleID' => null,
            'roleName' => null
        ];
        $tableSuffixes = ['', 'AtBase', 'AtHQ', 'AtOther'];
        foreach ($tableSuffixes as $suffix) {
            $tableName = 'charCorporationRoles' . $suffix;
            $sql = $this->getCsq()
                        ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
            $this->getLogger()
                 ->info($sql);
            $this->getPdo()
                 ->exec($sql);
            $this->attributePreserveData(
                $xml,
                $columnDefaults,
                $tableName,
                '//corporationRoles' . $suffix . '/row'
            );
        };
        return $this;
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @throws LogicException
     * @return self
     */
    protected function preserverToCorporationTitles(
        $xml,
        $ownerID
    ) {
        $columnDefaults = [
            'ownerID' => $ownerID,
            'titleID' => null,
            'titleName' => null
        ];
        $tableName = 'charCorporationTitles';
        $sql = $this->getCsq()
                    ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
        $this->getLogger()
             ->info($sql);
        $this->getPdo()
             ->exec($sql);
        $this->attributePreserveData(
            $xml,
            $columnDefaults,
            $tableName,
            '//corporationTitles/row'
        );
        return $this;
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @throws LogicException
     * @return self
     */
    protected function preserverToImplants(
        $xml,
        $ownerID
    ) {
        $columnDefaults = [
            'ownerID' => $ownerID,
            'typeID' => null,
            'typeName' => null
        ];
        $tableName = 'charImplants';
        $sql = $this->getCsq()
                    ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
        $this->getLogger()
             ->info($sql);
        $this->getPdo()
             ->exec($sql);
        $this->attributePreserveData(
            $xml,
            $columnDefaults,
            $tableName,
            '//implants/row'
        );
        return $this;
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @throws LogicException
     * @return self
     */
    protected function preserverToJumpCloneImplants(
        $xml,
        $ownerID
    ) {
        $columnDefaults = [
            'jumpCloneID' => null,
            'ownerID' => $ownerID,
            'typeID' => null,
            'typeName' => null
        ];
        $tableName = 'charJumpCloneImplants';
        $sql = $this->getCsq()
                    ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
        $this->getLogger()
             ->info($sql);
        $this->getPdo()
             ->exec($sql);
        $this->attributePreserveData(
            $xml,
            $columnDefaults,
            $tableName,
            '//jumpCloneImplants/row'
        );
        return $this;
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @throws LogicException
     * @return self
     */
    protected function preserverToJumpClones(
        $xml,
        $ownerID
    ) {
        $columnDefaults = [
            'cloneName' => null,
            'jumpCloneID' => null,
            'locationID' => null,
            'ownerID' => $ownerID,
            'typeID' => null
        ];
        $tableName = 'charJumpClones';
        $sql = $this->getCsq()
                    ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
        $this->getLogger()
             ->info($sql);
        $this->getPdo()
             ->exec($sql);
        $this->attributePreserveData(
            $xml,
            $columnDefaults,
            $tableName,
            '//jumpClones/row'
        );
        return $this;
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @throws LogicException
     * @return self
     */
    protected function preserverToSkills(
        $xml,
        $ownerID
    ) {
        $columnDefaults = [
            'level' => null,
            'ownerID' => $ownerID,
            'published' => null,
            'skillpoints' => null,
            'typeID' => null
        ];
        $tableName = 'charSkills';
        $sql = $this->getCsq()
                    ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
        $this->getLogger()
             ->info($sql);
        $this->getPdo()
             ->exec($sql);
        $this->attributePreserveData(
            $xml,
            $columnDefaults,
            $tableName,
            '//skills/row'
        );
        return $this;
    }
    /**
     * @type int $mask
     */
    protected $mask = 8;
    /**
     * @type string $xsl
     */
    protected $xsl
        = <<<'XSL'
<xsl:transform version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml"
                version="1.0"
                encoding="utf-8"
                omit-xml-declaration="no"
                standalone="no"
                indent="yes"/>
    <xsl:variable name="lower">abcdefghijklmnopqrstuvwxyz</xsl:variable>
    <xsl:variable name="upper">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>
    <xsl:template match="result">
        <xsl:copy>
            <xsl:apply-templates select="child::*[not(*|@*)]">
                <xsl:sort select="translate(name(),$upper,$lower)"
                          data-type="text" order="ascending"/>
            </xsl:apply-templates>
            <xsl:apply-templates select="child::*[* and not(@*)]">
                <xsl:sort select="translate(name(),$upper,$lower)"
                          data-type="text" order="ascending"/>
            </xsl:apply-templates>
            <xsl:apply-templates select="child::*[@name]">
                <xsl:sort select="translate(@name,$upper,$lower)"
                          data-type="text" order="ascending"/>
            </xsl:apply-templates>
        </xsl:copy>
    </xsl:template>
    <xsl:template match="rowset">
        <xsl:choose>
            <xsl:when test="@name">
                <xsl:element name="{@name}">
                    <xsl:copy-of select="@key"/>
                    <xsl:copy-of select="@columns"/>
                    <xsl:choose>
                        <xsl:when test="@key='jumpCloneID'">
                            <xsl:apply-templates select="child::*">
                                <xsl:sort select="@jumpCloneID"
                                          data-type="number"/>
                                <xsl:sort select="@typeID" data-type="number"/>
                            </xsl:apply-templates>
                        </xsl:when>
                        <xsl:when test="@key='roleID'">
                            <xsl:apply-templates select="child::*">
                                <xsl:sort select="roleID" data-type="number"/>
                            </xsl:apply-templates>
                        </xsl:when>
                        <xsl:when test="@key='titleID'">
                            <xsl:apply-templates select="child::*">
                                <xsl:sort select="titleID" data-type="number"/>
                            </xsl:apply-templates>
                        </xsl:when>
                        <xsl:when test="@key='typeID'">
                            <xsl:apply-templates select="child::*">
                                <xsl:sort select="@typeID" data-type="number"/>
                            </xsl:apply-templates>
                        </xsl:when>
                    </xsl:choose>
                </xsl:element>
            </xsl:when>
            <xsl:otherwise>
                <xsl:copy-of select="."/>
                <xsl:apply-templates/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="attributes">
        <xsl:copy>
            <xsl:apply-templates select="child::*">
                <xsl:sort select="translate(name(),$upper,$lower)"
                          data-type="text" order="ascending"/>
            </xsl:apply-templates>
        </xsl:copy>
    </xsl:template>
    <xsl:template match="attributeEnhancers"/>
    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>
</xsl:transform>
XSL;
}
