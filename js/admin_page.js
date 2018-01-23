jQuery(document).ready(function($) {
	//
	// Initiate tabbed content
	$(function() {  
		$('#tabs').tabs();
	});
	 
	window.send_to_editor = function(html) {  // Send WP media uploader response
		url = $(html).attr('href');
		$('#csv_file').val(url);
		tb_remove();
		blur_file_upload_field();  // Function to blur file upload field (gets column count from .csv file)
	}
	
	// ******* Begin 'Select Table' dropdown change function ******* //
	$('#table_select').change(function() {  // Get column count and load table
		
		// Begin ajax loading image
		$('#table_preview').html('<img src="'+wp_csv_to_db_pass_js_vars.ajax_image+'" />');
		
		// Clear 'disable auto_inc' checkbox
		$('#remove_autoinc_column').prop('checked', false);
		
		// Get new table name from dropdown
		sel_val = $('#table_select').val();
		
		// Setup ajax variable
		var data = {
			action: 'wp_csv_to_db_get_columns',
			sel_val: sel_val
			//disable_autoinc: disable_autoinc
		};
		
		// Run ajax request
		$.post(wp_csv_to_db_pass_js_vars.ajaxurl, data, function(response) {
			
			// Populate Table Preview HTML from response
			$('#table_preview').html(response.content);
			
			// Determine if column has an auto_inc value.. and enable/disable the checkbox accordingly
			if(response.enable_auto_inc_option == 'true') {
				$("#remove_autoinc_column").prop('disabled', false);
			}
			if(response.enable_auto_inc_option == 'false') {
				$("#remove_autoinc_column").prop('disabled', true);
			}
			
			
			// Get column count from ajax table and populate hidden div for form submission comparison
			var colCount = 0;
			$('#ajax_table tr:nth-child(1) td').each(function () {  // Array of table td elements
				if ($(this).attr('colspan')) {  // If the td element contains a 'colspan' attribute
					colCount += +$(this).attr('colspan');  // Count the 'colspan' attributes
				} else {
					colCount++;  // Else count single columns
				}
			});
			
			// Populate #num_cols hidden input with number of columns
			$('#num_cols').val(colCount);  
		});
	});
	// ******* End 'Select Table' dropdown change function ******* //
	
	
	
	// ******* Begin 'Reload Table Preview' button AND 'Disable auto-increment Column' checkbox click function ******* //
	$('#repop_table_ajax, #remove_autoinc_column').click(function() {  // Reload Table
	
		// Begin ajax loading image
		$('#table_preview').html('<img src="'+wp_csv_to_db_pass_js_vars.ajax_image+'" />');
	
		// Get value of disable auto-increment column checkbox
		if($('#remove_autoinc_column').is(':checked')){
			disable_autoinc = 'true';
		}else{
			disable_autoinc = 'false';
		}
		// Get new table name from dropdown
		sel_val = $('#table_select').val();
		
		// Setup ajax variable
		var data = {
			action: 'wp_csv_to_db_get_columns',
			sel_val: sel_val,
			disable_autoinc: disable_autoinc
		};
		
		// Run ajax request
		$.post(wp_csv_to_db_pass_js_vars.ajaxurl, data, function(response) {
			
			// Populate Table Preview HTML from response
			$('#table_preview').html(response.content);
			
			// Determine if column has an auto_inc value.. and enable/disable the checkbox accordingly
			if(response.enable_auto_inc_option == 'true') {
				$("#remove_autoinc_column").prop('disabled', false);
			}
			if(response.enable_auto_inc_option == 'false') {
				$("#remove_autoinc_column").prop('disabled', true);
			}
			
			// Get column count from ajax table and populate hidden div for form submission comparison
			var colCount = 0;
			$('#ajax_table tr:nth-child(1) td').each(function () {  // Array of table td elements
				if ($(this).attr('colspan')) {  // If the td element contains a 'colspan' attribute
					colCount += +$(this).attr('colspan');  // Count the 'colspan' attributes
				} else {
					colCount++;  // Else count single columns
				}
			});
			
			// Populate #num_cols hidden input with number of columns
			$('#num_cols').val(colCount);
			
			// Re-populate column count value
			remove_auto_col_val = $('#column_count').html('<strong>'+colCount+'</strong>');
		});
	});
	// ******* End 'Reload Table Preview' button AND 'Disable auto-increment Column' checkbox click function ******* //
	$('#neon_member_load').click(function() {  // Reload Table

		// Begin ajax loading image
		$('#date_of_last_load').html('<img src="'+nensa_admin_pass_js_vars.ajax_image+'" />');
		$('#skier_load_counts').text('');
		$('#season_load_counts').text('');


		// Get value of disable auto-increment column checkbox
		if($('#reload_all_from_neon').is(':checked')){
			reload = 'true';
		}else{
			reload = 'false';
		}

		// Setup ajax variable
		var data = {
			action: 'fetch_member_data',
			reload: reload
		};

		// Run ajax request
		$.post(nensa_admin_pass_js_vars.ajaxurl, data, function(response) {
			
			// Populate Table Preview HTML from response
			$('#date_of_last_load').text('Date last loaded: '+response.date_of_last_load);
			$('#skier_load_counts').text(response.skier_load_count+" NEON skiers were processed for the member_skier table, "+response.skier_update_count+" skiers were updated and "+response.skier_new_count+" skiers were added.");
			$('#season_load_counts').text(response.season_load_count+" NEON skiers were processed for the member_season table, "+response.season_update_count+" skiers were updated and "+response.season_new_count+" skiers were added.");

		});

	});


	$('#import_season').click(function() { 
		load_event_on_season_click_or_change ()
	});

	$('#import_season').change(function() { 
		load_event_on_season_click_or_change ()
	});

	function load_event_on_season_click_or_change () {
		import_season = $( "#import_season" ).val();
		$('#load_results_event_select option:eq(0)').remove();
		$('#load_results_event_select option:gt(0)').remove();
		$('#load_results_race_select option:eq(0)').remove();
		$('#load_results_race_select option:gt(0)').remove();
		// Setup ajax variable
		var data = {
			action: 'season_select',
			season: import_season
		};

		// Run ajax request
		$.post(nensa_admin_pass_js_vars.ajaxurl, data, function(response) {
			var obj = response.season.split(";");
			var event_list = $.makeArray( obj );
			$.each(event_list, function (index, value) {
			    $('#load_results_event_select').append($('<option/>', { 
			        value: value,
			        text : value 
			    }));
			}); 

		});
	}

	$('#load_results_event_select').change(function() { 
		event_name = $( "#load_results_event_select" ).val();

		// Setup ajax variable
		var data = {
			action: 'race_select',
			event_name: event_name
		};

		// Run ajax request
		$.post(nensa_admin_pass_js_vars.ajaxurl, data, function(response) {
			var obj = response.event_list.split(";");
			var event_list = $.makeArray( obj );
			$('#load_results_race_select option:eq(0)').remove();
			$('#load_results_race_select option:gt(0)').remove();
			$.each(event_list, function (index, value) {
			    $('#load_results_race_select').append($('<option/>', { 
			        value: value,
			        text : value 
			    }));
			}); 

		});
	});

	/*
	$('#import_race_results').click(function() { 
		results_file = $( "#results_file").val();
		race_name = $( "#load_results_race_select" ).val();

		var $imgForm    = $('.image-form');
    var $imgFile    = $imgForm.find('.image-file');

		 var formData = new FormData();

    formData.append('action', 'upload-attachment');
    formData.append('async-upload', $imgFile[0].files[0]);
    formData.append('name', $imgFile[0].files[0].name);
    var $test = $imgFile[0].files[0].name;
    async_upload = $imgFile;
    //formData.append('_wpnonce', su_config.nonce);
		//upload();
				// Setup ajax variable
		var data = {
			action: 'load_race_data',
			async_upload: async_upload,
			//,,,async_upload: $imgFile[0].files[0],
			results_file: results_file,
			race_name: race_name
		};

		// Run ajax request
		$.post(nensa_admin_pass_js_vars.ajaxurl, data, function(response) {
			$('#import_race_results_status').text('Hi there');
		});
	});

	function upload(){
	  var formData = new FormData();
	  formData.append("action", "upload-attachment");
		
	  var fileInputElement = document.getElementById("results_file");
	  formData.append("async-upload", fileInputElement.files[0]);
	  formData.append("name", fileInputElement.files[0].name);
	  	
	  //also available on page from _wpPluploadSettings.defaults.multipart_params._wpnonce
	  //<?php $my_nonce = wp_create_nonce('media-form'); ?>
	  //formData.append("_wpnonce", "<?php echo $my_nonce; ?>");
	  var xhr = new XMLHttpRequest();
	  xhr.onreadystatechange=function(){
	    if (xhr.readyState==4 && xhr.status==200){
	      console.log(xhr.responseText);
	    }
	  }
	  xhr.open("POST","/wp-admin/async-upload.php",true);
	  xhr.send(formData);
	}
	*/

});
