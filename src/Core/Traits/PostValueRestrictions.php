<?php

/**
 * Ushahidi Post Value Restrictions trait
 *
 *
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Application
 * @copyright  2014 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */

namespace Ushahidi\Core\Traits;

use Ushahidi\Core\Entity\User;
use Ushahidi\Core\Entity\Post;
use Ushahidi\Core\Entity\FormRepository;

trait PostValueRestrictions
{

	protected $form_repository;

	public function restrictAuthor(Post $post, FormRepository $form_repo)
	{
		if ($post->form_id) {
			return $form_repo->getHideAuthor($post->form_id);
		}

		return false;
	}

	protected function isUserOfRole(Post $post, $user)
	{
		if ($post->published_to) {
			return in_array($user->role, $post->published_to);
		}

		// If no visibility info, assume public
		return true;
	}

	protected function isPostPublishedToUser(Post $post, $user)
	{
		// Anon users can not see restricted fields
		if (!$user->getId()) {
			return false;
		}

		if ($post->status === 'published' && $this->isUserOfRole($post, $user)) {
			return true;
		}
		return false;
	}

	/**
	 * Test whether the post instance requires value restriction
	 * @param  Post $post
	 * @return Boolean
	 */
	public function restrictPostValues(Post $post, $user, FormRepository $form_repo)
	{
		$this->form_repository = $form_repo;
		if (!$this->isFormRestricted($post, $user) && $this->isPostPublishedToUser($post, $user))
		{
			return false;
		}
		return true;
	}

	/* FormRole */
	protected function isFormRestricted(Post $post, $user)
	{
		// If the $entity->form_id exists and the $form->everyone_can_create is False
		// we check to see if the Form & Role Join exists in the `FormRoleRepository`

		if ($post->form_id) {
			$roles = $this->form_repository->getRolesThatCanCreatePosts($post->form_id);

			if ($roles['everyone_can_create'] > 0) {
				return false;
			}

			if (is_array($roles['roles']) && in_array($user->role, $roles['roles'])) {
				return false;
			}
		}

		return true;
	}
}
