pimcore.registerNS('SocialData.Connector.Instagram');
SocialData.Connector.Instagram = Class.create(SocialData.Connector.AbstractConnector, {

    hasCustomConfiguration: function () {
        return true;
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
            loginWindow,
            loginTimer,
            stateData = null,
            windowSize = {
                width: 800,
                height: 550,
            },
            windowLocation = {
                left: ((window.screenLeft ? window.screenLeft : window.screenX) + (window.innerWidth / 2)) - (windowSize.width / 2),
                top: ((window.screenTop ? window.screenTop : window.screenY) + (window.screen.availHeight / 2)) - (window.innerHeight / 2)
            },
            features = [
                'toolbar=1',
                'location=1',
                'width=' + windowSize.width,
                'height=' + windowSize.height,
                'left=' + windowLocation.left,
                'top=' + windowLocation.top,
            ],
            checkPopupState = function checkLoginWindowClosure() {

                var stateElement,
                    popupDocument;

                if (!loginWindow) {
                    return;
                }

                if (stateData !== null) {

                    loginWindow.close();

                    clearInterval(loginTimer);
                    win.setLoading(false);

                    if (stateData.error === true) {
                        btn.setDisabled(false);
                        Ext.MessageBox.alert(t('error') + ' ' + stateData.identifier, stateData.description + ' (' + stateData.reason + ')');
                        return;
                    }

                    win.close();
                    this.stateHandler('connection', mainBtn);

                    return;

                } else if (loginWindow.closed) {

                    clearInterval(loginTimer);
                    btn.setDisabled(false);
                    win.setLoading(false);

                    return;
                }

                try {
                    popupDocument = loginWindow.document;
                } catch (error) {
                    return;
                }

                if (popupDocument.domain !== document.domain) {
                    return;
                }

                try {
                    stateElement = popupDocument.getElementById('connect-response');
                } catch (error) {
                    return;
                }

                if (stateElement) {
                    stateData = Ext.decode(stateElement.value);
                }

            }.bind(this);

        btn.setDisabled(true);
        win.setLoading(true);

        // use http://localhost:2332 or something in dev context
        loginWindow = window.open(window.location.origin + '/admin/social-data/connector/instagram/connect', 'LoginWindow', features.join(','));
        loginTimer = setInterval(checkPopupState, 500);
    },

    getCustomConfigurationFields: function (data) {

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