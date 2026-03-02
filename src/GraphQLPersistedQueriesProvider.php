<?php
namespace BrainzStudios\GraphQLPersistedQueries;

use BrainzStudios\GraphQLPersistedQueries\Middleware\PersistedFragmentsMiddleware;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
class GraphQLPersistedQueriesProvider extends ServiceProvider{


    public function boot(Router $router) {
        $router->aliasMiddleware('persisted_fragments', PersistedFragmentsMiddleware::class);
    }
}