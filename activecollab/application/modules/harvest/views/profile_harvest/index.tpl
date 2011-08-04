{title}Harvest Integration{/title}

  <div class="section_container">
    {form action=$harvest_admin_url method=POST}
      <div class="col_wide">
        {wrap field=user}
          {label for=user required=yes}Harvest Username{/label}
          {text_field id='user' name=harvest[user] value=$harvest_data.user}
          <p class="details">Your username (e-mail address)</p>
        {/wrap}
        {wrap field=pass}
          {label for=pass required=yes}Harvest Password{/label}
          {password_field id='pass' name=harvest[pass]}
          <p class="details">Your password</p>         
        {/wrap}
      </div>
      
	{wrap_buttons}
		{submit}Submit{/submit}
	{/wrap_buttons}
{/form}
