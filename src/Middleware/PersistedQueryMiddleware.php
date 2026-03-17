<?php
namespace BrainzStudios\GraphQLPersistedQueries\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PersistedQueryMiddleware {
    public function handle(Request $request, Closure $next): Response
    {

        if(($request->hasHeader('Allow-Bypass-PQL') && $request->header('Allow-Bypass-PQL') == config('app.gql_bypass_password')) || app()->environment('local') ) return $next($request);

        $operationName = $request->input('operationName');
        if (!$operationName) {
            return response()->json(['errors' => [['message' => 'Missing operationName']]], 403);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9]/', '', $operationName);

        // Try mutations first, then queries
        $path = resource_path("graphql/mutations/{$safeName}.graphql");
        $type = 'mutations';

        if (!file_exists($path)) {
            $path = resource_path("graphql/queries/{$safeName}.graphql");
            $type = 'queries';
        }

        if (!file_exists($path)) {
            return response()->json(['errors' => [['message' => 'Query not whitelisted']]], 403);
        }
        $queryBody = file_get_contents($path);
        $fragmentsPath = resource_path("graphql/queries/fragments");

        if (File::isDirectory($fragmentsPath)) {
            $fragmentFiles = File::allFiles($fragmentsPath);
            $queryBody = file_get_contents($path);
            $addedFragments = [];
            $foundNew = true;

            while ($foundNew) {
                $foundNew = false;
                foreach ($fragmentFiles as $file) {
                    $fName = $file->getBasename('.graphql');
                    if (isset($addedFragments[$fName])) continue;
                    $fragmentContent = file_get_contents($file->getPathname());
                    $pattern = '/\.\.\.\s*' . preg_quote($fName, '/') . '\b/';
                    if (preg_match($pattern, $queryBody)) {
                        $queryBody .= "\n" . $fragmentContent;
                        $addedFragments[$fName] = true;
                        $foundNew = true;
                        Log::info("Fragment $fName PRIPOJEN (i pro vnořené potřeby).");
                    }
                }
            }
        }

        // Also check for mutations fragments if this is a mutation
        if ($type === 'mutations') {
            $mutationsFragmentsPath = resource_path("graphql/mutations/fragments");
            if (File::isDirectory($mutationsFragmentsPath)) {
                $fragmentFiles = File::allFiles($mutationsFragmentsPath);
                $addedFragments = [];
                $foundNew = true;

                while ($foundNew) {
                    $foundNew = false;
                    foreach ($fragmentFiles as $file) {
                        $fName = $file->getBasename('.graphql');
                        if (isset($addedFragments[$fName])) continue;
                        $fragmentContent = file_get_contents($file->getPathname());
                        $pattern = '/\.\.\.\s*' . preg_quote($fName, '/') . '\b/';
                        if (preg_match($pattern, $queryBody)) {
                            $queryBody .= "\n" . $fragmentContent;
                            $addedFragments[$fName] = true;
                            $foundNew = true;
                            Log::info("Mutation fragment $fName PRIPOJEN.");
                        }
                    }
                }
            }
        }

        $request->merge([
            'query' => $queryBody
        ]);

        return $next($request);
    }

}