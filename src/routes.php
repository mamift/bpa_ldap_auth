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

    $dirty_bpanz = ldap_connect('ldap://bpa-d-server01.bpanz.local');

    $is_authenticated = bind_to_dirty_bpanz_domain($dirty_bpanz, $username, $password);

    $sr = get_first_and_last_names($dirty_bpanz, $username);

    $message = ($is_authenticated === false ? "Unsuccessful authentication" : "Successful authentication");
    $return_data = [];

    if ($is_authenticated === true) {
        $return_data['time_authorized'] = (new DateTime('now', new DateTimeZone("Australia/Brisbane")))->format('Y-m-d H:i:s');
        $return_data['username'] = 'BPANZ\mmiftah';
    }

    $return_message = json_message_array($message, $is_authenticated, $return_data);

    $new_response = $response->withJson($return_message);

    if ($is_authenticated === false) {
        return $new_response->withStatus(401);
    }

    return $new_response;
});

/**
 * This is the index route, will always return 401
 */
$app->get('/', function (Request $request, Response $response, array $args) {
    return $response->withJson(json_message_array("Unauthorized", false))->withStatus(401);
});
