function plugin_wf_account2(){
  this.sendmessage = function (plugin_module){
    $.ajax({url: "/"+plugin_module+"/sendmessage", success: function(result){
      PluginWfCallbackjson.call(result);
    }});
  }
  this.saveForm = function(id_btn, str, success){
    if(!document.getElementById(id_btn+'_warning')){
      var div = document.createElement('div');
      div.id = id_btn+'_warning';
      div.style.marginTop = '10px';
      div.style.marginLeft = '0px';
      div.style.textAlign = 'left';
      document.getElementById(id_btn).parentNode.appendChild(div);
    }else{
      div = document.getElementById(id_btn+'_warning');
    }
    if(success){
      if(str.length){
        div.className = 'alert alert-success';
        div.innerHTML = str;
      }else{
        div.style.display='none';
      }
    }else{
      div.className = 'alert alert-danger';
      div.innerHTML = str;
    }
  }
}
PluginWfAccount2 = new plugin_wf_account2();
