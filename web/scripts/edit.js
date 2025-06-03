$(document).ready(function () {
    let channels = {};
    let selectedChannel = null;
let data; // Declare data globally



    // Get transport from query parameters
    const urlParams = new URLSearchParams(window.location.search);
    const transport = urlParams.get('transport');

    if (transport) {
        $.ajax({
            type: "GET",
            url: `load_transport.php?transport=${transport}`,
            success: function (response) {
                 data = JSON.parse(response);

                // Load global data
                $('#transport_name').val(transport);
                $('#TVCT_version').val(data.global.TVCT_version);
                $('#TVCT_current').val(data.global.TVCT_current);
                $('#transport_stream_id').val(data.global.transport_stream_id);
                $('#protocol_version').val(data.global.protocol_version);

  

// Load channels dynamically
const channelsRegex = /^channel\d+$/; // Regex to match properties starting with channel
const channelKeys = Object.keys(data).filter(key => channelsRegex.test(key));
let selectedChannel = null; // Variable to store the selected channel for editing


$('#channel-list-items').empty();
channelKeys.forEach(channelKey => {
    const channelData = data[channelKey];
       
channels[channelKey] = {

                short_name: channelData.short_name,
                major_channel_number: channelData.major_channel_number,
                minor_channel_number: channelData.minor_channel_number,
                modulation_mode: channelData.modulation_mode,
                carrier_frequency: channelData.carrier_frequency,
                channel_TSID: channelData.channel_TSID,
                program_number: channelData.program_number,
                ETM_location: channelData.ETM_location,
                access_controlled: channelData.access_controlled,
                hidden: channelData.hidden,
                hide_guide: channelData.hide_guide,
                service_type: channelData.service_type,
                source_id: channelData.source_id,
                PCR_PID: channelData.PCR_PID,
                component_stream_type1: channelData.component_stream_type1,
                elementary_PID1: channelData.elementary_PID1,
                component_stream_type2: channelData.component_stream_type2,
                elementary_PID2: channelData.elementary_PID2,
                xmltv_settings: channelData.xmltv_settings,
                xmltv_id: channelData.xmltv_id,
            };





    $('#channel-list-items').append(`
        <li data-channel="${channelKey}">
            ${channelData.short_name} <button class="edit-channel-btn">Edit</button>
            <button class="delete-channel-btn">Delete</button>
        </li>

    `);
});

                // Load MGT data
                $('#MGT_version').val(data.MGT.version);
                $('#MGT_protocol_version').val(data.MGT.protocol_version);
                if (data.MGT.tables.length > 0) {
                    const table = data.MGT.tables[0];
                   // $('#table_type').val(table.type);
                   // $('#table_PID').val(table.PID);
                   // $('#table_version_number').val(table.version_number);
                   // $('#table_number_bytes').val(table.number_bytes);

		    $('#table_type').val(table.type.trim());
                    $('#table_PID').val(table.PID.trim());
                    $('#table_version_number').val(table.version_number.trim());
                    $('#table_number_bytes').val(table.number_bytes.trim());
                }

                // Load STT data
                $('#STT_protocol_version').val(data.STT.protocol_version);
                $('#system_time').val(data.STT.system_time);
                $('#GPS_UTC_offset').val(data.STT.GPS_UTC_offset);
                $('#DS_status').val(data.STT.DS_status);


		 // Load Control Port data
                $('#control_port').val(data.OUTPUT.control_port);


	      // Load OUPUT data
                $('#udp_address').val(data.OUTPUT.udpaddress);

            }
        });
    }


    // Add channel to the list
    $('#add-channel-btn').click(function () {

let channelNumber = 1;
 $('#channel-list-items li').each(function() {
 const existingChannelName = $(this).attr('data-channel');
const existingChannelNumber = parseInt(existingChannelName.replace('channel',''));
 channelNumber = Math.max(channelNumber,existingChannelNumber + 1);
 });

const shortName = $('#new-channel-name').val().trim();
        const channelName = "channel" + channelNumber;

        if (channelName && !channels[channelName]) {
            channels[channelName] = {};
            $('#channel-list-items').append(`
                <li data-channel="${channelName}">
                  ${shortName} <button class="edit-channel-btn">Edit</button>
                  <button class="delete-channel-btn">Delete</button>
                </li>
            `);
            $('#new-channel-name').val('');
        }
    });







 // Edit a channel
    $(document).on('click', '.edit-channel-btn', function () {
            const channelName = $(this).parent().attr('data-channel');
        selectedChannel = channelName;

try {
       const channelData = data[channelName];
       

if (channelData) {
 // Populate the form with channel data

        $('#short_name').val(channelData.short_name);
        $('#major_channel_number').val(channelData.major_channel_number);
        $('#minor_channel_number').val(channelData.minor_channel_number);
        $('#modulation_mode').val(channelData.modulation_mode);
        $('#carrier_frequency').val(channelData.carrier_frequency);
        $('#channel_TSID').val(channelData.channel_TSID);
        $('#program_number').val(channelData.program_number);
        $('#ETM_location').val(channelData.ETM_location);
        $('#access_controlled').val(channelData.access_controlled);
        $('#hidden').val(channelData.hidden);
        $('#hide_guide').val(channelData.hide_guide);
        $('#service_type').val(channelData.service_type);
        $('#source_id').val(channelData.source_id);
        $('#PCR_PID').val(channelData.PCR_PID);
        $('#component_stream_type1').val(channelData.component_stream_type1);
        $('#elementary_PID1').val(channelData.elementary_PID1);
        $('#component_stream_type2').val(channelData.component_stream_type2);
        $('#elementary_PID2').val(channelData.elementary_PID2);
        $('#xmltv_settings').val(channelData.xmltv_settings);
        $('#xmltv_id').val(channelData.xmltv_id);







 
} else {
 console.error("Channelnot found for name:", channelName);

$('#short_name').val('');
        $('#major_channel_number').val('');
        $('#minor_channel_number').val('');
        $('#modulation_mode').val('');
        $('#carrier_frequency').val('');
        $('#channel_TSID').val('');
        $('#program_number').val('');
        $('#ETM_location').val('');
        $('#access_controlled').val('');
        $('#hidden').val('');
        $('#hide_guide').val('');
        $('#service_type').val('');
        $('#source_id').val('');
        $('#PCR_PID').val('');
        $('#component_stream_type1').val('');
        $('#elementary_PID1').val('');
        $('#component_stream_type2').val('');
        $('#elementary_PID2').val('');
        $('#xmltv_settings').val('');
        $('#xmltv_id').val('');

 }


}
catch (error) {
}


$('#channel- edit').insertAfter($(this).parent());
$('#channel-edit').show();

});


    // Cancel button
    $('#cancel-btn').click(function () {




        if (selectedChannel) {



            $('#channel-edit').hide();
        }
    });




    // Save the channel data
    $('#save-channel-btn').click(function () {




        if (selectedChannel) {



            channels[selectedChannel] = {
                short_name: $('#short_name').val().trim(),
                major_channel_number: $('#major_channel_number').val().trim(),
                minor_channel_number: $('#minor_channel_number').val().trim(),
                modulation_mode: $('#modulation_mode').val().trim(),
                carrier_frequency: $('#carrier_frequency').val().trim(),
                channel_TSID: $('#channel_TSID').val().trim(),
                program_number: $('#program_number').val().trim(),
                ETM_location: $('#ETM_location').val().trim(),
                access_controlled: $('#access_controlled').val().trim(),
                hidden: $('#hidden').val().trim(),
                hide_guide: $('#hide_guide').val().trim(),
                service_type: $('#service_type').val().trim(),
                source_id: $('#source_id').val().trim(),
                PCR_PID: $('#PCR_PID').val().trim(),
                component_stream_type1: $('#component_stream_type1').val().trim(),
                elementary_PID1: $('#elementary_PID1').val().trim(),
                component_stream_type2: $('#component_stream_type2').val().trim(),
                elementary_PID2: $('#elementary_PID2').val().trim(),
                xmltv_settings: $('#xmltv_settings').val().trim(),
                xmltv_id: $('#xmltv_id').val().trim(),

            };

 data[selectedChannel] = {
                short_name: $('#short_name').val().trim(),
                major_channel_number: $('#major_channel_number').val().trim(),
                minor_channel_number: $('#minor_channel_number').val().trim(),
                modulation_mode: $('#modulation_mode').val().trim(),
                carrier_frequency: $('#carrier_frequency').val().trim(),
                channel_TSID: $('#channel_TSID').val().trim(),
                program_number: $('#program_number').val().trim(),
                ETM_location: $('#ETM_location').val().trim(),
                access_controlled: $('#access_controlled').val().trim(),
                hidden: $('#hidden').val().trim(),
                hide_guide: $('#hide_guide').val().trim(),
                service_type: $('#service_type').val().trim(),
                source_id: $('#source_id').val().trim(),
                PCR_PID: $('#PCR_PID').val().trim(),
                component_stream_type1: $('#component_stream_type1').val().trim(),
                elementary_PID1: $('#elementary_PID1').val().trim(),
                component_stream_type2: $('#component_stream_type2').val().trim(),
                elementary_PID2: $('#elementary_PID2').val().trim(),
                xmltv_settings: $('#xmltv_settings').val().trim(),
                xmltv_id: $('#xmltv_id').val().trim(),
            };





            $('#channel-edit').hide();
        }
    });

    // Delete a channel
    $(document).on('click', '.delete-channel-btn', function () {
        const channelName = $(this).parent().attr('data-channel');
        delete channels[channelName];
        $(this).parent().remove();
    });


// Cancel config button
    $('#cancel-config-btn').click(function () {

window.location.href = 'index.html';
    });



    // Save configuration to a file
    $('#save-config-btn').click(function () {
        let globalData = {
            TVCT_version: $('#TVCT_version').val().trim(),
            TVCT_current: $('#TVCT_current').val().trim(),
            transport_stream_id: $('#transport_stream_id').val().trim(),
            protocol_version: $('#protocol_version').val().trim()
        };

        let mgtData = {
            version: $('#MGT_version').val().trim(),
            protocol_version: $('#MGT_protocol_version').val().trim(),
            tables: [{
                type: $('#table_type').val().trim(),
                PID: $('#table_PID').val().trim(),
                version_number: $('#table_version_number').val().trim(),
                number_bytes: $('#table_number_bytes').val().trim(),
            }]
        };

        let sttData = {
            protocol_version: $('#STT_protocol_version').val().trim(),
            system_time: $('#system_time').val().trim(),
            GPS_UTC_offset: $('#GPS_UTC_offset').val().trim(),
            DS_status: $('#DS_status').val().trim()
        };

      


        let outputData = {
            udpaddress: $('#udp_address').val().trim(),
            control_port: $('#control_port').val().trim()
        };


        let configData = {
            global: globalData,
            channels: channels,
            MGT: mgtData,
            STT: sttData,
            OUTPUT: outputData

        };

        const transportName = $('#transport_name').val().trim();
        if (!transportName) {
            alert('Please provide a transport name');
            return;
        }

        $.ajax({
            type: "POST",
            url: "save_config.php",
            data: JSON.stringify({ transportName, configData }),
            contentType: "application/json",
            success: function (response) {
                alert("Configuration saved successfully!");
                window.location.href = 'index.html';
            },
            error: function (response) {
                alert("An error occurred while saving the configuration.");
            }
        });
    });
});