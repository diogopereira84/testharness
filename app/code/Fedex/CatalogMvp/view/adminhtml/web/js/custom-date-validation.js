require(
    [
        'jquery',
        'moment',
        'fedex/storage',
        'mage/translate'
    ],
    function ($,moment,fxoStorage) {
        $(document).ready(function(){
            $(document).ajaxStop(function(){
                var startval = jQuery("input[name='product[start_date_pod]']").val();
                var endval = jQuery("input[name='product[end_date_pod]']").val();
                if (window.e383157Toggle) {
                    fxoStorage.set('start_date', startval);
                    fxoStorage.set('end_date', endval);
                } else {
                    localStorage.setItem('start_date', startval);
                    localStorage.setItem('end_date', endval);
                }
                let selctedOption = jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').html();
                let customertimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                $("input[name='extraconfiguratorvalue[customertimezone]']").val(customertimezone);
                $("input[name='extraconfiguratorvalue[customertimezone]']").trigger('change');
                if(selctedOption == "PrintOnDemand") {
                    let adminTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    let options = {year: "numeric", month: "numeric", day: "numeric", timeZone: adminTimezone};
                    let now = new Date().toLocaleString("en-US",options);
                    if (jQuery("input[name='product[end_date_pod]']").val() !== '') {
                        jQuery("button[class='ui-datepicker-trigger']").text("");
                    }
                    if (jQuery("input[name='product[start_date_pod]']").val() !== '') {
                        jQuery("button[class='ui-datepicker-trigger']").text("");
                    }
                    if (jQuery("input[name='product[start_date_pod]']").val() == '') {
                        let customerTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                        let options = {
                                    timeZone: customerTimeZone
                                };
                        let nowDate = new Date().toLocaleString("en",options);
                        let customerTimeandDate = moment(nowDate).format('MM/DD/YYYY h:00 A');
                        jQuery("input[name='product[start_date_pod]']").val(customerTimeandDate);
                        jQuery("input[name='product[start_date_pod]']").trigger('change');
                        jQuery("input[name='extraconfiguratorvalue[custom_start_date]']").val(customerTimeandDate);
                        jQuery("input[name='extraconfiguratorvalue[custom_start_date]']").trigger('change');
                        jQuery("button[class='ui-datepicker-trigger']").text("");
                    }
                    setTimeout(
                        function()
                        {
                            if (jQuery("input[name='product[start_date_pod]']").val() == '') {
                                let customerTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                                let options = {
                                            timeZone: customerTimeZone
                                        };
                                let nowDate = new Date().toLocaleString("en",options);
                                let customerTimeandDate = moment(nowDate).format('MM/DD/YYYY h:00 A');
                                jQuery("input[name='product[start_date_pod]']").val(customerTimeandDate);
                                jQuery("input[name='product[start_date_pod]']").trigger('change');
                                jQuery("input[name='extraconfiguratorvalue[custom_start_date]']").val(customerTimeandDate);
                                jQuery("input[name='extraconfiguratorvalue[custom_start_date]']").trigger('change');
                                jQuery("input[name='product[start_date_pod]']").datepicker("option", "minDate", now);
                                jQuery("button[class='ui-datepicker-trigger']").text("");
                            }
                            if (jQuery("input[name='product[end_date_pod]']").val() == '') {
                                jQuery("input[name='product[end_date_pod]']").datepicker("option", "minDate", now);
                                jQuery("button[class='ui-datepicker-trigger']").text("");
                            }
                            jQuery("input[name='product[start_date_pod]']").attr('readonly', true);
                            jQuery("input[name='product[end_date_pod]']").attr('readonly', false);
                        }, 2000);

                    setInterval(function () {
                        $("input[name='product[start_date_pod]']").change(function() {
                            let startDate = jQuery("input[name='product[start_date_pod]']").val();
                            $("input[name='extraconfiguratorvalue[custom_start_date]']").val(startDate);
                            $("input[name='extraconfiguratorvalue[custom_start_date]']").trigger('change');
                            $("button[class='ui-datepicker-trigger']").text("");
                        });
                    }, 2000);

                    setInterval(function () {
                        $("input[name='product[end_date_pod]']").change(function() {
                            let endDate = jQuery("input[name='product[end_date_pod]']").val();
                            $("input[name='extraconfiguratorvalue[custom_end_date]']").val(endDate);
                            $("input[name='extraconfiguratorvalue[custom_end_date]']").trigger('change');
                        });
                    }, 2000);
                }
            });
        });
    });

