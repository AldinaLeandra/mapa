<?php

namespace Ushahidi\Modules\V5\Actions\Survey\Handlers;

use Ushahidi\Modules\V5\Actions\V5QueryHandler;
use App\Bus\Query\Query;
use Ushahidi\Modules\V5\Actions\Survey\Queries\FetchSurveyByIdQuery;
use Ushahidi\Modules\V5\Repository\Survey\SurveyRepository;
use Ushahidi\Modules\V5\Models\Survey;
use App\Bus\Query\QueryBus;

class FetchSurveyStatsQueryHandler extends V5QueryHandler
{

    private $survey_repository;
    private $queryBus;

    public function __construct(QueryBus $queryBus, SurveyRepository $survey_repository)
    {
        $this->survey_repository = $survey_repository;
        $this->queryBus = $queryBus;
    }

    protected function isSupported(Query $query)
    {
        assert(
            get_class($query) === FetchSurveyByIdQuery::class,
            'Provided query is not supported'
        );
    }


    /**
     * @param FetchSurveyByIdQuery $query
     * @return Survey
     */
    public function __invoke($query) //: array
    {
        $only = $this->getSelectFields(
            $query->getFormat(),
            $query->getOnlyFields(),
            Survey::$approved_fields_for_select,
            Survey::$required_fields_for_select
        );
        $this->isSupported($query);
        $survey = $this->survey_repository->findById($query->getId(), $only);

        $this->addHydrateRelationships(
            $survey,
            $only,
            $this->getHydrateRelationshpis(Survey::$relationships, $query->getHydrate())
        );
        $survey->offsetUnset('base_language');
        return $survey;
    }
}
