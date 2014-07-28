<?php
/**
 * Contains MemberTrackingExtended class.
 *
 * PHP version 5.3
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
 * @author    Stephen Gulick <stephenmg12@gmail.com>
 */
namespace Yapeal\Database\Corp;

use Yapeal\Database\AttributesDatabasePreserverTrait;
use Yapeal\Database\EveApiNameTrait;
use Yapeal\Xml\EveApiPreserverInterface;
use Yapeal\Xml\EveApiReadWriteInterface;
use Yapeal\Xml\EveApiRetrieverInterface;

/**
 * Class MemberTrackingExtended
 */
class MemberTrackingExtended extends AbstractCorpSection
{
    use EveApiNameTrait, AttributesDatabasePreserverTrait;
    /**
     * @param EveApiReadWriteInterface $data
     * @param EveApiRetrieverInterface $retrievers
     * @param EveApiPreserverInterface $preservers
     *
     * @return bool
     */
    public function oneShot(
        EveApiReadWriteInterface &$data,
        EveApiRetrieverInterface $retrievers,
        EveApiPreserverInterface $preservers
    ) {
        $data->setEveApiName('MemberTracking');
        $data->addEveApiArgument('extended', '1');
        return parent::oneShot($data, $retrievers, $preservers);
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @internal param int $key
     *
     * @return self
     */
    protected function preserverToMemberTrackingExtended(
        $xml,
        $ownerID
    ) {
        $columnDefaults = array(
            'ownerID' => $ownerID,
            'characterID' => null,
            'name' => null,
            'startDateTime' => null,
            'baseID' => null,
            'base' => null,
            'title' => null,
            'logonDateTime' => '1970-01-01 00:00:01',
            'logoffDateTime' => '1970-01-01 00:00:01',
            'locationID' => null,
            'location' => null,
            'shipTypeID' => null,
            'shipType' => null,
            'roles' => null,
            'grantableRoles' => null
        );
        $this->attributePreserveData(
            $xml,
            $columnDefaults,
            'corpMemberTracking'
        );
        return $this;
    }
    /**
     * @var int $mask
     */
    protected $mask = 33554432;
}