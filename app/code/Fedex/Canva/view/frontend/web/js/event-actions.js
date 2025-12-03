define(["jquery", "domReady"], function($, domReady){

    domReady(function () {
        if ($('.qty-stepper').length) {
            var qtyField = $('.qty-stepper').find('input.qty-field'),
                qtyValue = parseInt(qtyField.val()),
                minQty = qtyField.data('min-value'),
                maxQty = qtyField.data('max-value'),
                stepDownBtn = $('.step-down'),
                stepUpBtn = $('.step-up'),
                stepDownDisableFlag = false,
                stepUpDisableFlag = false;
            if(qtyValue === minQty) {
                stepDownBtn.prop('disabled', true).attr('aria-disabled','true');
                stepDownDisableFlag = true;
            }
            if(qtyValue === maxQty) {
                stepUpBtn.prop('disabled', true).attr('aria-disabled','true');
                stepUpDisableFlag = true;
            }
            $(document).on('click', '.btn-stepper', function() {
                qtyValue = parseInt(qtyField.val());
                if($(this).hasClass('step-down')) {
                    qtyValue--;
                    if(qtyValue === minQty) {
                        stepDownBtn.prop('disabled', true).attr('aria-disabled','true');
                        stepDownDisableFlag = true;
                    } else if(stepUpDisableFlag && (qtyValue < maxQty)) {
                        stepUpBtn.prop('disabled', false).attr('aria-disabled','false');
                        stepUpDisableFlag = false;
                    }
                } else {
                    qtyValue++;
                    if(qtyValue === maxQty) {
                        stepUpBtn.prop('disabled', true).attr('aria-disabled','true');
                        stepUpDisableFlag = true;
                    } else if(stepDownDisableFlag && (qtyValue > minQty)) {
                        stepDownBtn.prop('disabled', false).attr('aria-disabled','false');
                        stepDownDisableFlag = false;
                    }
                }

                    $('#qty-error-text').addClass('hide');

                qtyField.val(qtyValue).trigger('focusout');
            });
            $(document).on('input', 'input.qty-field', function() {
                qtyValue = parseInt(qtyField.val());
                if(qtyValue >= maxQty) {
                    stepUpBtn.prop('disabled', true).attr('aria-disabled','true');
                    stepUpDisableFlag = true;
                } else if(stepUpDisableFlag && (qtyValue < maxQty)) {
                    stepUpBtn.prop('disabled', false).attr('aria-disabled','false');
                    stepUpDisableFlag = false;
                }
                if (qtyValue <= minQty) {
                    stepDownBtn.prop('disabled', true).attr('aria-disabled','true');
                    stepDownDisableFlag = true;
                } else if(stepDownDisableFlag && (qtyValue > minQty)) {
                    stepDownBtn.prop('disabled', false).attr('aria-disabled','false');
                    stepDownDisableFlag = false;
                }
            });
        }

        var slickSelectControls = document.querySelectorAll('.slick-select');
        if (slickSelectControls.length) {
            slickSelectControls.forEach(slickSelectControl => {
                var slickSelectedOption = $(slickSelectControl).find('li[aria-selected="true"]'),
                    slickSelectedText= null;
                if(slickSelectedOption.length) {
                    slickSelectedText = slickSelectedOption.text().trim();
                    $(slickSelectControl).find('.slick-selected').text(slickSelectedText);
                    $(slickSelectControl).find('.slick-value').val(slickSelectedOption.data('value'));
                    $(slickSelectControl).find('.slick-options').attr('aria-activedescendant',slickSelectedText);
                }
            });

            $('.slick-select .slick-title').on('click', function() {
                var expandStatus = !($(this).attr('aria-expanded') === 'true');
                $(this).next().slideToggle('fast');
                $(this).attr('aria-expanded',expandStatus);
            });

            $('.slick-select .slick-option').on('click', function(args) {
                var slickControl = $(this).closest('.slick-select'),
                    currentOption = slickControl.find('li[aria-selected="true"]'),
                    selectedOption = $(this);
                if(currentOption.get(0) === selectedOption.get(0)) {
                    setSlickSelection(slickControl, currentOption, selectedOption, false, true);
                } else {
                    setSlickSelection(slickControl, currentOption, selectedOption, true, true);
                }
            });

            $('.slick-select .slick-title').on('keydown', function(args) {
                var slickControl = $(this).closest('.slick-select'),
                    currentOption = slickControl.find('li[aria-selected="true"]'),
                    selectedOption = null;
                switch (args.key) {
                    case 'Escape':
                    case 'Tab':
                        setSlickSelection(slickControl, null, null, false, true);
                        break;
                    case 'ArrowUp':
                        args.preventDefault();
                        selectedOption = currentOption.prev();
                        break;
                    case 'ArrowDown':
                        args.preventDefault();
                        selectedOption = currentOption.next();
                        break;
                }
                if(selectedOption && selectedOption.length) {
                    setSlickSelection(slickControl, currentOption, selectedOption, true, false);
                }
            });

            $(document).on('click' ,function(args) {
                var slickControl = $('.slick-select');
                if(slickControl !== args.target && !slickControl.has(args.target).length) {
                    setSlickSelection(slickControl, null, null, false, true);
                }
            });
        }

        //ADA Logged in User dropdown
        $(".header-nav-pannel").on("keydown", ".wlgn-login-container > .customer-name, .wlgn-login-container .customer-dropdown", function(e) {
            if(e.originalEvent.keyCode == 13) {
                $(e.target).closest("div.wlgn-login-container").toggleClass("active");
            }
        });
    });

    function setSlickSelection(slickControl, currentOption, selectedOption, setAttrFlag, slideUpFlag) {
        var slickOptions = slickControl.find('.slick-options');
        if(setAttrFlag) {
            var selectedOptionText = selectedOption.text().trim();
            currentOption.attr('aria-selected',false);
            selectedOption.attr('aria-selected',true);
            slickOptions.attr('aria-activedescendant',selectedOptionText);
            slickControl.find('.slick-selected').text(selectedOptionText);
            slickControl.find('.slick-value').val(selectedOption.data('value'));
        }
        if(slideUpFlag) {
            slickOptions.slideUp('fast');
            slickControl.find('.slick-title').attr('aria-expanded',false);
        }
    }

});
