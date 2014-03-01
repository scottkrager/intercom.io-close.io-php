<?php
    $request = curl_init();
    $headers = array('Content-Type: application/json');
    // max per call is 500 leads.
    // docs for getting all users http://doc.intercom.io/api/v1/#getting-all-users
    curl_setopt($request, CURLOPT_URL, "https://api.intercom.io/v1/users/");
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($request, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($request, CURLOPT_BUFFERSIZE, 4096);
    curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($request, CURLOPT_TIMEOUT, 60);
    curl_setopt($request, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // Your Intercom.io app-id : APIkey
    curl_setopt($request, CURLOPT_USERPWD, 'yourapp-id-here:yourapikeyhere');

    $response = curl_exec($request);
    $leads = json_decode($response, true);


    // OK, now that we have all the current leads we want to update from intercom, we need to get
    // the close.io ID for the lead because that's the only way to update via their API PUT call
    // This will loop through each intercom lead, find the closio, id, then use that to update
    // the custom Sessions field for the lead in close.io - cool beans.
    foreach ($leads['users'] as $lead) {
        // this is what we will use to find the lead in close.io
        $lead_subdomain = $lead['custom_data']['subdomain'];
        // this is the great data point for sales peeps
        $lead_sessions =  $lead['session_count'];
        // also useful for sales, when did the lead last login
        $lead_last_seen =  $lead['last_impression_at'];
        // format the timestamp into more readable date
        $last_seen = date('Y-m-d H:i:s', $lead_last_seen);

        $request_loop = curl_init();
        $headers_loop = array('Content-Type: application/json');
        // find the lead in close.io. Here we do it by a custom_data point from intercom, you'll need to update this
        curl_setopt($request_loop, CURLOPT_URL, "https://app.close.io/api/v1/lead/?query=company='.$lead_subdomain.'");
        curl_setopt($request_loop, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request_loop, CURLOPT_HTTPHEADER, $headers_loop);
        curl_setopt($request_loop, CURLOPT_BUFFERSIZE, 4096);
        curl_setopt($request_loop, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($request_loop, CURLOPT_TIMEOUT, 60);
        curl_setopt($request_loop, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // no password is set. Just your close.io API key
        curl_setopt($request_loop, CURLOPT_USERPWD, 'yourcloseioapikeyhere:');

        $response_loop = curl_exec($request_loop);
        $leads_loop = json_decode($response_loop, true);

        $closeio_id = $leads_loop['data'][0]['id'];  // works to get close.io id of first match

        // now lets PUT to update the close.io id with the lead sessions number as a custom data point
        // as well as the last_seen datapoint
        // $update_json = '{ custom.field_name: updated_value }';
        $update_json = '{
                    "custom.Sessions": "'.$lead_sessions.'",
                    "custom.LastSeen": "'.$last_seen.'"
                }';

        $request_loop_put = curl_init();
        // the PUT url here updates the lead based on the lead.id we found earlier
        curl_setopt($request_loop_put, CURLOPT_URL, "https://app.close.io/api/v1/lead/" . $closeio_id ."/");
        curl_setopt($request_loop_put, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($request_loop_put, CURLOPT_POSTFIELDS, $update_json);
        curl_setopt($request_loop_put, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request_loop_put, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($update_json))
        );
        curl_setopt($request_loop_put, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // again, no password here, just use your API key as the username.
        curl_setopt($request_loop_put, CURLOPT_USERPWD, 'yourcloseioapikey:');
    
        $response_loop_put = curl_exec($request_loop_put);
    }
?>
