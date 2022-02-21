<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group([], function() use ($router) {
    $router->get("/", function() use ($router) {
        $routeCollection = $router->getRoutes();

        foreach ( $routeCollection as $row ) {
            print_r("$row[method] <a href='$row[uri]'>$row[uri]</a><br>");
        }
    });

    $router->get("/version", function() use ($router) {
        return $router->app->version();
    });

    $router->get("/phpinfo", function() use ($router) {
        return phpinfo();
    });
});

# project route
$router->group([
    "namespace" => "project",
    "prefix"    => "project"
], function() use ($router) {
    $router->get("","ProjectController@index");
    $router->post("create","ProjectController@create");
    $router->post("closing","ProjectController@setClosing");
    $router->put("create/{id}","ProjectController@create");
    $router->delete("delete","ProjectController@delete");

    $router->get("summary","ProjectController@getSummary");

    $router->group([
        "prefix"    => "progress",
    ], function() use ($router) {
        $router->get("{id}","ProjectController@getProgress");
        $router->get("grantt/{id}","ProjectController@getGranttProgress");
        $router->post("create/{project}","ProjectController@setProgress");
        $router->delete("delete","ProjectController@deleteProgress");
    });
});
