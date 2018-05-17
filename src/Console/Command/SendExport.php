<?php

/**
 * Ushahidi Export Send Console Command
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Console
 * @copyright  2018 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */

namespace Ushahidi\Console\Command;

use Illuminate\Console\Command;
use Ushahidi\Core\Entity\ExportJobRepository;

class SendExport extends Command
{
    private $db;
    private $exportJobRepository;
    private $client;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'export:send';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'export:send {export_broker_uri} {deployment_domain} {deployment_subdomain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Export Jobs to Export Broker';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->exportJobRepository = service('repository.export_job');

        $this->client = new \GuzzleHttp\Client();

        // Get Queued webhook requests
        $pending_jobs = $this->exportJobRepository->getPendingJobs();

        // Start transaction
        $this->db->begin();

        foreach ($pending_jobs as $pending_job) {
            $this->generateRequest($pending_job);

            $count++;
        }

        // Finally commit changes
        $this->db->commit();

        $this->info("{$count} webhook requests sent");
    }

    private function generateRequest($pending_job)
    {
        $export_broker_uri = $this->option('export_broker_uri');

        $data['deployment_domain'] = $this->option('deployment_domain');
        $data['deployment_subdomain'] = $this->option('deployment_subdomain');
        $data['job_id'] = $pending_job->id;

        $json = json_encode($data);
        $promise = $this->client->request('POST', $export_broker_uri, [
            'headers' => [
                'Accept'               => 'application/json'
            ],
            'json' => $data
        ]);
        $pending_job->status = 'queued';

        $this->exportJobRepository->update($pending_job);
    }
}
