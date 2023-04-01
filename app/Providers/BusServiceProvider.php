<?php

namespace App\Providers;

use App\Bus\Command\CommandBus;
use App\Bus\Command\Example\ExampleCommand;
use App\Bus\Command\Example\ExampleCommandHandler;
use App\Bus\Query\Example\ExampleQuery;
use App\Bus\Query\Example\ExampleQueryHandler;
use Ushahidi\Modules\V5\Actions\Tos\Commands\CreateTosCommand;
use Ushahidi\Modules\V5\Actions\Tos\Handlers\CreateTosCommandHandler;
use Ushahidi\Modules\V5\Actions\Tos\Queries\FetchTosQuery;
use Ushahidi\Modules\V5\Actions\Tos\Handlers\FetchTosQueryHandler;
use Ushahidi\Modules\V5\Actions\Tos\Queries\FetchTosByIdQuery;
use Ushahidi\Modules\V5\Actions\Tos\Handlers\FetchTosByIdQueryHandler;

use App\Bus\Query\QueryBus;
use Illuminate\Support\ServiceProvider;
use Ushahidi\Modules\V5\Actions\CountryCode\Queries\FetchCountryCodeByIdQuery;
use Ushahidi\Modules\V5\Actions\CountryCode\Queries\FetchCountryCodeQuery;
use Ushahidi\Modules\V5\Actions\CountryCode\Handlers\FetchCountryCodeByIdQueryHandler;
use Ushahidi\Modules\V5\Actions\CountryCode\Handlers\FetchCountryCodeQueryHandler;
use Ushahidi\Modules\V5\Actions\User;
use Ushahidi\Modules\V5\Actions\Permissions\Queries\FetchPermissionsQuery;
use Ushahidi\Modules\V5\Actions\Permissions\Handlers\FetchPermissionsQueryHandler;
use Ushahidi\Modules\V5\Actions\Permissions\Queries\FetchPermissionsByIdQuery;
use Ushahidi\Modules\V5\Actions\Permissions\Handlers\FetchPermissionsByIdQueryHandler;
use Ushahidi\Modules\V5\Actions\Role;
use Ushahidi\Modules\V5\Actions\Survey;
use Ushahidi\Modules\V5\Actions\SavedSearch;
use Ushahidi\Modules\V5\Actions\Collection;

class BusServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->registerCommands();
        $this->registerQueries();
    }

    private function registerCommands(): void
    {
        $this->app->singleton(CommandBus::class, function ($app) {
            $commandBus = new CommandBus($app);

            $commandBus->register(ExampleCommand::class, ExampleCommandHandler::class);

            $commandBus->register(
                Role\Commands\CreateRoleCommand::class,
                Role\Handlers\CreateRoleCommandHandler::class
            );
            $commandBus->register(
                Role\Commands\UpdateRoleCommand::class,
                Role\Handlers\UpdateRoleCommandHandler::class
            );
            $commandBus->register(
                Role\Commands\DeleteRoleCommand::class,
                Role\Handlers\DeleteRoleCommandHandler::class
            );

            $commandBus->register(
                Role\Commands\CreateRolePermissionCommand::class,
                Role\Handlers\CreateRolePermissionCommandHandler::class
            );
            $commandBus->register(
                Role\Commands\DeleteRolePermissionByRoleCommand::class,
                Role\Handlers\DeleteRolePermissionByRoleCommandHandler::class
            );

            $commandBus->register(
                User\Commands\CreateUserCommand::class,
                User\Handlers\CreateUserCommandHandler::class
            );
            $commandBus->register(
                User\Commands\UpdateUserCommand::class,
                User\Handlers\UpdateUserCommandHandler::class
            );
            $commandBus->register(
                User\Commands\DeleteUserCommand::class,
                User\Handlers\DeleteUserCommandHandler::class
            );

            $commandBus->register(
                User\Commands\CreateUserSettingCommand::class,
                User\Handlers\CreateUserSettingCommandHandler::class
            );
            $commandBus->register(
                User\Commands\UpdateUserSettingCommand::class,
                User\Handlers\UpdateUserSettingCommandHandler::class
            );
            $commandBus->register(
                User\Commands\DeleteUserSettingCommand::class,
                User\Handlers\DeleteUserSettingCommandHandler::class
            );


            $commandBus->register(
                Survey\Commands\CreateTaskCommand::class,
                Survey\Handlers\CreateTaskCommandHandler::class
            );
            $commandBus->register(
                Survey\Commands\DeleteTasksCommand::class,
                Survey\Handlers\DeleteTasksCommandHandler::class
            );
            $commandBus->register(
                Survey\Commands\UpdateTaskCommand::class,
                Survey\Handlers\UpdateTaskCommandHandler::class
            );


            $commandBus->register(
                Survey\Commands\CreateSurveyCommand::class,
                Survey\Handlers\CreateSurveyCommandHandler::class
            );
            $commandBus->register(
                Survey\Commands\UpdateSurveyCommand::class,
                Survey\Handlers\UpdateSurveyCommandHandler::class
            );
            $commandBus->register(
                Survey\Commands\DeleteSurveyCommand::class,
                Survey\Handlers\DeleteSurveyCommandHandler::class
            );

            $commandBus->register(
                Survey\Commands\CreateSurveyRoleCommand::class,
                Survey\Handlers\CreateSurveyRoleCommandHandler::class
            );

            $commandBus->register(
                Survey\Commands\DeleteSurveyRolesBySurveyIDCommand::class,
                Survey\Handlers\DeleteSurveyRolesBySurveyIDCommandHandler::class
            );



            $commandBus->register(
                SavedSearch\Commands\CreateSavedSearchCommand::class,
                SavedSearch\Handlers\CreateSavedSearchCommandHandler::class
            );
            $commandBus->register(
                SavedSearch\Commands\UpdateSavedSearchCommand::class,
                SavedSearch\Handlers\UpdateSavedSearchCommandHandler::class
            );
            $commandBus->register(
                SavedSearch\Commands\DeleteSavedSearchCommand::class,
                SavedSearch\Handlers\DeleteSavedSearchCommandHandler::class
            );


            $commandBus->register(
                Collection\Commands\CreateCollectionCommand::class,
                Collection\Handlers\CreateCollectionCommandHandler::class
            );
            $commandBus->register(
                Collection\Commands\UpdateCollectionCommand::class,
                Collection\Handlers\UpdateCollectionCommandHandler::class
            );
            $commandBus->register(
                Collection\Commands\DeleteCollectionCommand::class,
                Collection\Handlers\DeleteCollectionCommandHandler::class
            );


            $commandBus->register(CreateTosCommand::class, CreateTosCommandHandler::class);

            return $commandBus;
        });
    }

    private function registerQueries(): void
    {
        $this->app->singleton(QueryBus::class, function ($app) {
            $queryBus = new QueryBus($app);

            $queryBus->register(ExampleQuery::class, ExampleQueryHandler::class);
            $queryBus->register(FetchCountryCodeQuery::class, FetchCountryCodeQueryHandler::class);
            $queryBus->register(FetchCountryCodeByIdQuery::class, FetchCountryCodeByIdQueryHandler::class);
            $queryBus->register(FetchPermissionsQuery::class, FetchPermissionsQueryHandler::class);
            $queryBus->register(FetchPermissionsByIdQuery::class, FetchPermissionsByIdQueryHandler::class);

            $queryBus->register(
                Role\Queries\FetchRoleQuery::class,
                Role\Handlers\FetchRoleQueryHandler::class
            );
            $queryBus->register(
                Role\Queries\FetchRoleByIdQuery::class,
                Role\Handlers\FetchRoleByIdQueryHandler::class
            );

            $queryBus->register(FetchTosQuery::class, FetchTosQueryHandler::class);
            $queryBus->register(FetchTosByIdQuery::class, FetchTosByIdQueryHandler::class);

            $queryBus->register(
                User\Queries\FetchUserQuery::class,
                User\Handlers\FetchUserQueryHandler::class
            );
            $queryBus->register(
                User\Queries\FetchUserByIdQuery::class,
                User\Handlers\FetchUserByIdQueryHandler::class
            );

            $queryBus->register(
                User\Queries\FetchUserSettingQuery::class,
                User\Handlers\FetchUserSettingQueryHandler::class
            );
            $queryBus->register(
                User\Queries\FetchUserSettingByIdQuery::class,
                User\Handlers\FetchUserSettingByIdQueryHandler::class
            );

            $queryBus->register(
                Survey\Queries\FetchRolesCanCreateSurveyPostsQuery::class,
                Survey\Handlers\FetchRolesCanCreateSurveyPostsQueryHandler::class
            );

            $queryBus->register(
                Survey\Queries\FetchTasksBySurveyIdQuery::class,
                Survey\Handlers\FetchTasksBySurveyIdQueryHandler::class
            );

            $queryBus->register(
                Survey\Queries\FetchSurveyQuery::class,
                Survey\Handlers\FetchSurveyQueryHandler::class
            );
            $queryBus->register(
                Survey\Queries\FetchSurveyByIdQuery::class,
                Survey\Handlers\FetchSurveyByIdQueryHandler::class
            );


            $queryBus->register(
                SavedSearch\Queries\FetchSavedSearchQuery::class,
                SavedSearch\Handlers\FetchSavedSearchQueryHandler::class
            );
            $queryBus->register(
                SavedSearch\Queries\FetchSavedSearchByIdQuery::class,
                SavedSearch\Handlers\FetchSavedSearchByIdQueryHandler::class
            );


            $queryBus->register(
                Collection\Queries\FetchCollectionQuery::class,
                Collection\Handlers\FetchCollectionQueryHandler::class
            );
            $queryBus->register(
                Collection\Queries\FetchCollectionByIdQuery::class,
                Collection\Handlers\FetchCollectionByIdQueryHandler::class
            );

            return $queryBus;
        });
    }
}
