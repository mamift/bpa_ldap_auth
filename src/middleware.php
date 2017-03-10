<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Ensures that the username and password of a basic HTTP authentication header are not empty.
 * @param Request $request
 * @param Response $response
 * @param callable $next
 * @return mixed
 */
$empty_auth_fields = function(Request $request, Response $response, callable $next): Response {
    $username = $request->getHeader('PHP_AUTH_USER')[0];
    $password = $request->getHeader('PHP_AUTH_PW')[0];

    if (empty($password)) {
        return $response->withJson(json_message_array("The password cannot be empty!", false));
    }

    if (empty($username)) {
        return $response->withJson(json_message_array("The username cannot be empty!", false));
    }

    $next_response = $next($request, $response);

    return $next_response;
};

/**
 * Allow CORS from a specific domain.
 * @param Request $request
 * @param Response $response
 * @param callable $next
 * @return mixed
 */
$specific_CORS = function(Request $request, Response $response, callable $next) {
    $route = $request->getAttribute("route");

    $methods = [];

    if (!empty($route)) {
        $pattern = $route->getPattern();

        foreach ($this->router->getRoutes() as $route) {
            if ($pattern === $route->getPattern()) {
                $methods = array_merge_recursive($methods, $route->getMethods());
            }
        }
        // methods holds all of the HTTP Verbs that a particular route handles.
    } else {
        $methods[] = $request->getMethod();
    }

    $response = $next($request, $response);
    $response = $response->withHeader("Access-Control-Allow-Methods", implode(",", $methods))
        ->withHeader("Access-Control-Allow-Origin", "*"); // TODO: need to change to specific domain for the dashboard!!
    return $response;
};

$app->add($empty_auth_fields);
$app->add($specific_CORS);