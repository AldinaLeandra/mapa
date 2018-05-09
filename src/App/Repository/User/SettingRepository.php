<?php

/**
 * Ushahidi User Setting Repository
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Application
 * @copyright  2018 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */

namespace Ushahidi\App\Repository\User;

use Ohanzee\DB;
use Ushahidi\Core\Data;
use Ushahidi\Core\Entity;
use Ushahidi\Core\SearchData;
use Ushahidi\Core\Entity\UserSetting;
use Ushahidi\Core\Entity\UserSettingRepository as UserSettingRepositoryContract;
use Ushahidi\App\Repository\OhanzeeRepository;
use Ushahidi\App\Repository\JsonTranscodeRepository;

class SettingRepository extends OhanzeeRepository implements
	UserSettingRepositoryContract
{
	// OhanzeeRepository
	protected function getTable()
	{
		return 'user_settings';
	}

	// CreateRepository
	// ReadRepository
	public function getEntity(array $data = null)
	{
		return new UserSetting($data);
	}

	// SearchRepository
	public function getSearchFields()
	{
		return ['user_id'];
	}

	// OhanzeeRepository
	protected function setSearchConditions(SearchData $search)
	{
		$query = $this->search_query;

		if ($search->user_id) {
			$query->where('user_id', '=', $search->user_id);
		}
	}

	// UserSettingRepository
	public function updateCollection(array $entities)
	{
		if (empty($entities)) {
			return;
		}

		// Delete all existing user settings records
		// Assuming all entites have the same user id
		$this->deleteAllForUser(current($entities)->user_id);

		$query = DB::insert($this->getTable())
			->columns(array_keys(current($entities)->asArray()));

		foreach ($entities as $entity) {
			$query->values($entity->asArray());
		}

		$query->execute($this->db);

		return $entities;
	}

	// UserSettingRepository
	public function getByUser($user_id)
	{
		$query = $this->selectQuery(compact($user_id));
		$results = $query->execute($this->db);

		return $this->getCollection($results->as_array());
	}

	public function deleteAllForForm($user_id)
	{
		return $this->executeDelete(compact('user_id'));
	}

	// UserSettingRepository
	public function existsInUserSetting($user_id)
	{
		return (bool)$this->selectCount(compact('user_id'));
	}

	public function create(Entity $entity)
	{
		$record = $entity->asArray();
		$record['created'] = time();

		$id = $this->executeInsert($this->removeNullValues($record));

		return $id;
	}

	public function update(Entity $entity)
	{
		$record = $entity->asArray();
		$record['updated'] = time();

		return $this->executeUpdate(['id' => $entity->id], $record);
	}
}
