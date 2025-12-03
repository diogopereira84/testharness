

/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
  'jquery',
  'uiComponent',
  'moment',
  'mage/url',
  'mage/storage',
  'Magento_Ui/js/modal/modal',
  'mage/calendar',
  'domReady!'
], function ($, Component, moment, urlBuilder, storage, modal) {
  'use strict';
  return Component.extend({

      /** @inheritdoc */
      initialize: function () {
          this._super();
          let self = this;
          $(document).ready(function () {
            if($('#product-id').val()){
              $('.complete-setting').addClass('active');
              $('.complete-setting').prop('disabled', false);
            }
            function startTimeOptions(){
              var startOptionsDate = new Date();
              var startOptionsHours = startOptionsDate.getHours();

              startOptionsDate.getDate();

              var isAM = startOptionsHours <= 12?true:false;

              var AMH = [];
              var PMH = [];

              //if slected date = current date
              for (var h = 0; h < 12; h++) {                           
                if(h >= startOptionsHours){
                  AMH[h] = (h === 0 ? 12 : h) + ':00 AM';
                }                
              }
              
              for (var h = 0; h < 12; h++) {
                if(h >= (startOptionsHours-12)){
                  PMH[h] = (h === 0 ? 12 : h) + ':00 PM';
                }          
              }

              var amOption = '';
              Object.entries(AMH).forEach(([key, value]) => {
                amOption += '<option value="'+value+'">'+value+'</option>';
              });
              Object.entries(PMH).forEach(([key, value]) => {
                amOption += '<option value="'+value+'">'+value+'</option>';
              });
              return amOption;
            }

            function generateTimeOptions() {
              var options = '';
      
                for (var hour = 0; hour < 12; hour++) {
                  for (var minute = 0; minute < 60; minute+=60) {
                    var displayHour = hour === 0 ? 12 : hour;
                    var displayMinute = minute < 10 ? '0' + minute : minute;
                    var amOption = '<option value="' + displayHour + ':' + displayMinute + ' AM">' + displayHour + ':' + displayMinute + ' AM</option>';
                    options += amOption;
                  }
              }
        
              for (var hour = 12; hour <= 23; hour++) {
                for (var minute = 0; minute < 60; minute+=60) {
                  var displayHour = hour === 12 ? 12 : hour - 12;
                  var displayMinute = minute < 10 ? '0' + minute : minute;
                  var pmOption = '<option value="' + displayHour + ':' + displayMinute + ' PM">' + displayHour + ':' + displayMinute + ' PM</option>';
                  options += pmOption;
                }
              }
      
                return options;
             }
             
             function doubledigit(n){
              return n > 9 ? "" + n: "0" + n;
          }

          function currentTime(){
            var today = new Date();
            var hours = today.getHours();
            var currentSelectedTime;

            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            currentSelectedTime = hours + ':00 ' + ampm;
            return currentSelectedTime;
          }

          function currentDate(){
            var todayStart = new Date(),
            dayStart  = todayStart.getDate(),  
            monthStart = todayStart.getMonth() + 1,              
            yearStart =  todayStart.getFullYear();
            return doubledigit(monthStart) + '/' 
            + doubledigit(dayStart) +'/' 
            + yearStart; 
          }
      
            var selectedDate;
            var selectedTime;
            var selectedOnlyTime;
            var endselectedDate;
            var endselectedTime;
            var endselectedOnlyTime;
      
            $('#start-date').on('click', function (e) {
              // Prevent click events from propagating to the parent modals
              e.stopPropagation();
              var startDate = '';
              var startTime = '';
              var mixDateTime = '';
              if($('#start-date').val()){                 
                 startDate = $('#start-date').val().trim().split(' ')[0];
                 startTime = $('#start-date').val().trim().split(' ')[1] +' '+ $('#start-date').val().trim().split(' ')[2];
              }            

              // Show the datetime picker popup
              $('#datetimePickerPopup').css('display', 'block');
              $('#datepicker').datepicker('show');
              if(startDate){
                $('#datepicker').datepicker("setDate",startDate);
              }            

              if (typeof selectedTime === "undefined") {
                selectedTime  = currentTime();
              }
              $('#timeSelect').val(selectedTime);
              if(startTime){
               let startTimeDis = startTime.replace(/^0+/, '');
                $('#timeSelect').val(startTimeDis).change();
              }

            });
      
            $('#datetimePickerPopup').on('click', function (e) {
              // Prevent click events from propagating to the parent modals
              e.stopPropagation();
            });
      
            $('#mvp-cancelButton').on('click', function (e) {
              // Close the datetime picker popup without applying changes
              $('#datetimePickerPopup').css('display', 'none');
              e.stopPropagation();
            });

            $('body.catalog-mvp-customer-admin').on('click', function (e) {
              // Close the datetime picker popup without applying changes
              $('#datetimePickerPopup').css('display', 'none');
              e.stopPropagation();
            });
            $('body.catalog-mvp-customer-admin').on('click', function (e) {
              // Close the datetime picker popup without applying changes
              $('#endDateTimePickerPopup').css('display', 'none');
              e.stopPropagation();
            });
      
            $('#mvp-applyButton').on('click', function (e) {
              let fieldDate = $('#start-date').val();


              if (selectedDate) {
                selectedDate = moment(selectedDate).format('MM/DD/YYYY');
              } else {
                selectedDate = moment(fieldDate).format('MM/DD/YYYY');
              }
              
              if(!selectedDate){
                selectedDate = currentDate();            
              }
              
              if (!selectedTime) {
                $('#start-date').val(selectedDate + ' ');
                e.stopPropagation();            
              }

              // Apply the selected date and time to the input field
              if (selectedDate && selectedTime) {
                $('#start-date').val(selectedDate + ' ' + selectedTime);
                 e.stopPropagation();
                 if(selectedDate < endselectedDate){
                  $('.complete-setting').addClass('active');
                  $('.complete-setting').prop('disabled', false);
                 }else{
                    if(endselectedTime){
                      let selectedTime = $('#timeSelect').val();
                      endselectedOnlyTime = parseInt(endselectedTime.split(" ").shift());
                      selectedOnlyTime = parseInt(selectedTime.split(" ").shift());

                      endselectedOnlyTime = endselectedOnlyTime == 12 ? 0: endselectedOnlyTime;
                      selectedOnlyTime = selectedOnlyTime == 12 ? 0 : selectedOnlyTime;

                      if(selectedTime.split(" ").pop() == "AM"){
                        if(selectedOnlyTime < endselectedOnlyTime){
                          $('.complete-setting').addClass('active');
                          $('.complete-setting').prop('disabled', false);
                        }else{
                          $('.complete-setting').removeClass('active');
                          $('.complete-setting').prop('disabled', true);
                        }
                      }

                      if(selectedTime.split(" ").pop() == "PM"){
                        if(selectedOnlyTime < endselectedOnlyTime){
                          $('.complete-setting').addClass('active');
                          $('.complete-setting').prop('disabled', false);
                          
                        }else{
                          $('.complete-setting').removeClass('active');
                          $('.complete-setting').prop('disabled', true);
                        }
                      }  
                    }
                                  
                 }
                 if($('#no-end-date').is(':checked')){
                  $('.complete-setting').addClass('active');
                  $('.complete-setting').prop('disabled', false);
                }
              }
      
              // Close the datetime picker popup after applying changes
              $('#datetimePickerPopup').css('display', 'none');
            });
      
            $('#datepicker').datepicker({
              dateFormat: 'mm/dd/yy',
              minDate: 0,
              onSelect: function (dateText) {
                selectedDate = dateText;
                $("#endDatePicker").datepicker("option","minDate", selectedDate);
                if( currentDate() != selectedDate){
                  $('#timeSelect').html(generateTimeOptions());
                }else{
                  $('#timeSelect').html(startTimeOptions());
                }

              }
            });
      
            $('#timeSelect').html(startTimeOptions());
            $('#timeSelect').on('change', function () {
              selectedTime = $(this).val();
            });   
 
            $('#shared-catalog-setting-start-date').on('click', function (e) {
              if(!$('#no-end-date').is(':checked')){ 
                // Prevent click events from propagating to the parent modals
                e.stopPropagation();
                var endDate = '';
                var endTime ='';
                if($('#shared-catalog-setting-start-date').val()){
                  var endDateArr = $('#shared-catalog-setting-start-date').val().trim().split(' ');
                  endDate = endDateArr[0];              
                  endTime = endDateArr[1] +' '+ endDateArr[2];
                }
                // Show the datetime picker popup
                $('#endDateTimePickerPopup').css('display', 'block');
                $('#endDatePicker').datepicker('show');
                if(endDate){
                  $('#endDatePicker').datepicker("setDate",endDate);
                }

                if (typeof endselectedTime === "undefined") {
                  endselectedTime  = currentTime();
                }
                $('#endDateTimeSelect').val(endselectedTime);
                if(endTime){
                  let endTimeDis = endTime.replace(/^0+/, '');
                  $('#endDateTimeSelect').val(endTimeDis).change();
                }
                
              }
            });
      
            $('#endDateTimePickerPopup').on('click', function (e) {
              // Prevent click events from propagating to the parent modals
              e.stopPropagation();
            });
      
            $('#mvp-endCancelButton').on('click', function (e) {
              // Close the datetime picker popup without applying changes
              $('#endDateTimePickerPopup').css('display', 'none');
              e.stopPropagation();
            });
      
            $('#mvp-endApplyButton').on('click', function (e) {
              if(!endselectedDate){           
                endselectedDate = currentDate();            
              }
              
              if (!endselectedTime) {
                $('#shared-catalog-setting-start-date').val(endselectedDate + ' ');
                e.stopPropagation();            
              }
              if($('#start-date').val()) {
                var startDates = $('#start-date').val();
                var starttimestamp = new Date(startDates);
                var starttimestamps = starttimestamp.getTime();
                var endDates = endselectedDate + ' ' + endselectedTime;
                var endtimestamp = new Date(endDates);
                var endtimestamps = endtimestamp.getTime();
                if(endtimestamps <= starttimestamps){
                  $(".endDateerrormsg").show();
                }else{
                  $(".endDateerrormsg").hide();
                  // Apply the selected date and time to the input field
                  if (endselectedDate && endselectedTime) {
                    $('#shared-catalog-setting-start-date').val(endselectedDate + ' ' + endselectedTime);
                    $('#endDateTimePickerPopup').css('display', 'none');
                    e.stopPropagation();
                    e.stopPropagation();
                    let fieldDate = $('#start-date').val();
                    let selectedDate = moment(fieldDate).format('MM/DD/YYYY');
                    if(endselectedDate > selectedDate){
                      $('.complete-setting').addClass('active');
                      $('.complete-setting').prop('disabled', false);
                    }else{
                        let selectedTime = $('#timeSelect').val();
                        endselectedOnlyTime = selectedTime ? parseInt(endselectedTime.split(" ").shift()) : '';
                        selectedOnlyTime = selectedTime ? parseInt(selectedTime.split(" ").shift()) : '';

                        endselectedOnlyTime = endselectedOnlyTime == 12 ? 0: endselectedOnlyTime;
                        selectedOnlyTime = selectedOnlyTime == 12 ? 0 : selectedOnlyTime;

                        if(selectedTime.split(" ").pop() == "AM"){
                          if(endselectedOnlyTime > selectedOnlyTime){
                            $('.complete-setting').addClass('active');
                            $('.complete-setting').prop('disabled', false);
                          }else{
                            $('.complete-setting').removeClass('active');
                            $('.complete-setting').prop('disabled', true);
                          }
                        }

                        if(selectedTime.split(" ").pop() == "PM"){
                          if(endselectedOnlyTime > selectedOnlyTime){
                            $('.complete-setting').addClass('active');
                            $('.complete-setting').prop('disabled', false);
                            
                          }else{
                            $('.complete-setting').removeClass('active');
                            $('.complete-setting').prop('disabled', true);
                          }
                        }                        

                    }
                  }
          
                  // Close the datetime picker popup after applying changes
                  $('#endDateTimePickerPopup').css('display', 'none');
                }
              }else{
                $(".endDateerrormsg").hide();
                // Apply the selected date and time to the input field
                if (endselectedDate && endselectedTime) {
                  $('#shared-catalog-setting-start-date').val(endselectedDate + ' ' + endselectedTime);
                  e.stopPropagation();
                  e.stopPropagation();
                  let fieldDate = $('#start-date').val();
                  let selectedDate = moment(fieldDate).format('MM/DD/YYYY');
                  if(endselectedDate > selectedDate){
                    $('.complete-setting').addClass('active');
                    $('.complete-setting').prop('disabled', false);
                  }else{
                    let selectedTime = $('#timeSelect').val();
                      endselectedOnlyTime = selectedTime ? parseInt(endselectedTime.split(" ").shift()) : '';
                      selectedOnlyTime = selectedTime ? parseInt(selectedTime.split(" ").shift()) : '';

                      endselectedOnlyTime = endselectedOnlyTime == 12 ? 0: endselectedOnlyTime;
                      selectedOnlyTime = selectedOnlyTime == 12 ? 0 : selectedOnlyTime;

                      if(selectedTime.split(" ").pop() == "AM"){
                        if(endselectedOnlyTime > selectedOnlyTime){
                          $('.complete-setting').addClass('active');
                          $('.complete-setting').prop('disabled', false);
                        }else{
                          $('.complete-setting').removeClass('active');
                          $('.complete-setting').prop('disabled', true);
                        }
                      }

                      if(selectedTime.split(" ").pop() == "PM"){
                        if(endselectedOnlyTime > selectedOnlyTime){
                          $('.complete-setting').addClass('active');
                          $('.complete-setting').prop('disabled', false);
                          
                        }else{
                          $('.complete-setting').removeClass('active');
                          $('.complete-setting').prop('disabled', true);
                        }
                      }                        

                  }
                }
                // Close the datetime picker popup after applying changes
                $('#endDateTimePickerPopup').css('display', 'none');
              }
            });
      
            $('#endDatePicker').datepicker({
              dateFormat: 'mm/dd/yy',
              minDate: 0,
              onSelect: function (dateText) {
                endselectedDate = dateText;
                $("#datepicker").datepicker("option","maxDate", endselectedDate);
                if( currentDate() != endselectedDate){
                  $('#endDateTimeSelect').html(generateTimeOptions());
                }else{
                  $('#endDateTimeSelect').html(startTimeOptions());
                }
              }
            });
            let fieldDate = $('#start-date').val();
            if(fieldDate)
            {
              let selectedDate = moment(fieldDate).format('MM/DD/YYYY');
              if( currentDate() == selectedDate){
                $('#endDateTimeSelect').html(startTimeOptions());
              }else{
                $('#endDateTimeSelect').html(generateTimeOptions());
              }
            }else{
              $('#endDateTimeSelect').html(startTimeOptions());
            }
            
            $('#endDateTimeSelect').on('change', function () {
              endselectedTime = $(this).val();
            });

            $("#shared-catalog-setting-start-date").click(function() {
              var nnoStartAnEndDate = $('#no-end-date').is(':checked');

              if(nnoStartAnEndDate){
                $("#no-end-date").prop('checked', false);      

                var endDate = $("#shared-catalog-setting-start-date").val();
                if (!$('#no-end-date').is(':checked') && !endDate) {
                  $('.complete-setting').removeClass('active');
                  $('.complete-setting').prop('disabled', true);
                }
                $('#shared-catalog-setting-start-date').attr('placeholder','MM/DD/YYYY 00:00 AM').prop('disabled', false);
              }
             
          });

            $("#no-end-date").on("input", function() {  
              if($(this).val() == "on"){
                $('#shared-catalog-setting-start-date').attr('placeholder','--').val('');                
                
                var inputNameVal = $('#shared-catalog-setting-name').val().length;
                var inputStartDateVal = $('#start-date').val().length; 
                if (inputNameVal != 0
                  && inputStartDateVal != 0){
                    $('.complete-setting').addClass('active');
                    $('.complete-setting').prop('disabled', false);
                  }else {
                    $('.complete-setting').removeClass('active');
                    $('.complete-setting').prop('disabled', true);
                  }
                }
              });

              $(".mvp-input-field").on("input", function() {
                $(window).keydown(function(event){
                 if(event.keyCode == 13) {
                     event.preventDefault();
                     return false;
                 }                
             });
                var inputNameVal = $('#shared-catalog-setting-name').val().length;
                var inputStartDateVal = $('#start-date').val().length;            
                var inputNoEndDateVal = $('#no-end-date').is(':checked');
                
                if(!inputNoEndDateVal){
                  var inputEndDateVal = $('#shared-catalog-setting-start-date').val().length;
                  
                  if (inputNameVal != 0 
                    && inputStartDateVal != 0
                    && inputEndDateVal != 0){
                      $('.complete-setting').addClass('active');
                        $('.complete-setting').prop('disabled', false);                
                    }else {
                      $('.complete-setting').removeClass('active');
                      $('.complete-setting').prop('disabled', true);
                    }
                  }else{
                    if (inputNameVal != 0
                      && inputStartDateVal != 0){
                        $('.complete-setting').addClass('active');
                        $('.complete-setting').prop('disabled', false);
                      }else {
                        $('.complete-setting').removeClass('active');
                        $('.complete-setting').prop('disabled', true);
                      }
                    }
                    
                  });
                  
          $(".mvp-input-field").on("input", function() {
          var max_length = 500;
          var character_entered = $('.char-length-validation').val().length;
          var character_remaining = max_length - character_entered;
          var character_remaining_text = character_remaining +' characters left';
          $('.char-length').html(character_remaining_text);
          });
          });
      },
  });
});
