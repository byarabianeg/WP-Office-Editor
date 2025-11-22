/**
 * Placeholder ckeditor.js
 * This small file allows the plugin to load while you build the real CKEditor.
 * Replace this file with the real `build/ckeditor.js` produced by webpack/npm.
 */
(function(global){
  console.warn("CKEditor placeholder loaded. Replace build/ckeditor.js with real CKEditor build for full functionality.");
  var Placeholder = {
    create: function(holder, config){
      return new Promise(function(resolve,reject){
        // create a very small contentEditable area so the UI doesn't completely break.
        var fakeEditor = {
          getData: function(){ return holder.innerHTML || ''; },
          setData: function(html){ holder.innerHTML = html; },
          ui: { view: { toolbar: { element: document.createElement('div') }, editable: { element: holder } } }
        };
        // add a tiny toolbar message
        fakeEditor.ui.view.toolbar.element.innerHTML = '<div style="padding:6px;font-size:13px;color:#333">Placeholder CKEditor — build missing</div>';
        resolve(fakeEditor);
      });
    }
  };
  global.DecoupledEditor = Placeholder;
})(window);
