(function ($) {
	// Here "$" is a jQuery reference
$(document).ready(function() {
	$('.tabbed').on('click','.sltabhead',function(e){dothetab(e);});
	$('.tabbed').each(function(index,elem){elem=$(elem).find('.sltabhead').get(0);dothetab($(elem));});
});

function dothetab(e){
	if (e.target==undefined) {
		ad=e;
	} else { ad=e.target; }
	$(ad).addClass('slactive').removeClass('slinactive');
	var index=$(ad).prevAll('.sltabhead').removeClass('slactive').addClass('slinactive').length;
	$(ad).closest('.tabbed').find('.sltab').each(function(i,elem){if (i!=index) {$(elem).hide();} else{$(elem).show();}});
	$(ad).nextAll('.sltabhead').removeClass('slactive').addClass('slinactive');

    }

/* Data dep, grey out fields which depend on other settings*/
$(document).ready(function() {
	dep_update();
	$('input[data-check]').click(function(){
		dep_update();			
	});
});

	function dep_update(){
		$('input[data-dep],textarea[data-dep],select[data-dep]').each(function(i,e){
    	var ccheck = $($(e).attr('data-dep')).is(":checked");

    	if (!ccheck){
    		$(e).attr('readonly','readonly');
    	}else{
    		$(e).removeAttr('readonly');
    	}

		});
	}

})(jQuery);

