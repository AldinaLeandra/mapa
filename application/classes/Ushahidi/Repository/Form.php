<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Ushahidi Form Repository
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Application
 * @copyright  2014 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */

use Ushahidi\Core\Entity;
use Ushahidi\Core\Entity\Form;
use Ushahidi\Core\Entity\FormRepository;
use Ushahidi\Core\SearchData;

class Ushahidi_Repository_Form extends Ushahidi_Repository implements
    FormRepository
{
    // Ushahidi_Repository
    protected function getTable()
    {
        return 'forms';
    }

    // CreateRepository
    // ReadRepository
    public function getEntity(Array $data = null)
    {
	    if (isset($data["id"])) {
            $can_create = $this->getRolesThatCanCreatePosts($data['id']);
            $data = $data + [
	            'can_create' => $can_create['roles'],
            ];
	    }
        return new Form($data);
    }

    // SearchRepository
    public function getSearchFields()
    {
        return ['parent', 'q' /* LIKE name */];
    }

    // Ushahidi_Repository
    protected function setSearchConditions(SearchData $search)
    {
        $query = $this->search_query;

        if ($search->parent) {
            $query->where('parent_id', '=', $search->parent);
        }

        if ($search->q) {
            // Form text searching
            $query->where('name', 'LIKE', "%{$search->q}%");
        }
    }

    // CreateRepository
    public function create(Entity $entity)
    {
        // todo ensure default group is created

        return parent::create($entity->setState(['created' => time()]));
    }

    // UpdateRepository
    public function update(Entity $entity)
    {
        return parent::update($entity->setState(['updated' => time()]));
    }

    /**
     * Get total count of entities
     * @param  Array $where
     * @return int
     */
    public function getTotalCount(Array $where = [])
    {
        return $this->selectCount($where);
    }

    /**
      * Get value of Form property hide_author
      * if no form is found return false
      * @param  $form_id
      * @return Boolean
      */
    public function isAuthorHidden($form_id)
    {
        $query = DB::select('hide_author')
            ->from('forms')
            ->where('id', '=', $form_id);

        $results = $query->execute($this->db)->as_array();

        return count($results) > 0 ? $results[0]['hide_author'] : false;
    }

    /**
     * Get `everyone_can_create` and list of roles that have access to post to the form
     * @param  $form_id
     * @return Array
     */
    public function getRolesThatCanCreatePosts($form_id)
    {
        $query = DB::select('forms.everyone_can_create', 'roles.name')
            ->distinct(TRUE)
            ->from('forms')
            ->join('form_roles', 'LEFT')
            ->on('forms.id', '=', 'form_roles.form_id')
            ->join('roles', 'LEFT')
            ->on('roles.id', '=', 'form_roles.role_id')
            ->where('forms.id', '=', $form_id);

        $results =  $query->execute($this->db)->as_array();

        $everyone_can_create = (count($results) == 0 ? 1 : $results[0]['everyone_can_create']);

        $roles = [];

        foreach($results as $role) {
            if (!is_null($role['name'])) {
                $roles[] = $role['name'];
            }
        }

        return [
            'everyone_can_create' => $everyone_can_create,
            'roles' => $roles,
            ];

    }

}
