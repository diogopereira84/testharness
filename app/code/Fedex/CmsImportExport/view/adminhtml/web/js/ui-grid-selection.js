require(['jquery'],
    function ($) {
        $(document).ready(function () {
            $('.Page').show();
            $('.Block').hide();
            $('.Template').hide();
            $('.Widget').hide();
            $("#cms_content").on('change', function () {
                var textselected = document.getElementById("cms_content").value;
                target = '.' + textselected;
                $('.column_1 .choice').hide();
                $('.column_1 '+target).show();

            });
        });
        $(document).on('click', '.left-to-right-drag', function () {
            var table1 = document.getElementById("table1"),
                table2 = document.getElementById("table2"),
                checkboxes = document.getElementsByName("check-tab1");
            checkboxes_right = document.getElementsByName("check-tab2");

            var strNotSelected = false;
            for (var i = 0; i < checkboxes.length; i++)
                if (checkboxes[i].checked) {
                    // create new row and cells
                    var newRow = table2.insertRow(table2.length),
                        cell1 = newRow.insertCell(0),
                        cell2 = newRow.insertCell(1),
                        cell3 = newRow.insertCell(2),
                        cell4 = newRow.insertCell(3);
                    cell5 = newRow.insertCell(4);
                    cell6 = newRow.insertCell(5);
                    cell7 = newRow.insertCell(6);
                    // add values to the cells
                    cell2.innerHTML = table1.rows[i + 1].cells[1].innerHTML;
                    cell3.innerHTML = table1.rows[i + 1].cells[2].innerHTML;
                    cell4.innerHTML = table1.rows[i + 1].cells[3].innerHTML;
                    cell5.innerHTML = table1.rows[i + 1].cells[4].innerHTML;
                    cell6.innerHTML = table1.rows[i + 1].cells[5].innerHTML;
                    cell7.innerHTML = table1.rows[i + 1].cells[6].innerHTML;
                    cell1.innerHTML = "<input type='checkbox' name='check-tab2'>";

                    // remove the transfered rows from the first table [table1]
                    var index = table1.rows[i + 1].rowIndex;
                    table1.deleteRow(index);
                    i--;

                    var $checkboxes = $('#table2 tr').length;
                    document.getElementById("row_export").innerHTML = "<b>Total " + ($checkboxes - 1) + " Row(s) To Be Exported</b>";
                    strNotSelected = true;
                }

            if (!strNotSelected) {
                var textselected = document.getElementById("cms_content").value;
                alert('Please select at least one ' + textselected + ' from left table.');
                return false;
            }

        });

        $(document).on('click', '.right-to-left-drag', function () {
            var table1 = document.getElementById("table1"),
                table2 = document.getElementById("table2"),
                checkboxes = document.getElementsByName("check-tab2");
                checkboxes_right = document.getElementsByName("check-tab2");

            var boolDselectEle = false;
            for (var i = 0; i < checkboxes.length; i++)
                if (checkboxes[i].checked) {
                    // create new row and cells
                    var newRow = table1.insertRow(table1.length),
                        cell1 = newRow.insertCell(0),
                        cell2 = newRow.insertCell(1),
                        cell3 = newRow.insertCell(2),
                        cell4 = newRow.insertCell(3);
                    cell5 = newRow.insertCell(4);
                    cell6 = newRow.insertCell(5);
                    cell7 = newRow.insertCell(6);
					
					var class_name = table2.rows[i + 1].cells[3].innerHTML;
					if(class_name == 'cms_page'){ class_name = 'Page'; }
					if(class_name == 'cms_block'){ class_name = 'Block'; }
					if(class_name == 'widget'){ class_name = 'Widget'; }
					if(class_name == 'template'){ class_name = 'Template'; }
					
					cell1.className = 'choice '+class_name;
					cell4.className = 'choice '+class_name;
					cell5.className = 'choice '+class_name;
					cell6.className = 'choice '+class_name;
					cell7.className = 'choice '+class_name;

                    cell2.innerHTML = table2.rows[i + 1].cells[1].innerHTML;
                    cell3.innerHTML = table2.rows[i + 1].cells[2].innerHTML;
                    cell4.innerHTML = table2.rows[i + 1].cells[3].innerHTML;
                    cell5.innerHTML = table2.rows[i + 1].cells[4].innerHTML;
                    cell6.innerHTML = table2.rows[i + 1].cells[5].innerHTML;
                    cell7.innerHTML = table2.rows[i + 1].cells[6].innerHTML;
                    cell1.innerHTML = "<input type='checkbox' name='check-tab1'>";

                    // remove the transfered rows from the second table [table2]
                    var index = table2.rows[i + 1].rowIndex;
                    table2.deleteRow(index);
                    i--;
                    var $checkboxes = $('#table2 tr').length;
                    document.getElementById("row_export").innerHTML = "<b>Total " + ($checkboxes - 1) + " Row(s) To Be Exported</b>";
                    var boolDselectEle = true;

					var textselected = document.getElementById("cms_content").value;
					var target = '.' + textselected;
					$('.column_1 .choice').hide();
					$('.column_1 '+target).show();
					// End
                }
            var textselected = document.getElementById("cms_content").value;
            varMsg = 'Please select at least one ' + textselected + ' to shift from left table.'
            if (!checkboxes.length) {
                varMsg = 'No any ' + textselected + ' data found to shift from right table to left table.'
            }
            if (!boolDselectEle) {
                var textselected = document.getElementById("cms_content").value;
                alert(varMsg);
                return false;
            }
        });
    });
