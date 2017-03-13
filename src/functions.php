<?php
/**
 * Created by PhpStorm.
 * User: mmiftah
 * Date: 10/03/2017
 * Time: 3:14 PM
 */

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
 * Explodes a string into an array and then returns the indexed element (specified by the $index paramter)
 * @param string $str
 * @param int $index
 * @param string $delimiter
 * @return mixed
 */
function get_indexed_element_of_string_converted_to_array(string $str, int $index = 0, string $delimiter = '\\') {
    $str_arr = explode($delimiter, $str);
    return $str_arr[$index];
}

/**
 * Binds to the dirty BPANZ server with a username and password. Uses plain PHP.
 * @param
 * @param string $username [username must include the appropriate NetBIOS prefix, cannot be empty or null]
 * @param string $password [the password cannot be empty or null]
 * @return bool [true if binding succeeded]
 * @throws Exception
 */
function bind_to_dirty_bpanz_domain($ldap_server_link, string $username, string $password): bool {
    if (empty($username) || empty($password)) {
        // this should never occur as there's middleware that will check for empty usernames or passwords in the
        // Authorization header
        throw new Exception("Empty username or password!");
    }
    return ldap_bind($ldap_server_link, $username, $password);
}

use Toyota\Component\Ldap\Core\Manager;
use Toyota\Component\Ldap\Platform\Native\Driver;

/**
 * Binds to the dirty BPANZ server, using the 'tiesa/ldap' library.
 * @param string $username
 * @param string $password
 * @return Manager
 */
function obj_bind_to_dirty_bpanz_domain(string $username, string $password): Manager {
    $binding_params = [
        'hostname' => 'bpa-d-server01.bpanz.local',
        'base_dn' => 'dc=bpanz,dc=local'
    ];

    $manager = new Manager($binding_params, new Driver());

    $manager->connect();

    $manager->bind($username, $password);

    return $manager;
}

/**
 * Converts a Windows timestamp to UNIX
 * @param string|int|float $windows_time
 * @return float|int
 */
function convert_windows_timestamp_to_unix($windows_time) {
    return round(($windows_time / (10*1000*1000) - 11644473600));
}

/**
 * Searches for a user by the sAMAccountName (i.e. the user in the 'bpanz\user' string)
 * @param Manager $dirty_bpanz_connection
 * @param string $logon_name
 * @return array
 */
function search_for_user_in_ad_and_get_attributes(Manager $dirty_bpanz_connection, string $logon_name): array {
    $results = $dirty_bpanz_connection->search('OU=Users,OU=MyBusiness,DC=bpanz,DC=local', "(sAMAccountName={$logon_name})");
    $the_user = $results->current();
    if (empty($the_user) || $the_user === null) {
        return [];
    }
    return $the_user->getAttributes();
}