$(document).ready(function () {
    // Load existing transports
    function loadTransports() {
        $.ajax({
            type: "GET",
            url: "load_transports.php",
            success: function (response) {
                const transports = JSON.parse(response);
                $('#transport-list-items').empty();
                transports.forEach(function (transport) {
                    $('#transport-list-items').append(`
                      <option value="${transport}">${transport}</option>
                    `);
                });
            }
        });
checkStatus();
    }


// check ts status function

function checkStatus() {
            $('#transport-list-items option').each(function() {
                var itemName = $(this).text();
                
                // Store the reference to the current list item
                var listItem = $(this);
                
                $.ajax({
                    url: 'check_ts_status.php',
                    type: 'POST',
                    data: { name: itemName },
                    success: function(response) {
                        if (response.trim() == 'running') {
                            listItem.removeClass('not-running').addClass('running');
                        } else {
                            listItem.removeClass('running').addClass('not-running');
                        }
                    },
                    error: function() {
                        console.log('Error checking status.');
                    }
                });
            });
        }

// transport start/stop button check

     


// set loop to check transport status
   setInterval(checkStatus, 2000); // Check every 2 seconds

// Attach event listener to the select element
       //     $('#transport-list-items').on('change', updateButtonStatus);


 


// Attach event listener to the button
$('#toggle-transport-btn').on('click', function() {
                
const selectedTransport = $('#transport-list-items').val();

let selectedItem = $('#transport-list-items option:selected');

let  action = "stop";

if (selectedItem.hasClass('running')) {
               // button.text('Stop Transport');
            action = "stop";
            } else {
               // button.text('Start Transport');
             action = "start";
            }

        if (selectedTransport) {
            
  $.ajax({
                    url: 'toggle_transport.php',
                    type: 'GET',
                    data: { 
                        transport: selectedTransport, 
                        action: action 
                    },
                    success: function(response) {
                        alert(response);
                        checkStatus(); // Refresh status after action
                    },
                    error: function() {
                        alert('Error toggling transport.');
                    }
                });


        } else {
            alert("Please select a transport to toggle");
        }

                
               


              });


    loadTransports();


    // Add a new transport
    $('#add-transport-btn').click(function () {
        window.location.href = 'edit.html';
    });

    // Edit an existing transport
    $('#edit-transport-btn').click(function () {
        const selectedTransport = $('#transport-list-items').val();
        if (selectedTransport) {
            window.location.href = `edit.html?transport=${selectedTransport}`;
        } else {
            alert("Please select a transport to edit");
        }
    });

    // Delete transport
    $('#delete-transport-btn').click(function () {
        const selectedTransport = $('#transport-list-items').val();
        if (selectedTransport) {
            if (confirm(`Are you sure you want to delete transport "${selectedTransport}"?`)) {
                $.ajax({
                    type: "POST",
                    url: "delete_transport.php",
                    data: JSON.stringify({ transportName: selectedTransport }),
                    contentType: "application/json",
                    success: function (response) {
                        alert("Transport deleted successfully!");
                        loadTransports();
                    },
                    error: function (response) {
                        alert("An error occurred while deleting the transport.");
                    }
                });
            }
        } else {
            alert("Please select a transport to delete");
        }
    });
});