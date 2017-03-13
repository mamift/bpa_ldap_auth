<?php
// Routes
use Slim\Http\Request;
use Slim\Http\Response;

use \Toyota\Component\Ldap\Exception\ConnectionException;
use \Toyota\Component\Ldap\Exception\BindException;

/**
 * The login route. Will accept only basic HTTP authentication. Send a request to this route to authenticate a BPA
 * employee's credential.
 */
$app->get('/login', function(Request $request, Response $response) {

    $username = $request->getHeader('PHP_AUTH_USER')[0];
    $password = $request->getHeader('PHP_AUTH_PW')[0];
    $is_authenticated = null;

    try {
        $dirty_bpanz_connection = obj_bind_to_dirty_bpanz_domain($username, $password);
        $is_authenticated = true;
    } catch (ConnectionException $connection_exception) {
        $is_authenticated = false;
        goto didnt_authenticate;
    } catch (BindException $bind_exception) {
        $is_authenticated = false;
        goto didnt_authenticate;
    }

    $logon_name = get_indexed_element_of_string_converted_to_array($username, 1);
    $user_attributes = search_for_user_in_ad_and_get_attributes($dirty_bpanz_connection, $logon_name);

    didnt_authenticate:
    $message = ($is_authenticated === false ? "Unsuccessful authentication" : "Successful authentication");
    $return_data = [];

    if ($is_authenticated === true) {
        $default_timezone = new DateTimeZone("Australia/Brisbane");
        $usr_last_logon = convert_windows_timestamp_to_unix($user_attributes['lastLogon']->getValues()[0]);
        $datetime_format = 'Y-m-d H:i:s';

        $return_data['time_authorized'] = (new DateTime('now', $default_timezone))->format($datetime_format);
//        $return_data['attributes'] = $user_attributes;
        $return_data['full_name'] = $user_attributes['name']->getValues()[0];
//        $return_data['last_logon'] = $usr_last_logon;
//        $return_data['last_logon_date'] = date($datetime_format, $usr_last_logon);
        $return_data['last_logon_date'] = (new DateTime())->setTimestamp($usr_last_logon)->setTimezone($default_timezone)->format($datetime_format);
//        $return_data['last_logoff'] = $user_attributes['lastLogoff']->getValues()[0];
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
