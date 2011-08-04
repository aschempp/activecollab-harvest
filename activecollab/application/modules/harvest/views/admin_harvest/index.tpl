{title}Harvest Integration{/title}
	
  <div class="section_container">
  
    {form action=$harvest_admin_url method=POST}
    
      <h2 class="section_name"><span class="section_name_span">{lang}Harvest Account{/lang}</span></h2>
      <p>This activeCollab installation will be linked to one Harvest account.</p>
    
      <div class="col_wide">
        {wrap field=account}
          {label for=domain required=yes}Harvest Account{/label}
          {text_field id='account' name=harvest[account] value=$harvest_data.account}
          <p class="details">The subdomain you use for login, eg. http://<strong>EXAMPLE</strong>.harvestapp.com/.</p>
        {/wrap}
      </div>
      
      <div class="clear"></div>
      
      <h2 class="section_name"><span class="section_name_span">{lang}Admin Account{/lang}</span></h2>
      <p>For tasks like background synching and adding new projects, we need an account with as much access rights as possible.<br />Please enter your admin account credentials here. Submitting time entries to Harvest will be done with the user's credentials.</p>
      
      <div class="col">
        {wrap field=user}
          {label for=user required=yes}Harvest Admin User{/label}
          {text_field id='user' name=harvest[user] value=$harvest_data.user}
          <p class="details">Admin username (e-mail address).</p>
        {/wrap}
      </div>
      <div class="col">
        {wrap field=pass}
          {label for=pass required=yes}Harvest Admin Password{/label}
          {password_field id='pass' name=harvest[pass]}
          <p class="details">Admin password.</p>         
        {/wrap}
      </div>
      
      <div class="clear"></div>
      
      <h2 class="section_name"><span class="section_name_span">{lang}Harvest Integration{/lang}</span></h2>
      <p>Please setup the features you want to use.</p>
      
      <div class="col">
        {wrap field=create_project}
          {label for=create_project required=yes}Create & Update Projects{/label}
          {yes_no id=create_project name=harvest[create_project] value=$harvest_data.create_project}
          <p class="details">Create a new Harvest project when a project is added to activeCollab. Archive & Unarchive projects in Harvest when changing project status.</p>
        {/wrap}
        {wrap field=sync_interval}
          {label for=sync_interval required=yes}Sync Projects{/label}
		  {select_harvest_sync_interval name=harvest[sync_interval] id=sync_interval data=$sync_intervals selected=$harvest_data.sync_interval}
          <p class="details">Automatically sync Harvest with activeCollab. Will download new time records from Harvest to activeCollab and update billing status.</p>
        {/wrap}
      </div>
      <div class="col">
        {wrap field=create_client}
          {label for=create_client required=yes}Create Clients{/label}
          {yes_no id=create_client name=harvest[create_client] value=$harvest_data.create_client}
          <p class="details">Add client to Harvest if it is missing when adding a new project.</p>
        {/wrap}
      </div>
      
	{wrap_buttons}
		{submit}Submit{/submit}
	{/wrap_buttons}
{/form}
