<tr class="time_record {cycle values='odd,even'} {if $timerecord->isBilled()}billed{/if}">
  <td class="date">{$timerecord->getRecordDate()|date:0}</td>
  <td class="user">{user_link user=$timerecord->getUser()}</td>
  <td class="hours"><b>{$timerecord->getValue()}</b></td>
  <td class="desc">
  {if instance_of($timerecord->getParent(), 'ProjectObject')}
    {object_link object=$timerecord->getParent()} 
    {if $timerecord->getBody()}
      &mdash; {$timerecord->getBody()}
    {/if}
  {else}
    {$timerecord->getBody()}
  {/if}
  </td>
  <td class="billable">
  {if $timerecord->isBillable()}
    {lang}Yes{/lang}
  {else}
    {lang}No{/lang}
  {/if}
  </td>  
  <td class="checkbox">
  {if $can_manage}
    <input type="checkbox" name="time_record_ids[]" value="{$timerecord->getId()}" class="auto slave_checkbox input_checkbox" />
  {/if}
  </td>
</tr>