{title}Harvest Integration{/title}

  <div class="section_container">
    {form action=$harvest_admin_url method=POST}
      <div class="col_wide">
        {wrap field=project}
          {label for=project}Harvest Project{/label}
   	      <select name="config[project]" class="auto">
	        <option value="">{lang}Select project ...{/lang}</option>
	        {foreach from=$projects item=project key=company}
            <optgroup label="{$company}">
	          {foreach from=$project item=name key=id}
              <option value="{$id}"{if $config.project == $id} selected="selected"{/if}>{$name}</option>
	          {/foreach}
	        </optgroup>
	        {/foreach}
	      </select>
          <p class="details">Select a Harvest projects for this activeCollab project.</p>         
        {/wrap}
      </div>
      
	{wrap_buttons}
		{submit}Submit{/submit}
	{/wrap_buttons}
{/form}
