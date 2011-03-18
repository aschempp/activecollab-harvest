App.harvest = {
  controllers : {},
  models      : {}
};

var months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

App.harvest.controllers.project =
{
	
	sync : function()
	{
		var progress_div = $('#harvest_sync_progress');
		
		var delimiter = App.data.path_info_through_query_string ? '&' : '?';
		
		var perform_sync = function(start)
		{
			var progress_content = $('#progress_content');
			var month = new Date(start*1000);
			progress_content.html('<p><img src="' + App.data.assets_url + '/images/indicator.gif" alt="" /> ' + App.lang('Syncing with Harvest') + ': ' + months[month.getMonth()] + ' ' + month.getFullYear() + '</p>');
			$.ajax(
			{
				url: App.data.harvest_sync_url + delimiter + 'start=' + start + '&async=1',
				type: 'GET',
				success : function(response)
				{
					// update finished
					if (response == 'finished')
					{
						progress_content.html('<p><img src="' + App.data.assets_url + '/images/ok_indicator.gif" alt="" /> '+ App.lang('Harvest sync successfully') + '</p>');
						window.setTimeout(function()
						{
							$(window.location).attr('href', App.data.success_url);
						}, 3000);
					}
					
					// update error
					else if(isNaN(response))
					{
						progress_content.html(response); // if not success, reponse is an error message
					}
					
					// sync next month
					else
					{
						perform_sync(response);
					}
				}
			});
		}

		progress_div.prepend('<p>Please wait while we sync your Harvest Timesheet with this activeCollab project.</p>');
		perform_sync(App.data.harvest_start);
	
/*
		if (App.data.repository_uptodate == 1)
		{
			progress_div.html('<p><img src="' + App.data.assets_url + '/images/ok_indicator.gif" alt="" /> '+ App.lang('Repository is already up-to-date') + '</p>');
		}
		else
		{
			total_commits = App.data.repository_head_revision - App.data.repository_last_revision;
		
			if (total_commits > 0)
			{
				progress_div.prepend('<p>There are new commits, please wait until the repository gets updated to revision #'+App.data.repository_head_revision+'</p>');
				get_logs();
			}
			else
			{
				progress_div.prepend('<p>' + App.lang('Error getting new commits') + ':</p>');
			}
		}
*/
	}
};
