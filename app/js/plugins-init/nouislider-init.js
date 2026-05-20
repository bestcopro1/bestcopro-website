(function($) {
    "use strict"
	var avancementInput = document.getElementById('avancement');
	var avancementInputValue = parseInt(avancementInput.value);
    //stepping and snapping the values
    var stepSlider = document.getElementById('slider-step');
    noUiSlider.create(stepSlider, {
        start: [avancementInputValue],
        step: 1,
        range: {
            'min': [0],
            'max': [100]
        }
    });

    var stepSliderValueElement = document.getElementById('slider-step-value');
    
    stepSlider.noUiSlider.on('update', function (values, handle) {
        stepSliderValueElement.innerHTML = parseInt(values[handle]);
        avancementInput.value = parseInt(values[handle]);
    });
    //stepping and snapping the values ^

})(jQuery);