TailwindHelper.util.scanClasses = function () {
    var register = 'mgr';
    var topic = '/scanclasses/';
    var console = MODx.load({
        xtype: 'tailwindhelper-window-console',
        register: register,
        topic: topic,
        clear: true,
        show_filename: 0,
        listeners: {
            shutdown: {
                fn: function () {
                },
                scope: this
            }
        }
    });
    console.show(Ext.getBody());

    MODx.Ajax.request({
        url: TailwindHelper.config.connectorUrl,
        params: {
            action: 'mgr/tailwindhelper/scanclasses',
            register: register,
            topic: topic
        },
        listeners: {
            success: {
                fn: function () {
                    console.fireEvent('complete');
                },
                scope: this
            }
        }
    });
    return true;
}

TailwindHelper.window.Console = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        items: [{
            itemId: 'header',
            cls: 'modx-console-text',
            html: _('console_running'),
            border: false
        }, {
            xtype: 'panel',
            itemId: 'body',
            cls: 'x-panel-bwrap modx-console-text'
        }, {
            cls: "treehillstudio_about",
            html: '<img width="146" height="40" src="' + TailwindHelper.config.assetsUrl + 'img/mgr/treehill-studio-small.png"' + ' srcset="' + TailwindHelper.config.assetsUrl + 'img/mgr/treehill-studio-small@2x.png 2x" alt="Treehill Studio">',
            listeners: {
                afterrender: function (component) {
                    component.getEl().select('img').on('click', function () {
                        var msg = '<span style="display: inline-block; text-align: center"><img src="' + TailwindHelper.config.assetsUrl + 'img/mgr/treehill-studio.png" srcset="' + TailwindHelper.config.assetsUrl + 'img/mgr/treehill-studio@2x.png 2x" alt="Treehill Studio"><br>' +
                            '&copy; 2021 by <a href="https://treehillstudio.com" target="_blank">treehillstudio.com</a></span>';
                        Ext.Msg.show({
                            title: _('tailwindhelper') + ' ' + TailwindHelper.config.version,
                            msg: msg,
                            buttons: Ext.Msg.OK,
                            cls: 'treehillstudio_window',
                            width: 358
                        });
                    });
                }
            }
        }]
    });
    TailwindHelper.window.Console.superclass.constructor.call(this, config);
    this.config = config;
    this.addEvents({
        'shutdown': true,
        'complete': true
    });
    this.on('show', this.init, this);
    this.on('hide', function () {
        if (this.provider && this.provider.disconnect) {
            try {
                this.provider.disconnect();
            } catch (e) {
            }
        }
        this.fireEvent('shutdown');
        this.destroy();
    });
    this.on('complete', this.onComplete, this);
}
Ext.extend(TailwindHelper.window.Console, MODx.Console);
Ext.reg('tailwindhelper-window-console', TailwindHelper.window.Console);
