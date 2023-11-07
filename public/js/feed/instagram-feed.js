pimcore.registerNS('SocialData.Feed.Instagram');
SocialData.Feed.Instagram = Class.create(SocialData.Feed.AbstractFeed, {

    panel: null,

    getLayout: function () {

        this.panel = new Ext.form.FormPanel({
            title: false,
            defaults: {
                labelWidth: 200
            },
            items: this.getConfigFields()
        });

        return this.panel;
    },

    getConfigFields: function () {

        var fields = [];

        fields.push(
            {
                xtype: 'container',
                html: t('social_data.wall.feed.instagram.basic_display_api_only_note'),
                anchor: '100%',
                style: {
                    padding: '5px',
                    margin: '10px 0',
                    background: '#c5d8c5'
                },
                flex: 1
            }
        );

        fields.push(
            {
                xtype: 'numberfield',
                value: this.data !== null ? this.data['limit'] : null,
                fieldLabel: t('social_data.wall.feed.instagram.limit'),
                name: 'limit',
                maxValue: 99,
                minValue: 0,
                labelAlign: 'left',
                anchor: '100%',
                flex: 1
            }
        );

        return fields;
    },

    isValid: function () {
        return this.panel.form.isValid();
    },

    getValues: function () {
        return this.panel.form.getValues();
    }
});