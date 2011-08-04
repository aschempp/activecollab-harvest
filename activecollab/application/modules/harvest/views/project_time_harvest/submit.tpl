{title}Submit to Harvest{/title}

<div id="timerecords">
  {if $pagination->getLastPage() > 1}
  <p class="pagination top">
  {if instance_of($active_object, 'ProjectObject')}
    <span class="inner_pagination">{lang}Page{/lang}: {pagination pager=$pagination}{assemble route=project_time page='-PAGE-' project_id=$active_project->getId() for=$active_object->getId()}{/pagination}</span>
  {else}
    <span class="inner_pagination">{lang}Page{/lang}: {pagination pager=$pagination}{assemble route=project_time page='-PAGE-' project_id=$active_project->getId()}{/pagination}</span>
  {/if}
  </p>
  <div class="clear"></div>
  {/if}
  
  {if !is_foreachable($tasks)}
  <div id="no_records">
    <p class="empty_page">{lang}No projects/tasks found on Harvest or request failed. Check your access credentials.{/lang}</p>
  </div>
  {else}

	  {form action=$submit_url method=post id=add_time_record_form show_errors=no}
	      <table class="common_table timerecords">
	        <thead>
	          <tr>
	            <th class="date">{lang}Date{/lang}</th>
	            <th class="user">{lang}Person{/lang}</th>
	            <th class="hours">{lang}Hours{/lang}</th>
	            <th class="desc">{lang}Summary{/lang}</th>
	            <th class="billable">{lang}Billable{/lang}</th>
	            <th class="checkbox"></th>
	          </tr>
	        </thead>
	        <tbody>
	        {if is_foreachable($timerecords)}
	          {foreach from=$timerecords item=timerecord}
	            {include_template name=_time_row controller=project_time_harvest module=harvest}
	          {/foreach}
	        {/if}
	      </tbody>
	    </table>
	    
	    {if $can_manage && is_foreachable($tasks)}
	    <div id="mass_edit">
	    {if $is_admin}
	      <select name="user" class="auto">
	        <option value="0">{lang}Detect Harvest user{/lang}</option>
            {foreach from=$users item=name key=id}
            <option value="{$id}">{lang}Submit to{/lang} {$name}</option>
            {/foreach}
	      </select>
	    {/if}
	      <select name="task" class="auto">
	        <option value="">{lang}Select project/task ...{/lang}</option>
	         {foreach from=$tasks item=task key=id}
	          <option value="{$id}">{$task}</option>
	        {/foreach}
	      </select>
	      <button class="simple" type="submit" class="auto">{lang}Post{/lang}</button>
	    </div>
	    {/if}
	  {/form}
  
	  <div class="clear"></div>
	  
	  {if ($pagination->getLastPage() > 1) && !$pagination->isLast()}
	    {if instance_of($active_object, 'ProjectObject')}
	      <p class="next_page"><a href="{assemble route=project_time page=$pagination->getNextPage() project_id=$active_project->getId() for=$active_object->getId()}">{lang}Next Page{/lang}</a></p>
	    {else}
	      <p class="next_page"><a href="{assemble route=project_time page=$pagination->getNextPage() project_id=$active_project->getId()}">{lang}Next Page{/lang}</a></p>
	    {/if}
	  {/if}
  {/if}
  
{if !is_foreachable($timerecords)}
  <script type="text/javascript">
    $('#mass_edit').hide();
  </script>
  
  <div id="no_records">
    <p class="empty_page">{lang}No time records here{/lang}. {if $can_add}{lang}Use the form above to create new ones{/lang}.{/if}</p>
  </div>
{/if}
</div>