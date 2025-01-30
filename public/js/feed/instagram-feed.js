pimcore.registerNS('SocialData.Feed.Instagram');
SocialData.Feed.Instagram = Class.create(SocialData.Feed.AbstractFeed, {

    panel: null,

    getLayout: function () {

        this.panel = new Ext.form.FormPanel({
            title: false,
            defaults: {
                labelWidth: 200
            }
        });

        Ext.Ajax.request({
            url: Routing.generate('social_data_connector_instagram_feed_config'),
            method: 'GET',
            success: function (response) {

                var res = Ext.decode(response.responseText);

                if (res.success !== true) {
                    Ext.MessageBox.alert(t('error'), res.message);
                    return;
                }

                this.panel.add(this.getConfigFields(res.data));

            }.bind(this)
        });

        return this.panel;
    },

    getConfigFields: function (feedConfig) {

        var fields = [];

        if (feedConfig.apiType === 'facebook_login') {
            fields.push({
                xtype: 'combo',
                value: this.data !== null ? this.data['accountId'] : null,
                fieldLabel: t('social_data.wall.feed.instagram.account_id'),
                name: 'accountId',
                labelAlign: 'left',
                anchor: '100%',
                flex: 1,
                displayField: 'key',
                valueField: 'value',
                mode: 'local',
                triggerAction: 'all',
                queryDelay: 0,
                editable: false,
                summaryDisplay: true,
                allowBlank: false,
                store: new Ext.data.Store({
                    fields: ['value', 'key'],
                    data: feedConfig.accounts
                })
            });
        } else {
            fields.push({
                xtype: 'container',
                html: t('social_data.wall.feed.instagram.basic_display_api_only_note'),
                anchor: '100%',
                style: {
                    padding: '5px',
                    margin: '10px 0',
                    background: '#c5d8c5'
                },
                flex: 1
            });
        }

        fields.push({
            xtype: 'numberfield',
            value: this.data !== null ? this.data['limit'] : null,
            fieldLabel: t('social_data.wall.feed.instagram.limit'),
            name: 'limit',
            maxValue: 99,
            minValue: 0,
            labelAlign: 'left',
            anchor: '100%',
            flex: 1
        });

        return fields;
    },

    isValid: function () {
        return this.panel.form.isValid();
    },

    getValues: function () {
        return this.panel.form.getValues();
    }
});