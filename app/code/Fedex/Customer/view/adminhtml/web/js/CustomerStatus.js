require(['jquery'], function ($) {
	$(function () {
		$(document).ready(function (e) {
			var toggleData = $('body').hasClass('self_reg_admin_updates');
			var mazegeeks_d_193860_fix = $('body').hasClass('mazegeeks_d_193860_fix');
			if (toggleData && !mazegeeks_d_193860_fix) {
				$('div[data-index="extension_attributes.company_attributes.status"]').hide();
				$(document).on('change', '[data-index="customer_status"]', function () {
					$('div[data-index="extension_attributes.company_attributes.status"]').hide();
					var selectedValue = $('option:selected', this).val();
					if (selectedValue == 0 || selectedValue == 2) {
						if ($("input[name='customer[extension_attributes][company_attributes][status]']").val() == 1) {
							$("input[name='customer[extension_attributes][company_attributes][status]']").attr("checked", false);
							$("input[name='customer[extension_attributes][company_attributes][status]']").click();
						}
					} else if (selectedValue == 1) {
						if ($("input[name='customer[extension_attributes][company_attributes][status]']").val() == 0) {
							$("input[name='customer[extension_attributes][company_attributes][status]']").attr("checked", false);
							$("input[name='customer[extension_attributes][company_attributes][status]']").click();
						}
					}
				});
				$('div[data-index="extension_attributes.company_attributes.status"]').hide();
			} else if (!mazegeeks_d_193860_fix) {
				$(document).on('change', '[data-index="customer_status"]', function () {
					$('div[data-index="customer_status"]').hide();
					$('div[data-index="extension_attributes.company_attributes.status"]').show();
				});
			}

		});
	});
});