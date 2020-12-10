<?php

namespace Ushahidi\App\Http\Controllers\API;

use Ushahidi\App\Http\Controllers\RESTController;
use Ushahidi\App\Facades\Features;
use Illuminate\Http\Request;

/**
 * Ushahidi API Register Controller
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Application\Controllers
 * @copyright  2015 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */
class RegisterController extends RESTController
{

    protected function getResource()
    {
        return 'users';
    }

    /**
     * Register a user
     *
     * POST /api/v3/register
     *
     * @return void
     */
    public function store(Request $request)
    {
        // If the disable registration feature is enabled and site registration is disabled in config
        if (Features::isEnabled('disable_registration')
            && app('multisite')->getSite()->getSiteConfig('disable_registration', false)) {
            abort(403, 'Registration Disabled');
        }

        $this->usecase = $this->usecaseFactory
            ->get($this->getResource(), 'register')
            ->setPayload($request->json()->all());

        return $this->prepResponse($this->executeUsecase($request), $request);
    }
}
