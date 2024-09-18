
function showMarketingPopup(){
	if($('.dyn_mp_jqm').length)
	{
		var allDelays = [];
		$('.dyn_mp_jqm').each(function(i, el) {
			var jqmBlock = $(el),
				delay = 0;

			if(!jqmBlock.hasClass('initied')) // first load
			{
				jqmBlock.addClass('initied');
				
				if(jqmBlock.data('param-delay'))
					delay = jqmBlock.data('param-delay')*1000;

				while(allDelays.indexOf(delay) !== -1) {
					delay += 1000;
				}

				allDelays.push(delay);

				if(typeof localStorage !== 'undefined')
				{
					var dataLS = localStorage.getItem(jqmBlock.data('ls')),
						ls = '';
					try{
						ls = JSON.parse(dataLS);
					}
					catch(e){
						ls = dataLS
					}
					if(!ls || (ls && (ls.TIMESTAMP < Date.now() && ls.TIMESTAMP)))
					{
						/*if (timeoutID) {
							clearTimeout(timeoutID);
						}*/
						timeoutID = setTimeout(function(){
							console.log(jqmBlock, delay)
							jqmBlock.click();
						}, delay);
					}
				}
				else
				{
					var ls = $.cookie(jqmBlock.data('ls'));
					if(!ls)
					{
						/*if (timeoutID) {
							clearTimeout(timeoutID);
						}*/
						timeoutID = setTimeout(function(){
							jqmBlock.click();
						}, delay);
					}
				}

			}
			else // ajax popup
			{
				
			}

		});
	}
}




$(document).ready(function(){

	showMarketingPopup();
	
	if(typeof window.frameCacheVars !== "undefined"){
		BX.addCustomEvent("onFrameDataReceived", function (json){
			showMarketingPopup();
		});
	}

	$(document).on('click', '.dyn_mp_jqm_frame .popup-text-info__btn .btn', function() {
		var closeBtn = $(this).closest('.dyn_mp_jqm_frame').find('.jqmClose');

		if(closeBtn.length) {
			closeBtn.trigger('click');
		}
	});
})
