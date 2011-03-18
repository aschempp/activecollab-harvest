{title}Syncing with Harvest{/title}

{add_bread_crumb}Harvest Sync{/add_bread_crumb}

<div class="blockLabels">
  <div id="harvest_sync_progress">
    <div id="progress_content"></div>
  </div>
</div>
<script type="text/javascript">
	App.data.harvest_sync_url = '{$harvest_sync_url}';
	App.data.harvest_start = {$harvest_start};
	App.data.success_url = '{$success_url}';
	App.harvest.controllers.project.sync();
</script>