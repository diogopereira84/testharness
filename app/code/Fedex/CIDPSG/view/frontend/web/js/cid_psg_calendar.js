require([
	'jquery',
	'mage/calendar',
], function ($) {
	
	/*
	 * Add calendar in Date of Incorporation field
	 */
	$('#datepicker_date_of_incorp').calendar({
		changeMonth: true,
		changeYear: true,
		showButtonPanel: false,
		yearRange: "-50:-0",
		maxDate: 0,
		dateFormat: 'mm/dd/yyyy',
		onSelect: function (dateString, txtDate) {
			$("#date_of_incorp").focus();
			$("#date_of_incorp").val(dateString);
			$(this).hide();
		}
	});

	/*
	 * Clear date after press key
	 */
	$('#date_of_incorp').keyup(function (e) {
		if (e.keyCode == 8) {
			$("#date_of_incorp").val("");
			$('#datepicker_date_of_incorp').hide();
		}
	});

	/*
	 * Show calendar click and focus in Date of Information field
	 */
	$(document).on("click, focus", '#date_of_incorp', function () {
		$("#datepicker_date_of_incorp").show();
		$("#datepicker_in_buiseness_since").hide();
	});

	/*
	 * Show calendar after press key in Date of Information field
	 */
	$("#date_of_incorp").keypress(function (e) {
		if (e.keyCode == 13) {
			$("#datepicker_date_of_incorp").show();
		}
	});

	/*
	 * Show focus in field after select date
	 */
	$('#date_of_incorp, #in_buiseness_since').click(function () {
		$(this).focus();
	});

	/*
	 * Close calendar focus on any other field
	 */
	$(document).on("focus", '#state_of_incorp, #nature_of_business', function () {
		$("#datepicker_date_of_incorp").hide();
	});

	/*
	 * Close calendar after click outside
	 */
	$(document).click(function (e) {
		let container = $("#ui-datepicker-div,#datepicker_date_of_incorp");
		if (!($(e.target).is($('#ui-datepicker-div,#datepicker_date_of_incorp')) ||
		$(e.target).has($('#ui-datepicker-div,#datepicker_date_of_incorp')).length === 0)) {
			$("#datepicker_date_of_incorp").hide();
		}
	});

	/*
	 * Add calendar in business since field
	 */
	$('#datepicker_in_buiseness_since').calendar({
		changeMonth: true,
		changeYear: true,
		showButtonPanel: false,
		yearRange: "-50:-0",
		maxDate: 0,
		dateFormat: 'mm/dd/yyyy',
		onSelect: function (dateString, txtDate) {
			$("#in_buiseness_since").focus();
			$("#in_buiseness_since").val(dateString);
			$(this).hide();
		}
	});

	/*
	 * Show calendar after press enter key
	 */
	$('#in_buiseness_since').keyup(function (e) {
		if (e.keyCode == 8) {
			$("#in_buiseness_since").val("");
			$('#datepicker_in_buiseness_since').hide();
		}
	});

	/*
	 * Show calendar click and focus in Date of Information field
	 */
	$(document).on("click, focus", '#in_buiseness_since', function () {
		$("#datepicker_in_buiseness_since").show();
		$("#datepicker_date_of_incorp").hide();
	});

	/*
	 * Show calendar click and focus in Date of Information field
	 */
	$("#in_buiseness_since").keypress(function (e) {
		if (e.keyCode == 13) {
			$("#datepicker_in_buiseness_since").show();
		}
	});

	/*
	 * Close calendar focus on any other field
	 */
	$(document).on("focus", '#state_of_business, #state_of_incorp', function () {
		$("#datepicker_in_buiseness_since").hide();
	});

	/*
	 * Close calendar after click outside
	 */
	$(document).click(function (e) {
		let container = $("#ui-datepicker-div,#datepicker_in_buiseness_since");
		if (!($(e.target).is($('#ui-datepicker-div,#datepicker_in_buiseness_since')) ||
		$(e.target).has($('#ui-datepicker-div,#datepicker_in_buiseness_since')).length === 0)) {
			$("#datepicker_in_buiseness_since").hide();
		}
	});
});
