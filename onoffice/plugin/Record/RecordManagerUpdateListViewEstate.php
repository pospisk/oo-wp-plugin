<?php

/**
 *
 *    Copyright (C) 2017 onOffice GmbH
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace onOffice\WPlugin\Record;

use onOffice\WPlugin\DataView\DataListView;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class RecordManagerUpdateListViewEstate
	extends RecordManagerUpdate
{
	/**
	 *
	 * @param DataListView $pDataViewList
	 * @return bool
	 *
	 */

	public function updateByDataListView(DataListView $pDataViewList)
	{
		$row = array(
			'name' => $pDataViewList->getName(),
			'sortby' => $pDataViewList->getSortby(),
			'sortorder' => $pDataViewList->getSortOrder(),
			'show_status' => $pDataViewList->getShowStatus(),
			'list_type' => $pDataViewList->getListType(),
			'template' => $pDataViewList->getTemplate(),
			'recordsPerPage' => $pDataViewList->getRecordsPerPage(),
			'random' => $pDataViewList->getRandom(),
		);

		$tableRow = [
			self::TABLENAME_LIST_VIEW => $row,
			self::TABLENAME_PICTURETYPES => $pDataViewList->getPictureTypes(),
			self::TABLENAME_FIELDCONFIG => $pDataViewList->getFields(),
			self::TABLENAME_LISTVIEW_CONTACTPERSON => $pDataViewList->getAddressFields(),
		];

		return $this->updateByRow($tableRow);
	}


	/**
	 *
	 * @param int $listviewId
	 * @param array $tableRow
	 * @return bool success
	 *
	 */

	public function updateByRow($tableRow)
	{
		$prefix = $this->getTablePrefix();
		$pWpDb = $this->getWpdb();
		$whereListviewTable = array('listview_id' => $this->getRecordId());
		$result = $pWpDb->update($prefix.self::TABLENAME_LIST_VIEW,
			$tableRow[self::TABLENAME_LIST_VIEW], $whereListviewTable);

		if (array_key_exists(self::TABLENAME_FIELDCONFIG, $tableRow)) {
			$fields = $tableRow[self::TABLENAME_FIELDCONFIG];
			$pWpDb->delete($prefix.self::TABLENAME_FIELDCONFIG, $whereListviewTable);
			foreach ($fields as $fieldRow) {
				$table = $prefix.self::TABLENAME_FIELDCONFIG;
				$pWpDb->insert($table, $fieldRow);
			}
		}

		if (array_key_exists(self::TABLENAME_PICTURETYPES, $tableRow)) {
			$pictures = $tableRow[self::TABLENAME_PICTURETYPES];
			$pWpDb->delete($prefix.self::TABLENAME_PICTURETYPES, $whereListviewTable);
			foreach ($pictures as $pictureRow) {
				$table = $prefix.self::TABLENAME_PICTURETYPES;
				if (is_array($pictureRow)) {
					$pWpDb->insert($table, $pictureRow);
				}
			}
		}

		if (array_key_exists(self::TABLENAME_LISTVIEW_CONTACTPERSON, $tableRow)) {
			$contactPerson = $tableRow[self::TABLENAME_LISTVIEW_CONTACTPERSON];
			$pWpDb->delete($prefix.self::TABLENAME_LISTVIEW_CONTACTPERSON, $whereListviewTable);
			foreach ($contactPerson as $contactPersonRow) {
				$table = $prefix.self::TABLENAME_FIELDCONFIG;
				$pWpDb->insert($table, $contactPersonRow);
			}
		}

		return $result !== false;
	}
}
