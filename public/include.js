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
      var body = document.getElementById(id_btn.substr(0, id_btn.length-5));
      body.appendChild(div);
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
  this.toogle_password = function(e){
    if(e.checked){
      document.getElementById('frm_account_current_password').type = "text";
      document.getElementById('frm_account_new_password').type = "text";
      document.getElementById('frm_account_new_password_again').type = "text";
    }else{
      document.getElementById('frm_account_current_password').type = "password";
      document.getElementById('frm_account_new_password').type = "password";
      document.getElementById('frm_account_new_password_again').type = "password";
    }
  }
  this.toogle_signin = function(e){
    if(e.checked){
      document.getElementById('frm_account_password').type = "text";
    }else{
      document.getElementById('frm_account_password').type = "password";
    }
  }
}
PluginWfAccount2 = new plugin_wf_account2();
