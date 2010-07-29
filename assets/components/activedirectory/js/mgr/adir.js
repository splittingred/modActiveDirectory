var ADir = function(config) {
    config = config || {};
    ADir.superclass.constructor.call(this,config);
};
Ext.extend(ADir,Ext.Component,{
    page:{},window:{},grid:{},tree:{},panel:{},combo:{},config: {},view: {}
});
Ext.reg('activedirectory',ADir);

var ADir = new ADir();