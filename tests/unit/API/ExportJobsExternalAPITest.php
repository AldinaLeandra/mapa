<?php

namespace Tests\Unit\API;

use Laravel\Lumen\Testing\DatabaseTransactions;
use Tests\TestCase;
use Faker;

/**
 * @group api
 * @group integration
 */
class ExportJobsExternalAPITest extends TestCase
{

	protected $jobId;
	protected $userId;

	public function setUp()
	{
		parent::setUp();

		$this->withoutMiddleware();
		$faker = Faker\Factory::create();

		$this->userId = service('repository.user')->create(new \Ushahidi\Core\Entity\User([
			'email' => $faker->email,
			'password' => $faker->password(10),
			'realname' => $faker->name,
		]));

		$exportJobs = service('repository.export_job');
		$this->jobId = $exportJobs->create(new \Ushahidi\Core\Entity\ExportJob([
			'user_id' => $this->userId,
			'entity_type' => 'post'
		]));
	}

	public function tearDown()
	{
		parent::tearDown();

		service('repository.user')->delete(new \Ushahidi\Core\Entity\User(['id' => $this->userId]));
		service('repository.export_job')->delete(new \Ushahidi\Core\Entity\ExportJob(['id' => $this->jobId]));
	}

	/**
	 * Get count
	 */
	public function testCount()
	{
		$this->get('/api/v3/exports/external/count/' . $this->jobId);

		$this->seeStatusCode('200')
			->seeJsonStructure([[
				"total",
				"label",
			]]);
	}

	/**
	 * Get count
	 */
	public function testCli()
	{
		$this->get('/api/v3/exports/external/cli/' . $this->jobId);

		$this->seeStatusCode('200')
			->seeJsonStructure([
				"results" => [[
					'file',
				]]
			]);
	}

	/**
	 * Get all jobs
	 */
	public function testGetJobs()
	{
		$this->get('/api/v3/exports/external/jobs');

		$this->seeStatusCode('200')
			->seeJsonStructure([
				"count",
				"results" => [
					'*' => [
						'id',
						'user',
						'entity_type',
						'fields',
						'filters',
						'status',
						'header_row',
						'created'
					]
				],
			]);
	}


	/**
	 * Getting a job
	 */
	public function testGetJob()
	{
		$this->get('/api/v3/exports/external/jobs/' . $this->jobId);

		$this->seeStatusCode('200')
			->seeJsonStructure([
				'id',
				'user',
				'entity_type',
				'fields',
				'filters',
				'status',
				'header_row',
				'created'
			]);
	}


	/**
	 * Update a job
	 */
	public function testUpdateJob()
	{
		$this->json('PUT', '/api/v3/exports/external/jobs/' . $this->jobId, [
			'filters' => ['status' => 'draft']
		]);

		$this->seeStatusCode('200')
			->seeJsonStructure([
				'id',
				'user',
				'entity_type',
				'fields',
				'filters',
				'status',
				'header_row',
				'created'
			])
			->seeJson([
				'filters' => [
					'status' => 'draft',
				],
			]);
	}

	protected function makeSig($sharedSecret, $url, $payload)
	{
		$data = $url . $payload;

		return base64_encode(hash_hmac("sha256", $data, $sharedSecret, true));
	}

	/**
	 * Update a job
	 */
	public function testUpdateJobWithSignature()
	{
		// Re-enable middleware
		$this->app->instance('middleware.disable', false);
		// Set the shared secret
		$originalSecret = getenv('PLATFORM_SHARED_SECRET');
		putenv('PLATFORM_SHARED_SECRET=asharedsecret');

		// Make an API key
		$apiKeys = service('repository.apikey');
		$apiKeyId = $apiKeys->create(new \Ushahidi\Core\Entity\ApiKey([]));
		$apiKey = $apiKeys->get($apiKeyId);

		// Make a signature
		$sig = $this->makeSig(
			'asharedsecret',
			$this->prepareUrlForRequest(
				'/api/v3/exports/external/jobs/' . $this->jobId . '?api_key=' . $apiKey->api_key
			),
			json_encode(['filters' => ['status' => 'draft']])
		);

		$this->json(
			'PUT',
			'/api/v3/exports/external/jobs/' . $this->jobId . '?api_key=' . $apiKey->api_key,
			[
				'filters' => ['status' => 'draft']
			],
			[
				'X-Ushahidi-Signature' => $sig
			]
		);

		$this->seeStatusCode('200')
			->seeJsonStructure([
				'id',
				'user',
				'entity_type',
				'fields',
				'filters',
				'status',
				'header_row',
				'created'
			])
			->seeJson([
				'filters' => [
					'status' => 'draft',
				],
			]);

		// Clean up
		$apiKeys->delete($apiKey);
		putenv('PLATFORM_SHARED_SECRET=' . $originalSecret);
	}
}
