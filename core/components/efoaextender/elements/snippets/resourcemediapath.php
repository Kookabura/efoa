<?php
/**
 * @name ResourceMediaPath
 * @description Dynamically calculates the upload path for a given resource
 *
 * This Snippet is meant to dynamically calculate your baseBath attribute
 * for custom Media Sources.  This is useful if you wish to shepard uploaded
 * images to a folder dedicated to a given resource.  E.g. page 123 would
 * have its own images that page 456 could not reference.
 *
 * USAGE:
 * [[ResourceMediaPath? &pathTpl=`assets/test/{username}`]]
 *
 * PARAMETERS
 * &pathTpl string formatting string specifying the file path.
 *		Relative to MODX base_path
 *		Available placeholders: {usename}, {user.fullname}
 * &createFolder (optional) boolean whether or not to create
 */
$pathTpl = $modx->getOption('pathTpl', $scriptProperties, '');
$createfolder = $modx->getOption('createFolder', $scriptProperties, false);

$path = '';
$createpath = false;

if (empty($pathTpl)) {
    $modx->log(MODX_LOG_LEVEL_ERROR, '[ResourceMediaPath]: pathTpl not specified.');
    return;
}

$path = $pathTpl;
$ultimateParent = '';

if ($modx->context->key == 'mgr') {

    if ($user = $modx->user) {
        if ($profile = $modx->user->getOne('Profile')) {
            $path = str_replace('{username}', $modx->user->get('username'), $path);
            $path = str_replace('{user.fullname}', str_replace(['-', ' '],'.',strtolower($profile->get('fullname'))), $path);
        } else {
            $path = 'assets/profiles/';
        }
    } else {
        $path = 'assets/profiles/';
    }
} else {
    $path = str_replace('{username}', str_replace(['-', ' '],'.',strtolower($modx->resource->get('alias'))), $path);
}

$fullpath = $modx->getOption('base_path') . $path;

if ($createfolder && !file_exists($fullpath)) {

    $permissions = octdec('0' . (int)($modx->getOption('new_folder_permissions', null, '755', true)));
    if (!@mkdir($fullpath, $permissions, true)) {
        $modx->log(MODX_LOG_LEVEL_ERROR, sprintf('[ResourceMediaPath]: could not create directory %s).', $fullpath));
    } else {
        chmod($fullpath, $permissions);
    }
}

return $path;
