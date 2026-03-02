<?php
namespace BrainzStudios\GraphQLPersistedQueries;

use BrainzStudios\GraphQLPersistedQueries\Middleware\PersistedQueryMiddleware;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
class GraphQLPersistedQueriesProvider extends ServiceProvider{


    public function boot(Router $router) {
        $router->aliasMiddleware('persisted_queries', PersistedQueryMiddleware::class);
    }
}