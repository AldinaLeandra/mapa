<?php

/**
 * Ushahidi Platform Create Form Attribute Use Case
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Platform
 * @copyright  2014 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */

namespace Ushahidi\Core\Usecase\Form;

use Ushahidi\Core\Exception\ValidatorException;
use Ushahidi\Core\Usecase\Contact\CreateContact;
use Ushahidi\Core\Usecase\Concerns\IdentifyRecords;
use Ushahidi\Core\Usecase\Concerns\VerifyEntityLoaded;

class CreateFormContact extends CreateContact
{
	use VerifyFormLoaded;

	// For form check:
	use VerifyEntityLoaded;
	use IdentifyRecords;
	protected $phone_validator;
	protected function getEntity()
	{
		$entity = parent::getEntity();

		// Add user id if this is not provided
		if (empty($entity->user_id) && $this->auth->getUserId()) {
			$entity->setState(['user_id' => $this->auth->getUserId()]);
		}

		return $entity;
	}

	// Usecase
	public function interact()
	{

		// First verify that the form even exists
		$this->verifyFormExists();
		$this->verifyTargetedSurvey();
		$this->verifyFormDoesNoExistInContactPostState();
		// Fetch a default entity and ...
		$entity = $this->getEntity();
		// ... verify the current user has have permissions
		$this->verifyCreateAuth($entity);

		// Get each item in the collection
		$entities = [];
		$invalid = [];
		$countryCode = $this->getPayload('country_code');
		$contacts = explode(',', $this->getPayload('contacts'));
		foreach ($contacts as $contact) {
			$entities[] = $this->getContactEntity($contact, $countryCode, $invalid);
		}
		return $this->getContactCollection($entities, $invalid);
	}

	private function getContactEntity($contactNumber, $countryCode, &$invalid)
    {
		// .. generate an entity for the item
		$entity = $this->repo->getEntity(array('contact' => $contactNumber));
		/**
		 * we only use this field for validation
		 * we check that country code + phone number are valid.
		 * country_code is unset before saving the entity
		 */
		$entity->country_code = $countryCode;
		$countryCodeNumber = $this->phone_validator->parse($contactNumber, $countryCode)->getCountryCode();
		$contactNumber = $countryCodeNumber . $contactNumber;
		$entity->setState(
			[
				'created' => time(),
				'can_notify' => true,
				'type' => 'phone',
				'contact' => $contactNumber
			]
		);
		// ... and save it for later
		$entities[] = $entity;

		if (!$this->validator->check($entity->asArray())) {
			$invalid[$entity->contact] = $this->validator->errors();
		}
		return $entity;
	}

	private function getContactCollection($entities, $invalid)
    {
		// FIXME: move to collection error trait?
		if (!empty($invalid)) {
			$invalidList = implode(',', array_keys($invalid));
			throw new ValidatorException(sprintf(
				'The following contacts have validation errors:',
				$invalidList
			), $invalid);
		} else {
			// ... persist the new collection
			$this->repo->updateCollection($entities, intval($this->getIdentifier('form_id')));
			// ... and finally format it for output
			return $this->formatter->__invoke(intval($this->getIdentifier('form_id')), $entities);
		}
	}

	public function setPhoneValidator($validator)
    {
		$this->phone_validator = $validator;
	}
}
