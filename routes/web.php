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
    "namespace" => "activity",
    "prefix"    => "activity"
], function() use ($router) {
    $router->get("","ActivityController@index");

    // create new project
    $router->post("create","ActivityController@create");
    $router->post("create/{id}","ActivityController@create");

    $router->put("project/{project_activity_id}/{project_id}", "ActivityController@project");

    // delete project
    $router->delete("delete/{mode}/{id}","ActivityController@delete");
});
