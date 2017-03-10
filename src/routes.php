<?php
// Routes
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Generates an array for use in JSON responses. Should really be used for status messages.
 * @param string $message
 * @param bool $status
 * @param array|null $data
 * @return array
 */
function json_message_array(string $message, bool $status = true, ?array $data = []): array {
    $return_data = [
        'success' => $status,
        'message' => $message,
        'data' => $data
    ];

    if (empty($data) || $data == null) {
        unset($return_data['data']);
    }

    return $return_data;
}

/**
 * Binds to the dirty BPANZ server with a username and password.
 * @param string $username [username must include the appropriate NetBIOS prefix, cannot be empty or null]
 * @param string $password [the password cannot be empty or null]
 * @return bool [true if binding succeeded]
 * @throws Exception
 */
function bind_to_dirty_bpanz_domain(string $username, string $password): bool {
    if (empty($username) || empty($password)) {
        // this should never occur as there's middleware that will check for empty usernames or passwords in the
        // Authorization header
        throw new Exception("Empty username or password!");
    }
    $dirty_bpanz = ldap_connect('ldap://bpa-d-server01.bpanz.local');
    return ldap_bind($dirty_bpanz, $username, $password);
}

/**
 * The login route. Will accept only basic HTTP authentication
 */
$app->get('/login', function(Request $request, Response $response) {

    $username = $request->getHeader('PHP_AUTH_USER')[0];
    $password = $request->getHeader('PHP_AUTH_PW')[0];

    $is_authenticated = bind_to_dirty_bpanz_domain($username, $password);

    $message = ($is_authenticated === false ? "Unsuccessful authentication" : "Successful authentication");
    $data = json_message_array($message, $is_authenticated);

    $new_response = $response->withJson($data);
    return $new_response;
});

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

