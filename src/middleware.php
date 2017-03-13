<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

use Slim\Http\Request;
use Slim\Http\Response;

$_APIKEY = require __DIR__ . DIRECTORY_SEPARATOR . 'apikey.php';

/**
 * Checks the API key header.
 * @param Request $request
 * @param Response $response
 * @param callable $next
 * @return Response
 */
$check_api_key_first = function(Request $request, Response $response, callable $next): Response {
    global $_APIKEY;
    $apikey = $request->getHeader('apikey')[0];

    if (empty($apikey) || !isset($apikey)) {
        return $response->withJson(json_message_array("Empty API key. Unauthorized.", false))->withStatus(401);
    }

    if ($apikey !== $_APIKEY) {
        return $response->withJson(json_message_array("Invalid API key. Unauthorized.", false))->withStatus(401);
    }

    $next_response = $next($request, $response);

    return $next_response;
};

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

    if (empty($username)) {
        return $response->withJson(json_message_array("The username cannot be empty!", false));
    }

    if (empty($password)) {
        return $response->withJson(json_message_array("The password cannot be empty!", false));
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

/**
 * Will return a specific error message for a request lacking the Authorization header.
 * @param Request $request
 * @param Response $response
 * @param callable $next
 * @return Response
 */
$http_basic_auth = function(Request $request, Response $response, callable $next) {
    $http_basic_auth_header_is_present = $request->getHeader('HTTP_AUTHORIZATION');

    if (empty($http_basic_auth_header_is_present) || !isset($http_basic_auth_header_is_present)) {
        return $response->withJson(json_message_array("No basic HTTP Authorization header provided.", false))->withStatus(401);
    }

    $response = $next($request, $response);
    return $response;
};

$app->add($check_api_key_first);
$app->add($specific_CORS);
$app->add($http_basic_auth);
$app->add($empty_auth_fields);
