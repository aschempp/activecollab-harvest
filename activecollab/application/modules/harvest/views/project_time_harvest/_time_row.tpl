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
  {if $timerecord->getBillableStatus() == BILLABLE_STATUS_BILLABLE}
    <td class="billed">{lang}Billable{/lang}</td>
  {elseif $timerecord->getBillableStatus() == BILLABLE_STATUS_PENDING_PAYMENT}
    <td class="billed">{lang}Pending{/lang}</td>
  {elseif $timerecord->getBillableStatus() == BILLABLE_STATUS_BILLED}
    <td class="billed">{lang}Billed{/lang}</td>
  {else}
    <td class="billed details">--</td>
  {/if}  <td class="checkbox">
  {if $can_manage}
    <input type="checkbox" name="time_record_ids[]" value="{$timerecord->getId()}" class="auto slave_checkbox input_checkbox" />
  {/if}
  </td>
</tr>