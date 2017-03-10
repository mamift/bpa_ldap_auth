<?php
// Routes
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * The login route. Will accept only basic HTTP authentication. Send a request to this route to authenticate a BPA
 * employee's credential.
 */
$app->get('/login', function(Request $request, Response $response) {

    $username = $request->getHeader('PHP_AUTH_USER')[0];
    $password = $request->getHeader('PHP_AUTH_PW')[0];

    $is_authenticated = bind_to_dirty_bpanz_domain($username, $password);

    $message = ($is_authenticated === false ? "Unsuccessful authentication" : "Successful authentication");
    $data = json_message_array($message, $is_authenticated);

    $new_response = $response->withJson($data);
    if ($is_authenticated === false) {
        $new_response = $new_response->withStatus(401);
    }
    return $new_response;
});

/**
 * This is the index route, will always return 401
 */
$app->get('/', function (Request $request, Response $response, array $args) {
    return $response->withJson(json_message_array("Unauthorized", false))->withStatus(401);
});
