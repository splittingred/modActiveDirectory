ADir.panel.Home = function(config) {
    config = config || {};
    Ext.apply(config,{
        border: false
        ,baseCls: 'modx-formpanel'
        ,items: [{
            html: '<h2>'+_('adir')+'</h2>'
            ,border: false
            ,cls: 'modx-page-header'
        },{
            xtype: 'modx-tabs'
            ,bodyStyle: 'padding: 10px'
            ,defaults: { border: false ,autoHeight: true }
            ,border: true
            ,activeItem: 0
            ,hideMode: 'offsets'
            ,items: [{
                title: _('adir.home')
                ,items: [{
                    html: '<p>'+_('adir.intro_msg')+'</p><br />'
                    ,border: false
                }]
            }]
        }]
    });
    ADir.panel.Home.superclass.constructor.call(this,config);
};
Ext.extend(ADir.panel.Home,MODx.Panel);
Ext.reg('adir-panel-home',ADir.panel.Home);
