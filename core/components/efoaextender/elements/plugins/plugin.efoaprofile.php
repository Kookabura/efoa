<?php
switch ($modx->event->name) {

    case 'OnDocFormSave':

        // Add every newly created resource to Admin group
        if ($resource->get('action') == 'resource/create') {
            $resource->joinGroup(1);
        }

        break;

    case 'OnUserSave':

        // Create a user and resource groups on user save
        $modx->error->reset();

        $usergroup = [
            'name'  => 'profile_' . $user->get('username'),
            'aw_users' => $user->get('username')
        ];

        // Creating user group and resorce group if it doesn't exist
        if (!$u_group = $modx->getObject("modUserGroup", ['name' => $usergroup['name']])) {

            $response = $modx->runProcessor('security/group/create', $usergroup);

            if ($response->isError()) {
                $modx->log(modX::LOG_LEVEL_ERROR, "Error on group creation: \n" . print_r($response->getFieldErrors(), 1));
                continue;
            }

            $u_group = $response->getObject();
        }

        $u_group = is_object($u_group) ? $u_group->toArray() : $u_group;



        $resourcegroup = [
            'name' => 'profile_' . $user->get('username'),
        ];

        if (!$r_group = $modx->getObject('modResourceGroup', ['name' => $resourcegroup['name']])) {
            $response = $modx->runProcessor('security/resourcegroup/create', $resourcegroup);

            if ($response->isError()) {
                $modx->log(1, "Resourcegroup creation error: " . $response->getMessage());
                continue;
            }

            $r_group = $response->getObject();

        }

        $r_group = is_object($r_group) ? $r_group->toArray() : $r_group;


        // Add membership to defualt group and resource to user group mapping
        if ($policy = $modx->getObject('modAccessPolicy',array('name' => 'EFOA Resource'))) {
            $response = $modx->runProcessor('security/access/usergroup/resourcegroup/create', [
                'principal' => $u_group['id'],
                'principal_class' => 'modUserGroup',
                'target' => $r_group['id'],
                'context_key' => 'mgr',
                'authority' => 9999,
                'policy' => $policy->get('id')
            ]);

            if ($response->isError()) {
                $modx->log(modX::LOG_LEVEL_ERROR, "Can't add resource group permissions: " . print_r($response->getObject(), 1));
            }

        }


        if (!$user->isMember('EFOA Content Editor')) {
            $user->joinGroup(2, 1, 9999);
        }

        if (!$user->isMember($u_group['id'])) {
            $user->joinGroup($u_group['id'], 1, 9999);
        }


        // Create user profile resource page with username alias. If user profile exists assign it a user's resource group
        // Set the alias to the lowercase fullname replacing spaces with hyphens
        $resource_alias = strtolower(str_replace(" ", "-", $user->get('username')));
        if ($resource = $modx->getObject('modResource', ['alias' => $resource_alias])) {
            $modx->log(modX::LOG_LEVEL_INFO, 'Found resource with alias ' . $resource_alias);

            if (!$resource->isMember('profile_' . $resource->get('alias'))) {
                $response = $modx->runProcessor('security/resourcegroup/updateresourcesin', ['resource' => 'web_' . $resource->get('id'), 'resourceGroup' => 'n_dg_' . $r_group['id']]);

                if ($response->isError()) {
                    $modx->log(modX::LOG_LEVEL_ERROR, "Can't add access to user profile: " . print_r($response->getObject(), 1));
                }
            } else {
                $modx->log(modX::LOG_LEVEL_INFO, 'User ' . $user->get('username') . ' already has access to profile.');
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
                    'id' => $r_group['id'],
                    'name' => $r_group['name'],
                    'access' => true,
                    'menu' => null
                ]
            ];

            // Set the pagetitle and longtitle to the fullname
            // Change parent to be Artists parent ID
            // template_id
            $resource = [
                'pagetitle' => $user->get('fullname'),
                'longtitle' => $user->get('fullname'),
                'alias' => $resource_alias,
                'parent' => 2,
                'profile template_id' => 1,
                'published' => 1,
                'class_key' => 'mgResource',
                'resource_groups' => json_encode($userresource_group)];

            $response = $modx->runProcessor('resource/create', $resource);

            if ($response->isError()) {
                $modx->log(modX::LOG_LEVEL_ERROR, "Can't create profile page for user: " . print_r($response->getObject(), 1));
            }
        }
        break;

    case 'OnBeforeManagerPageInit':

        // Hide buttons for non-admins
        if ($modx->user->isMember('EFOA Content Editor')) {
            $modx->controller->addHtml('<style>
                .moregallery_v23 #modx-resource-tree-tbar .tree-new-gallery {
                    display: none;
                }
                #modx-resource-tree .modx-tree-node-tool-ct .icon-plus-circle {
                    display: none;
                }
                </style>');
        }

        // Customize profile and user update pages
        if (in_array($action['controller'], ['security/profile', 'security/user/update'])) {

            $modx->controller->addHtml('<script type="text/javascript">

                fields = [{
                    id: "modx-user-twitter"
                    ,name: "twitter"
                    ,fieldLabel: _("user_twitter")
                    ,xtype: "textfield"
                    ,anchor: "100%"
                    ,maxLength: 255
                    ,description: _("user_twitter_desc")
                }, {
                    xtype: "label"
                    ,forId: "modx-user-twitter"
                    ,html: _("user_twitter_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-facebook"
                    ,name: "facebook"
                    ,fieldLabel: _("user_facebook")
                    ,xtype: "textfield"
                    ,anchor: "100%"
                    ,maxLength: 255
                    ,description: _("user_facebook_desc")
                }, {
                    xtype: "label"
                    ,forId: "modx-user-facebook"
                    ,html: _("user_facebook_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-instagram"
                    ,name: "instagram"
                    ,fieldLabel: _("user_instagram")
                    ,xtype: "textfield"
                    ,anchor: "100%"
                    ,maxLength: 255
                    ,description: _("user_instagram_desc")
                }, {
                    xtype: "label"
                    ,forId: "modx-user-instagram"
                    ,html: _("user_instagram_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-pinterest"
                    ,name: "pinterest"
                    ,fieldLabel: _("user_pinterest")
                    ,xtype: "textfield"
                    ,anchor: "100%"
                    ,maxLength: 255
                    ,description: _("user_pinterest_desc")
                }, {
                    xtype: "label"
                    ,forId: "modx-user-pinterest"
                    ,html: _("user_pinterest_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-linkedin"
                    ,name: "linkedin"
                    ,fieldLabel: _("user_linkedin")
                    ,xtype: "textfield"
                    ,anchor: "100%"
                    ,maxLength: 255
                    ,description: _("user_linkedin_desc")
                }, {
                    xtype: "label"
                    ,forId: "modx-user-linkedin"
                    ,html: _("user_linkedin_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-youtube"
                    ,name: "youtube"
                    ,fieldLabel: _("user_youtube")
                    ,xtype: "textfield"
                    ,anchor: "100%"
                    ,maxLength: 255
                    ,description: _("user_youtube_desc")
                }, {
                    xtype: "label"
                    ,forId: "modx-user-youtube"
                    ,html: _("user_youtube_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-shop"
                    ,name: "shop"
                    ,fieldLabel: _("user_shop")
                    ,xtype: "textfield"
                    ,anchor: "100%"
                    ,maxLength: 255
                    ,description: _("user_shop_desc")
                }, {
                    xtype: "label"
                    ,forId: "modx-user-shop"
                    ,html: _("user_shop_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-discipline"
                    ,fieldLabel: _("user_discipline")
                    ,xtype: "efoa-combo-units"
                    ,dataIndex : "discipline" // Empty field on page reload without it
                    ,description: _("user_discipline_desc")
                }, {
                    xtype: "label"
                    ,forId: "modx-user-discipline"
                    ,html: _("user_discipline_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-biography"
                    ,name: "biography"
                    ,fieldLabel: _("user_biography")
                    ,xtype: "textarea"
                    ,anchor: "100%"
                    ,maxLength: 255
                    ,description: _("user_biography_desc")
                    ,cls: "modx-richtext"
                }, {
                    xtype: "label"
                    ,forId: "modx-user-biography"
                    ,html: _("user_biography_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-cv"
                    ,name: "cv"
                    ,fieldLabel: _("user_cv")
                    ,xtype: "textarea"
                    ,anchor: "100%"
                    ,maxLength: 255
                    ,description: _("user_cv_desc")
                    ,cls: "modx-richtext"
                }, {
                    xtype: "label"
                    ,forId: "modx-user-cv"
                    ,html: _("user_cv_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-highlight_image"
                    ,name: "highlight_image"
                    ,fieldLabel: _("user_highlight_image")
                    ,xtype: "modx-combo-browser"
                    ,hideFiles: true
                    ,source: MODx.config["photo_profile_source"] || MODx.config.default_media_source
                    ,hideSourceCombo: true
                    ,description: _("user_highlight_image_desc")
                }, {
                    xtype: "label"
                    ,forId: "modx-user-highlight_image"
                    ,html: _("user_highlight_image_desc")
                    ,cls: "desc-under"
                }, {
                    id: "modx-user-summary"
                    ,name: "summary"
                    ,fieldLabel: _("user_summary")
                    ,xtype: "textarea"
                    ,anchor: "100%"
                    ,maxLength: 255
                    ,description: _("user_summary_desc")
                    ,cls: "modx-richtext"
                }, {
                    xtype: "label"
                    ,forId: "modx-user-summary"
                    ,html: _("user_summary_desc")
                    ,cls: "desc-under"
                }];


                MODx.combo.Units = function(config) {
                    config = config || {};
                    Ext.applyIf(config,{
                        xtype: "superboxselect"
                        ,name: config.name || "discipline"
                        ,allowBlank: true
                        ,msgTarget: "under"
                        ,addNewDataOnBlur: true
                        ,pinList: false
                        ,resizable: true
                        ,minChars: 1
                        ,store: new Ext.data.ArrayStore({
                            id: (config.name || "discipline") + "-store"
                            ,fields: ["display"]
                            ,data: [
                                ["Ceramics"],
                                ["Digital Art"],
                                ["Film"],
                                ["Glass"],
                                ["Jewellery"],
                                ["Mixed Media"],
                                ["Painting"],
                                ["Photography"],
                                ["Printmaking"],
                                ["Sculpture"],
                                ["Textiles"],
                                ["Other Media"]
                            ]
                        })
                        ,mode: "local"
                        ,triggerAction: "all"
                        ,displayField: "display"
                        ,valueField: "display"
                        ,extraItemCls: "x-tag"
                        ,expandBtnCls: "x-form-trigger"
                        ,clearBtnCls: "x-form-trigger"
                    });
                    config.name += "[]";

                    MODx.combo.Units.superclass.constructor.call(this,config);
                };
                Ext.extend(MODx.combo.Units, Ext.ux.form.SuperBoxSelect);
                Ext.reg("efoa-combo-units",MODx.combo.Units);

                // Extend security/user/update page
                Ext.ComponentMgr.onAvailable("modx-panel-user", function (e) {

                    var items = Ext.getCmp("modx-panel-user").items[1].items[0].items[0].items[0].items;
                    //console.log(items);

                    fields = fields.concat([{
                        id: "modx-user-openhouse_host"
                        ,name: "openhouse_host"
                        ,fieldLabel: _("user_openhouse_host")
                        ,xtype: "xcheckbox"
                        ,anchor: "100%"
                        ,description: _("user_openhouse_host_desc")
                    }, {
                        xtype: "label"
                        ,forId: "modx-user-openhouse_host"
                        ,html: _("user_openhouse_host_desc")
                        ,cls: "desc-under"
                    }, {
                        id: "modx-user-openhouse_exhibiting"
                        ,name: "openhouse_exhibiting"
                        ,fieldLabel: _("user_openhouse_exhibiting")
                        ,xtype: "xcheckbox"
                        ,anchor: "100%"
                        ,description: _("user_openhouse_exhibiting_desc")
                    }, {
                        xtype: "label"
                        ,forId: "modx-user-openhouse_exhibiting"
                        ,html: _("user_openhouse_exhibiting_desc")
                        ,cls: "desc-under"
                    }, {
                        id: "modx-user-openhouse_number"
                        ,name: "openhouse_number"
                        ,fieldLabel: _("user_openhouse_number")
                        ,xtype: "numberfield"
                        ,anchor: "100%"
                        ,description: _("user_openhouse_number_desc")
                    }, {
                        xtype: "label"
                        ,forId: "modx-user-openhouse_number"
                        ,html: _("user_openhouse_number_desc")
                        ,cls: "desc-under"
                    }])

                    // Set id for photo field
                    items[2].id = "modx-user-photo";

                    items.push(fields);


                    Ext.getCmp("modx-panel-user").listeners.setup = function() {
                        if (this.config.user === "" || this.config.user === 0) {
                            this.fireEvent("ready");
                            return false;
                        }
                        MODx.Ajax.request({
                            url: this.config.url
                            ,params: {
                                action: "security/user/get"
                                ,id: this.config.user
                                ,getGroups: true
                            }
                            ,listeners: {
                                "success": {fn:function(r) {
                                    this.getForm().setValues(r.object);

                                    var d = Ext.decode(r.object.groups);
                                    var g = Ext.getCmp("modx-grid-user-groups");
                                    if (g) {
                                        var s = g.getStore();
                                        if (s) { s.loadData(d); }
                                    }
                                    Ext.get("modx-user-header").update(_("user")+": "+r.object.username);
                                    this.fireEvent("ready",r.object);
                                    MODx.fireEvent("ready");

                                    // Loads Redactor for every field with modx-richtext class
                                    if (MODx.loadRTE) {
                                        // Will transform the textarea to richtext
                                        MODx.loadRTE(false);
                                    }
                                },scope:this}
                            }
                        });
                    }


                });

                // Extend security/profile page
                Ext.ComponentMgr.onAvailable("modx-panel-profile-update", function (e) {

                    var items = Ext.getCmp("modx-panel-profile-update").items;
                    // Removeing fax, state, date of birth
                    //console.log(items);
                    items.splice(5, 3);

                    // Renaming zip label to post code
                    items[5].fieldLabel = _("user_zip_uk");


                    fields = fields.concat([{
                        id: "modx-user-comment"
                        ,name: "comment"
                        ,fieldLabel: _("comment")
                        ,xtype: "textarea"
                        ,anchor: "100%"
                        ,maxLength: 255
                        ,grow: true
                    }, {
                        xtype: "label"
                        ,forId: "modx-user-comment"
                        ,html: _("user_openhouse_host_desc")
                        ,cls: "desc-under"
                    }, {
                        id: "modx-user-address"
                        ,name: "address"
                        ,fieldLabel: _("address")
                        ,xtype: "textarea"
                        ,anchor: "100%"
                        ,grow: true
                    }, {
                        xtype: "label"
                        ,forId: "modx-user-address"
                        ,html: _("user_address_desc")
                        ,cls: "desc-under"
                    }, {
                        id: "modx-user-city"
                        ,name: "city"
                        ,fieldLabel: _("city")
                        ,xtype: "textfield"
                        ,anchor: "100%"
                    }, {
                        xtype: "label"
                        ,forId: "modx-user-city"
                        ,html: _("user_city_desc")
                        ,cls: "desc-under"
                    }, {
                        id: "modx-user-website"
                        ,name: "website"
                        ,fieldLabel: _("user_website")
                        ,xtype: "textfield"
                        ,anchor: "100%"
                        ,maxLength: 255
                    }, {
                        xtype: "label"
                        ,forId: "modx-user-website"
                        ,html: _("user_openhouse_host_desc")
                        ,cls: "desc-under"
                    }, {
                        id: "modx-user-openhouse_host"
                        ,name: "openhouse_host"
                        ,fieldLabel: _("user_openhouse_host")
                        ,xtype: "xcheckbox"
                        ,anchor: "100%"
                        ,disabled: true
                    }, {
                        xtype: "label"
                        ,forId: "modx-user-openhouse_host"
                        ,html: _("user_openhouse_host_desc")
                        ,cls: "desc-under"
                    }, {
                        id: "modx-user-openhouse_exhibiting"
                        ,name: "openhouse_exhibiting"
                        ,fieldLabel: _("user_openhouse_exhibiting")
                        ,xtype: "xcheckbox"
                        ,anchor: "100%"
                        ,disabled: true
                    }, {
                        xtype: "label"
                        ,forId: "modx-user-openhouse_exhibiting"
                        ,html: _("user_openhouse_host_desc")
                        ,cls: "desc-under"
                    }, {
                        id: "modx-user-openhouse_number"
                        ,name: "openhouse_number"
                        ,fieldLabel: _("user_openhouse_number")
                        ,xtype: "numberfield"
                        ,anchor: "100%"
                        ,readOnly: true
                    }, {
                        xtype: "label"
                        ,forId: "modx-user-openhouse_number"
                        ,html: _("user_openhouse_host_desc")
                        ,cls: "desc-under"
                    }])


                    // Add event to load image preview and Redactor
                    Ext.getCmp("modx-panel-profile-update").listeners.setup = function() {
                        MODx.Ajax.request({
                            url: MODx.config.connector_url
                            ,params: {
                                action: "security/profile/get"
                                ,id: this.config.user
                            }
                            ,listeners: {
                                "success": {fn:function(r) {
                                    this.getForm().setValues(r.object);

                                    // Remove preview if it exists
                                    if (Ext.get("photo-preview")) {
                                        Ext.get("photo-preview").remove();
                                    }
                                    // Add new preview
                                    Ext.get("modx-user-photo").parent().parent().createChild("<img id=\"photo-preview\" style=\"margin-top: 10px;\" width=\"200\" src=\"/assets/profiles/" + r.object.photo + "\" />");

                                    // Loads Redactor for every field with modx-richtext class
                                    if (MODx.loadRTE) {
                                        // Will transform the textarea to RTE
                                        MODx.loadRTE(false);
                                    }
                                },scope:this}
                            }
                        });
                    };

                    // Set id for photo field
                    items[2].id = "modx-user-photo";


                    items.push(fields);


                });

            </script>');


            $result = $modx->invokeEvent('OnRichTextEditorInit');
            $modx->controller->addHtml($result[0]);

        }
        break;

	case 'OnMODXInit':

	    // Load additional user fields and lexicons
	    $modx->lexicon->load('core:efoa');
    	$modx->loadClass('modUserProfile');
        $fields = [
            'twitter' => [
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false
                ],
            'facebook' => [
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false
                ],
            'instagram' => [
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false
            ],
            'pinterest' => [
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false
            ],
            'linkedin' => [
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false
            ],
            'youtube' => [
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false
            ],
            'shop' => [
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false
            ],
            'discipline' => [
                'dbtype' => 'text',
                'phptype' => 'json',
                'null' => false
            ],
            'biography' => [
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => false,
            ],
            'cv' => [
                'dbtype' => 'mediumtext',
                'phptype' => 'string',
                'null' => false
            ],
            'highlight_image' => [
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false
            ],
            'summary' => [
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => false
            ],
            'openhouse_host' => [
                'dbtype' => 'tinyint',
                'phptype' => 'boolean',
                'precision' => '1',
                'null' => false,
                'default' => 0
            ],
            'openhouse_exhibiting' => [
                'dbtype' => 'tinyint',
                'phptype' => 'boolean',
                'precision' => '1',
                'null' => false,
                'default' => 0
            ],
            'openhouse_number' => [
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'default' => 0
            ]
        ];

        foreach ($fields as $key => $data) {
            $modx->map['modUserProfile']['fields'][$key] = $key == 'discipline' ? [] : '';
        	$modx->map['modUserProfile']['fieldMeta'][$key] = $data;
        }
	break;
}
