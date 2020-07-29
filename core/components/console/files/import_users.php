<?php
/* User available fields are
    - all fields from modUser object
    - all fields from modUserProfile object
    - 
*/

// There are script parameters
// Default csv delimeter ';'
$delimeter = ';';
// Path to file
$file = MODX_BASE_PATH . 'assets/files/users.csv';
// Debug status. If enabled advanced messages will be shown
$is_debug = true;
// Profile's root resource id 
$profile_root = 2;
$profile_template_id = 1;
$default_groups = [
            // EFOA Content Editor group
            [
                'usergroup' => 2,
                'role' => 1,
                'rank' => 9999
            ]
        ]; 

$modx->setLogLevel($is_debug ? modX::LOG_LEVEL_INFO : modX::LOG_LEVEL_ERROR);

if (!file_exists($file)) {
	$modx->log(modX::LOG_LEVEL_ERROR, 'File not found at '.$file.'.');
	exit;
}

// Processing file line by line
$handle = fopen($file, "r");
$rows = $created = $updated = 0;
$headers = [];

while (($csv = fgetcsv($handle, 0, $delimeter)) !== false) {
    
    $properties = [];
    
    // If line 1 then get headers and pass to second line
    if ($rows == 0) {
        
        // remove BOM if it exists
        $bom = pack('H*','EFBBBF');
        $csv[0] = preg_replace("/^$bom/", '', $csv[0]);
        
        // setting headers
        $headers = $csv;
        $modx->log(modX::LOG_LEVEL_INFO, "Headers are parsed: \n".print_r($headers,1));
        $rows ++;
        continue;
    }
    
    // Filling user properties from csv
    foreach ($csv as $key => $field) {
        $properties[$headers[$key]] = $field;
    }
    
    $modx->log(modX::LOG_LEVEL_INFO, "Importing user ". $properties['username']);
    
    $properties['active'] = 1;
    
    if ($properties['discipline']) {
        $properties['discipline'] = explode(',', $properties['discipline']);
        $properties['discipline'] = array_map('trim', $properties ['discipline']);
    } else {
        $properties['discipline'] = [];
    }
    
    if ($properties['password']) {
        $properties = array_merge($properties, [
            'newpassword' => true,
            'passwordgenmethod' => 'spec',
            'specifiedpassword' => $properties['password'],
            'confirmpassword' => $properties['password'],
            'passwordnotifymethod' => 's'
        ]);
    }
    
    $rows ++;
    $modx->error->reset();

    $modx->log(modX::LOG_LEVEL_INFO, "User properties are: \n". print_r($properties,1));
    
    // If user exists then update it
    if ($user = $modx->getObject('modUser', ['username' => $properties['username']])) {
        $properties['id'] = $user->get('id');
        $action = 'update';
    } else {
        $action = 'create';
    }
    
    $modx->log(modX::LOG_LEVEL_INFO, "Action is: ".$action);
    
    $response = $modx->runProcessor('security/user/' . $action, $properties);
    
    if ($response->isError()) {
        $modx->log(modX::LOG_LEVEL_ERROR, "Can't " . $action . " user: " . print_r($response->getFieldErrors(), 1));
        continue;
    } else {
        if ($action == 'create') {
            $created++;
        } else {
            $updated++;
        }
    }
    
    $modx->log(modX::LOG_LEVEL_INFO, "The result of " . $action . " for user " . $properties['username'] . " \n" . print_r($properties, 1) . " is \n" . print_r($response->getObject(), 1));
    
}

$modx->log(modX::LOG_LEVEL_INFO, "Processed " . $rows . " lines.\nUpdated users: " . $updated . "\nCreated users: " . $created);