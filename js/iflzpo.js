var $ = jQuery.noConflict();

$(document).ready( function(){

	// 
	// $(".add-safety").click(function(e) { 
	// 	e.preventDefault();
	// 	$(e.target).addClass('.add-button');
	// 	$(e.target).removeClass('.add-safety');

	// });

	// 
	$(".member_select_list").on('click','.add-token', function(e) {
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

				/// Gotta change this if we change the code. Find a new way!
				var new_token = '<li>'+response.token_id+' <a class="remove-token icon" data-tid="'+response.token_id+'">x</a></li>';
				$('tr.user-'+uid+' .user-tokens ul').append(new_token);

				// $(target).parent('tr').children('.user-tokens ul')

			// Or failure.
			} else {                
			}

			// Give some sort of affirmation...
			$(".iflzpo-wrap").prepend(build_wp_notice(response).fadeIn());		
			console.log(response.message);
		}

		// Send the package ==>
		iflzpo_ajax_request(package);
		
	});

	$(".member_select_list").on('click','.remove-token', function(e) {
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
				$(target).parent('li').remove();                

			// Or failure.
			} else {                                
				
			}

			// Give some sort of affirmation...			
			$(".iflzpo-wrap").prepend(build_wp_notice(response).fadeIn());		
			console.log(response.message);


		}

		// Send the package ==>
		iflzpo_ajax_request(package);
		
	});

	// Activate filterable tables/lists
	$("#q").on( 'keyup',
		{
			target:'.filterable',
			children:'.filter-item'			
		}
		,filter); 

	// Auto focus on the search box when we load.
	setTimeout(function(){
		$(".member_select_search #q").focus();          
	},50);

	

});

/*
 	This makes an instant search filter.	
 */
if (typeof filter === "undefined") { 

	function filter(event) {

		delay(function(){
			
			$(event.data.target).show();
			
			// First we create a variable for the value from the search-box.
			var searchTerm = $(event.target).val();

			// Then a variable for the list-items (to keep things clean).
			var listItem = $(event.data.target).children(event.data.children);
			
			// Extends the default :contains functionality to be case insensitive if you want case sensitive search, just remove this next chunk
			$.extend($.expr[':'], {
				'containsi': function(elem, i, match, array) {
					return (elem.textContent || elem.innerText || '').toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
				}
			}); // End of case insensitive chunk.

			// Optional
			// Here we are replacing the spaces with another :contains
			// What this does is to make the search less exact by searching all words and not full strings
			var searchSplit = searchTerm.replace(/ /g, "'):containsi('")
			
			// Here is the meat. We are searching the list based on the search terms
			$(event.data.target+" "+event.data.children).not(":containsi('" + searchSplit + "')").each(function(e)   {				  
				  $(this).addClass('hidden');
			});
			
			// This does the opposite -- brings items back into view
			$(event.data.target+" "+event.data.children+":containsi('" + searchSplit + "')").each(function(e) {				  
				$(this).removeClass('hidden');
			});
		
		},500);
	}
}


// Setup to delay until user stops typing
var delay = (function(){
	var timer = 0;
	return function(callback, ms){
		clearTimeout (timer);
		timer = setTimeout(callback, ms);
	};
})();

/// SORT saving this just in case.
// var attendees = $('.member_select_list'), 
// 	attendeesli = attendees.children('tr');

// attendeesli.sort(function(a,b){
// 	var an = a.getAttribute('data-sort').toLowerCase(),
// 		bn = b.getAttribute('data-sort').toLowerCase();

// 	if(an > bn) {
// 		return 1;
// 	}
// 	if(an < bn) {
// 		return -1;
// 	}
// 	return 0;
// });


// Build WP Notice box for admin AJAX actions.
function build_wp_notice(response) {

	// var testresponse = {
	// 	message:'test message',
	// 	notice : {
	// 		level:'',
	// 		display:true,
	// 		dismissible:true			
	// 	}
	// }

	if (!response.notice.display) return false;

	var notice = $('<div class="notice hidden"></div>').addClass(response.notice.level); 
	
	var noticeMessage = document.createElement('p'); 
	noticeMessage.innerText =response.message ;
	
	notice.append(noticeMessage);
	
	if (response.notice.dismissible) {		
		var dismissbutton = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
		notice.append(dismissbutton);
		notice.addClass('is-dismissible');

		dismissbutton.on('click',function(e){			
			$(e.target).parent('.notice').fadeOut();
		})
	}

	return notice;
}


function iflzpo_ajax_request(package) {

	$.ajax({
		url : iflzpo_ajax.ajaxurl,
		type : 'post',
		data : {
			action : 'iflzpo_async_controller',                
			security : iflzpo_ajax.check_nonce, 
			request : package.request,
			package : package.data
		},
		success : function( json ) {                
			console.log(json);
			var response = JSON.parse(json);
			package.success(response);
		},
		error : function(jqXHR, textStatus, errorThrown) {
			console.log(jqXHR + " :: " + textStatus + " :: " + errorThrown);
		}
	});
}