<?php
namespace BrainzStudios\GraphQLPersistedQueries;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
class GraphQLPersistedQueriesProvider {


    public function boot(Router $router) {
        $router->aliasMiddleware('persisted_fragments', \BrainzStudios\GraphQLPersistedQueries\Middleware\PersistedFragmentsMiddleware::class);

    }
}