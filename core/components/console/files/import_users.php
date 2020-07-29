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
    
    $properties['groups'] = $default_groups;
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
    
    // Creating user group and resorce group if it doesn't exist
    if (!$usergroup = $modx->getObject("modUserGroup", ['name' => 'profile_' . $properties['username']])) {
        $response = $modx->runProcessor('security/group/create', [
            'name' => 'profile_' . $properties['username'],
        ]);
        
        // If error switch to another user
        if ($response->isError()) {
            $modx->log(modX::LOG_LEVEL_ERROR, "Error on group creation: \n" . print_r($response->getFieldErrors(), 1));
            continue;
        }
        
        $usergroup = $response->getObject();
        $userfroup_id = $usergroup['id'];
    } else {
        $usergroup_id = $usergroup->get('id');
    }
    
    $properties['groups'][] = [
        'usergroup' => $usergroup_id,
        'role' => 1,
        'rank' => 9999
    ];
    
    
    // Creating resource group for a user
    if (!$resourcegroup = $modx->getObject('modResourceGroup', ['name' => 'profile_' . $properties['username']])) {
        $response = $modx->runProcessor('security/resourcegroup/create', [
            'name' => 'profile_' . $properties['username'],
            'access_contexts' => 'mgr',
            'access_usergroups' => 'profile_' . $properties['username']
        ]);
        
        // If error switch to another user
        if ($response->isError()) {
            $modx->log(modX::LOG_LEVEL_ERROR, "Error on resource group creation: \n" . print_r($response->getFieldErrors(), 1));
            continue;
        }
        
        $resourcegroup = $response->getObject();
    }
    
    $resourcegroup = is_object($resourcegroup) ? $resourcegroup->toArray() : $resourcegroup;
    
    
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
    
    // Create user profile resource page with username alias. If user profile exists assign it a user's resource group
    if ($resource = $modx->getObject('modResource', ['alias' => $properties['username']])) {
        $modx->log(modX::LOG_LEVEL_INFO, 'Found resource with alias ' . $properties['username']);
        
        if (!$resource->isMember('profile_' . $resource->get('alias'))) {
            $response = $modx->runProcessor('security/resourcegroup/updateresourcesin', ['resource' => 'web_' . $resource->get('id'), 'resourceGroup' => 'n_dg_' . $resourcegroup['id']]);
            
            if ($response->isError()) {
                $modx->log(modX::LOG_LEVEL_ERROR, "Can't add access to user profile: " . print_r($response->getObject(), 1));
            }
        } else {
            $modx->log(modX::LOG_LEVEL_INFO, 'User ' . $properties['username'] . ' already has access to profile.');
        }
        
        
    } else {
        // Create profile page and add it to user resource gorup for allowing access
        $userresource_group = [
            [
                'id' => 1,
                'name' => 'Admin',
                'access' => true,
                'menu' => null
            ],
            [
                'id' => $resourcegroup['id'],
                'name' => $resourcegroup['name'],
                'access' => true,
                'menu' => null
            ]
        ];
        
        
        $resource = [
            'pagetitle' => $properties['fullname'],
            'alias' => $properties['username'],
            'parent' => $profile_root,
            'profile template_id' => $profile_template_id,
            'published' => 1,
            'class_key' => 'mgResource',
            'resource_groups' => json_encode($userresource_group)];
            
        $response = $modx->runProcessor('resource/create', $resource);
        
        if ($response->isError()) {
            $modx->log(modX::LOG_LEVEL_ERROR, "Can't create profile page for user: " . print_r($response->getObject(), 1));
        }
    }
    
    
    
}

$modx->log(modX::LOG_LEVEL_INFO, "Processed " . $rows . " lines.\nUpdated users: " . $updated . "\nCreated users: " . $created);