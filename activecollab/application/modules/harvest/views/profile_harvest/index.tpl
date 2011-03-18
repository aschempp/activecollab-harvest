{title}Harvest Integration{/title}

  <div class="section_container">
    {form action=$harvest_admin_url method=POST}
      <div class="col_wide">
        {wrap field=domain}
          {label for=domain required=yes}Harvest Domain{/label}
          {text_field id='domain' name=harvest[domain] value=$harvest_data.domain}
          <p class="details">The Domain you use for login, eg. "http://example.harvestapp.com/".</p>         
        {/wrap}
      </div>
      
      <div class="clear"></div>
      
      <div class="col_wide">
        {wrap field=auth_token}
          {label for=auth_token required=yes}Harvest Username{/label}
          {text_field id='user' name=harvest[user] value=$harvest_data.user}
          <p class="details">Your username (e-mail address)</p>
        {/wrap}
        {wrap field=auth_token}
          {label for=auth_token required=yes}Harvest Password{/label}
          {password_field id='pass' name=harvest[pass]}
          <p class="details">Your password</p>         
        {/wrap}
      </div>
      
	{wrap_buttons}
		{submit}Submit{/submit}
	{/wrap_buttons}
{/form}
