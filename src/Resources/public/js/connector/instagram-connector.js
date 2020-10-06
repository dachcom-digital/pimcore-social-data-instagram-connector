pimcore.registerNS('SocialData.Connector.Instagram');
SocialData.Connector.Instagram = Class.create(SocialData.Connector.AbstractConnector, {

    hasCustomConfiguration: function () {
        return true;
    },

    afterSaveCustomConfiguration: function () {

        var fieldset = this.customConfigurationPanel.up('fieldset').previousSibling();

        this.changeState(fieldset, 'connection');
    },

    afterChangeState: function (stateType, active) {
        if (stateType === 'connection' && active === true) {
            this.refreshCustomConfigurationPanel();
        }
    },

    beforeDisableFieldState: function (stateType, toDisableState) {

        if (stateType === 'connection' && toDisableState === false) {
            console.log(this.customConfiguration);
            return !(
                this.customConfiguration.hasOwnProperty('appId') &&
                this.customConfiguration.hasOwnProperty('appSecret') &&
                this.customConfiguration.hasOwnProperty('apiType')
            );
        }

        return toDisableState;
    },

    connectHandler: function (stateType, mainBtn) {

        var win,
            stateData = this.states[stateType],
            flag = this.data[stateData.identifier] === true ? 'deactivate' : 'activate';

        // just go by default
        if (flag === 'deactivate') {
            this.stateHandler(stateType, mainBtn);
            return;
        }

        mainBtn.setDisabled(true);

        win = new Ext.Window({
            width: 400,
            modal: true,
            bodyStyle: 'padding:10px',
            title: t('social_data.connector.instagram.connect_service'),
            html: t('social_data.connector.instagram.connect_service_note'),
            listeners: {
                beforeclose: function () {
                    mainBtn.setDisabled(false);
                }
            },
            buttons: [
                {
                    text: t('social_data.connector.instagram.connect'),
                    iconCls: 'pimcore_icon_open_window',
                    handler: this.handleConnectWindow.bind(this, mainBtn)
                }
            ]
        });

        win.show();
    },

    handleConnectWindow: function (mainBtn, btn) {

        var win = btn.up('window'),
            connectWindow;

        btn.setDisabled(true);
        win.setLoading(true);

        connectWindow = new SocialData.Component.ConnectWindow(
            '/admin/social-data/connector/instagram/connect',
            // success
            function (stateData) {
                win.setLoading(false);
                win.close();
                this.stateHandler('connection', mainBtn);
            }.bind(this),
            // error
            function (stateData) {
                win.setLoading(false);
                btn.setDisabled(false);
                Ext.MessageBox.alert(t('error') + ' ' + stateData.identifier, stateData.description + ' (' + stateData.reason + ')');
            },
            // closed
            function () {
                btn.setDisabled(false);
                win.setLoading(false);
            }
        );

        connectWindow.open();
    },

    getCustomConfigurationFields: function () {

        var data = this.customConfiguration;

        return [
            {
                xtype: 'textfield',
                fieldLabel: 'Token Expiring Date',
                disabled: true,
                hidden: !data.hasOwnProperty('accessToken') || data.accessToken === null || data.accessToken === '',
                value: data.hasOwnProperty('accessTokenExpiresAt') ? data.accessTokenExpiresAt === null ? 'never' : data.accessTokenExpiresAt : '--'
            },
            {
                xtype: 'textfield',
                name: 'appId',
                fieldLabel: 'App ID',
                allowBlank: false,
                value: data.hasOwnProperty('appId') ? data.appId : null
            },
            {
                xtype: 'textfield',
                name: 'appSecret',
                fieldLabel: 'App Secret',
                allowBlank: false,
                value: data.hasOwnProperty('appSecret') ? data.appSecret : null
            },
            {
                xtype: 'fieldcontainer',
                layout: 'vbox',
                fieldLabel: 'API Type',
                items: [
                    {
                        xtype: 'combo',
                        name: 'apiType',
                        fieldLabel: false,
                        displayField: 'key',
                        valueField: 'value',
                        mode: 'local',
                        labelAlign: 'left',
                        value: data.hasOwnProperty('apiType') ? data.apiType : 'private',
                        triggerAction: 'all',
                        anchor: '100%',
                        queryDelay: 0,
                        editable: false,
                        summaryDisplay: true,
                        allowBlank: false,
                        store: new Ext.data.ArrayStore({
                            fields: ['value', 'key'],
                            data: [
                                ['private', 'Private'],
                                ['business', 'Business'],
                            ]
                        }),
                        listeners: {
                            change: function (field, value) {
                                var labelField = this.up('fieldcontainer').query('container')[0];
                                labelField.update(t('social_data.connector.instagram.connect_api_type_' + value));
                            }
                        }
                    },
                    {
                        xtype: 'container',
                        width: 500,
                        style: {
                            padding: '5px',
                            border: '2px dashed #c1c1c1;',
                            lineHeight: 1.5
                        },
                        html: data.hasOwnProperty('apiType') ? t('social_data.connector.instagram.connect_api_type_' + data.apiType) : t('social_data.connector.instagram.connect_api_type_private')
                    }
                ]
            }
        ];
    }
});