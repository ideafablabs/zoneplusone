var $ = jQuery.noConflict();

$(document).ready( function(){

	// 
	$(".add-safety").click(function(e) { 
		e.preventDefault();
		$(e.target).addClass('.add-button');
		$(e.target).removeClass('.add-safety');

	}

	// 
	$(".add-token").click(function(e) {
		e.preventDefault();
		var target = e.target;
		
		// Get the relevant data.
		var uid = jQuery(this).data( 'uid' );        
		
		// Bundle the package.
		package = {
			request : 'add_token',
			data : {
				uid : uid
			}
		}

		// Forsee outcomes.
		package.success = function(response) {
			
			// Actual success.
			if (response.success == true) {                
				var usermessage = '<p class="ajax-success">'+response.message+'</p>';                
					
				/// Gotta change this if we change the code. Find a new way!
				var new_token = '<li>'+response.token_id+' <a class="remove-token icon" data-tid="'+response.token_id+'">x</a></li>';
				$('tr.user-'+uid+' .user-tokens ul').append(new_token);

				// $(target).parent('tr').children('.user-tokens ul')

			// Or failure.
			} else {                
				var usermessage = '<p class="ajax-error">'+response.message+'</p>';                
			}

			// Give some sort of affirmation...
			$(".ajax-message").html(usermessage);
			console.log(response.message);
		}

		// Send the package ==>
		ajaxRequest(package);
		
	});

	$(".remove-token").click(function(e) {
		e.preventDefault();
		var target = e.target;        
		
		// Get the relevant data.
		var tid = jQuery(this).data( 'tid' );
		
		// Bundle the package.
		package = {
			request : 'remove_token',
			data : {
					tid : tid
			}
		}

		// Forsee outcomes.
		package.success = function(response) {            

			// Actual success.
			if (response.success == true) {
				var usermessage = '<p class="ajax-success">'+response.message+'</p>';                
				$(target).parent('li').remove();                

			// Or failure.
			} else {                                
				var usermessage = '<p class="ajax-error">'+response.message+'</p>';                
			}

			// Give some sort of affirmation...
			$(".ajax-message").html(usermessage);
			console.log(response.message);
		}

		// Send the package ==>
		ajaxRequest(package);
		
	});

	function ajaxRequest(package) {

			$.ajax({
					url : iflzpo_ajax.ajaxurl,
					type : 'post',
					data : {
							action : 'async_controller',                
							security : iflzpo_ajax.check_nonce, 
							request : package.request,
							package : package.data
					},
					success : function( json ) {                
							// console.log(json);
							var response = JSON.parse(json);
							package.success(response);
					},
					error : function(jqXHR, textStatus, errorThrown) {
							console.log(jqXHR + " :: " + textStatus + " :: " + errorThrown);
					}
			});
	}
	/*
	This makes an instant search for the gallery member sign-in list
			@jordan
	*/
	
	// Setup to delay until user stops typing
	var delay = (function(){
			var timer = 0;
			return function(callback, ms){
					clearTimeout (timer);
					timer = setTimeout(callback, ms);
			};
	})();

	//we want this function to fire whenever the user types in the search-box
	$(".member_select_search #q").keyup(function () {
			
			delay(function(){

					$(".member_select_list").show();
			
					//first we create a variable for the value from the search-box
					var searchTerm = $(".member_select_search #q").val();

					//then a variable for the list-items (to keep things clean)
					var listItem = $('.member_select_list').children('tr');
					
					//extends the default :contains functionality to be case insensitive
					//if you want case sensitive search, just remove this next chunk
					$.extend($.expr[':'], {
						'containsi': function(elem, i, match, array)
						{
							return (elem.textContent || elem.innerText || '').toLowerCase()
							.indexOf((match[3] || "").toLowerCase()) >= 0;
						}
					});//end of case insensitive chunk


					//this part is optional
					//here we are replacing the spaces with another :contains
					//what this does is to make the search less exact by searching all words and not full strings
					var searchSplit = searchTerm.replace(/ /g, "'):containsi('")
					
					
					//here is the meat. We are searching the list based on the search terms
					$(".member_select_list tr").not(":containsi('" + searchSplit + "')").each(function(e)   {

								//add a "hidden" class that will remove the item from the list
								$(this).addClass('hidden');

					});
					
					//this does the opposite -- brings items back into view
					$(".member_select_list tr:containsi('" + searchSplit + "')").each(function(e) {

								//remove the hidden class (reintroduce the item to the list)
								$(this).removeClass('hidden');

					});

					// SORT
					var attendees = $('.member_select_list'),
					attendeesli = attendees.children('tr');

					attendeesli.sort(function(a,b){
							var an = a.getAttribute('data-sort').toLowerCase(),
									bn = b.getAttribute('data-sort').toLowerCase();

							if(an > bn) {
									return 1;
							}
							if(an < bn) {
									return -1;
							}
							return 0;
					});


			}, 500 );
	}); 

	// Auto focus on the search box when we load.
	setTimeout(function(){
			$(".member_select_search #q").focus();          
	},0);

	if ($(".nfc_button").length) {
			reader_id = $(".nfc_button").attr('data-reader_id');        
			setTimeout(ajax_get_token_id_from_reader(reader_id),3000);
	}

	// Clear search / hide attendee list.
	$('.clear-search').on('click', function(e) { 
			// document.getElementById('q').value = '';
			$(".member_select_list tr").show(); //Debug: show all the members. 
	});

});

function ajax_get_token_id_from_reader(reader_id) {
						
		console.log("Getting Token from reader "+reader_id);                
		//TODO Add loading graphic 
		
		$.ajax({
				url : iflpm_ajax.ajax_url,            
				type : 'get',
				data : {
						action : 'iflpm_get_token_from_reader',
						reader_id : reader_id
				},
				
				// security : iflpm_ajax.check_nonce,
				success : function( response ) {
						console.log("Success!");
						console.log(response);
						$('.token_id').html(response);            
				}
		});
						
}

function ajax_associate_medallion_with_user(reader_id,user_id) {
						
		console.log("Associating Token with user "+user_id+" with reader: ");                
		//TODO Add loading graphic 
		
		$.ajax({
				url : iflpm_ajax.ajax_url,            
				type : 'get',
				data : {
						action : 'iflpm_associate_user_with_token_from_reader',
						reader_id : reader_id,
						user_id : user_id
				},
				
				// security : iflpm_ajax.check_nonce,
				success : function( response ) {
						console.log("Success!");
						console.log(response);
						$('.token-response').html(response);
				}
		});
						
}