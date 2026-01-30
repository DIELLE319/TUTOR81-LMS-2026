var TablesDatatables = function() {
    return {
        init: function() {
            //App.datatables();
            progressTable = $('#progress-table').DataTable({
                order: [[0, "asc"],[1, "asc"]],
                columnDefs: [{
                        orderable: false,
                        target: [9]
                }],
                pageLength: 25,
                lengthMenu: [[25, 50, -1], [25, 50, "All"]],
                "drawCallback":  function(){
                    var learning_project_users = $('#progress-table tbody tr.started:not(.prog_rate)').map(function(){ 
                        return {id: $(this).data('learning_project_user_id'), 
                            learning_project_id: $(this).data('learning_project_id'),
                            learning_event_id: $(this).data('learning_event_id')};
                    }).get();
                    if (learning_project_users.length > 0) {
                        $.post('manage/project.php', {
                                op_type: "calc_progress_rate",
                                //learning_project_id: $('#courseSelect').val(),
                                learning_project_users: JSON.stringify(learning_project_users)
                            }, function(data){
                                try {
                                    var result = $.parseJSON(data);
                                    $.each(result, function(index, execution_percentage){
                                        $('#progress-table tbody tr[data-learning_project_user_id="'+index+'"] td.progress-status')
                                                .html('<div class="progress">'
                                                            + '<div class="progress-bar progress-bar-striped ' + (execution_percentage == 0 ? ' progress-bar-danger" ' : '""')  
                                                                + 'aria-valuemin="0" aria-valuemax="100" '
                                                                + 'style="width: ' + execution_percentage + '%; min-width: 2em;">'
                                                                + execution_percentage
                                                            + '</div>'
                                                        + '</div>').parents('tr').addClass('prog_rate');
                                    });
                                } catch (err){
                                    //alert('error: ' + err.message);
                                }
                            }
                        );
                    }
                } 
            });
            $("#bk-sold-courses-table").DataTable({
                dom: 'Bfrtlip',
                order: [[0, "desc"]],
                columnDefs: [{
                        orderable: false,
                        target: [8]
                }],
                buttons: [
                    {
                        extend: 'copy',
                        text: 'copia negli appunti'
                    },
                    'csv', 'excel'
                ],
                pageLength: 100,
                lengthMenu: [[10, 20, 30, -1], [10, 20, 30, "All"]]
            });
            $("#bk-purchase-courses-table").DataTable({
                order: [[0, "desc"]],
                pageLength: 100,
                lengthMenu: [[10, 20, 30, -1], [10, 20, 30, "All"]]
            });
            bkEcommerceTable = $("#bk-ecommerce-table").DataTable({
                dom: 'Bfrtlip',
                order: [[0, "desc"]],
                columnDefs: [{
                    orderable: false,
                    targets: [6]
                }],
                buttons: [
                    {
                        extend: 'copy',
                        text: 'copia negli appunti'
                    },
                    'csv', 'excel'
                ],
                pageLength: 100,
                lengthMenu: [[10, 20, 30, -1], [10, 20, 30, "All"]]
            });
            $("#employees-table").DataTable({
                autoWidth: false,
                order: [1,'asc'],
                columnDefs: [{
                    data: "name",
                    name: "name",
                    targets: 0
                }, {
                    data: "surname",
                    name: "surname",
                    targets: 1
                }, {
                    data: "tax_code",
                    name: "tax_code",
                    targets: 2
                }, {
                    data: "business_function",
                    name: "business_function",
                    targets: 3
                }, {
                    data: "email",
                    name: "email",
                    targets: 4
                }, {
                    data: "deleted",
                    name: "deleted",
                    targets: 5,
                    orderable: false,
                    render: function(data, type, row, meta) {
                        var deleted = data == "1" ? 'open' : 'close';
                        return '<a href="javascript: void(0);" class="employee-' + deleted + '"><span class="glyphicon glyphicon-eye-' + deleted + '"></span></a>';
                    }
                }],
                rowCallback: function( row, data, index ) {
                    $(row).data('user_id', data.user_id);
                },
                pageLength: 50,
                lengthMenu: [[100, 200, 300, -1], [100, 200, 300, "All"]]
            });
            bkUpdateNeedsTable = $("#update_needs_table").DataTable({
                dom: 'Bfrtlip',
                autoWidth: true,
                order: [[0, "asc"]],
                columnDefs: [
                    { searchable: false, targets: [5] },
                    { orderable: false, targets: [5] }
                ],
                buttons: [
                    {
                        extend: 'copy',
                        text: 'copia negli appunti',
                        exportOptions: {
                            columns: ':not(.noExport)',
                        }
                    },
                    {
                        extend:'csv',
                        exportOptions: {
                            columns: ':not(.noExport)',
                        }
                        
                    },
                    {
                        extend:'excel',
                        exportOptions: {
                            columns: ':not(.noExport)',
                        }
                    }
                ],
                pageLength: 100,
                lengthMenu: [[10, 20, 30, -1], [10, 20, 30, "All"]]
            });
            bkAllUpdateNeedsTable = $("#all_update_needs_table").DataTable({
                dom: 'Bfrtlip',
                autoWidth: true,
                order: [[0, "asc"]],
                columnDefs: [
                    { searchable: false, targets: [6] },
                    { orderable: false, targets: [6] }
                ],
                buttons: [
                    {
                        extend: 'copy',
                        text: 'copia negli appunti',
                        exportOptions: {
                            columns: ':not(.noExport)',
                        }
                    },
                    {
                        extend:'csv',
                        exportOptions: {
                            columns: ':not(.noExport)',
                        }
                        
                    },
                    {
                        extend:'excel',
                        exportOptions: {
                            columns: ':not(.noExport)',
                        }
                    }
                ],
                pageLength: 100,
                lengthMenu: [[10, 20, 30, -1], [10, 20, 30, "All"]]
            });
            $("#attestati-table").DataTable({
                autoWidth: false,
                columns: [{
                    data: "user_name",
                    name: "user_name",
                    targets: 0,
                    className: 'dt-body-left'
                }, {
                    data: "learning_project_title",
                    name: "learning_project_title",
                    targets: 1,
                    render: function(data, type, row, meta) {
                        var cod_pos = data.indexOf('-');
                        return cod_pos > 0 ? data.substring(cod_pos+2) : data;
                    },
                    className: 'dt-body-left'
                }, {
                    data: "end_date",
                    name: "end_date",
                    targets: 2,
                    render: function(data, type, row, meta) {
                        var dateSplit = data.split('-');
                        return type === "display" || type === "filter" ?
                        dateSplit[2] +'-'+ dateSplit[1] +'-'+ dateSplit[0] : data;
                    }
                }, {
                    data: "license_id",
                    name: "attestati",
                    targets: 3,
                    orderable: false,
                    render: function(data, type, row, meta) {
                        return '<a target="_blank" href="manage/render_document.php?doc_type=attestato_elearning&license_id=' + data +'" title="Scarica"><span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span></a>' +
                                ' | ' + 
                                '<a href="javascript: void(0)" class="send-attestato" data-license_id="' + data + '" title="Invia"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>';
                    }
                }],
                pageLength: 50,
                lengthMenu: [[100, 200, 300, -1], [100, 200, 300, "All"]]
            });
            $("#companies-table").DataTable({
                order: [[0, "asc"]],
                columnDefs: [{
                    orderable: false,
                    targets: [1, 2, 3, 4, 5]
                }],
                pageLength: 100,
                lengthMenu: [[10, 20, 30, -1], [10, 20, 30, "All"]]
            });
            employeesSellTable = $("#employees-sell-table").DataTable({
                autoWidth: false,
                order: [0,'asc'],
                columnDefs: [
                    {
                    data: "surname",
                    name: "surname",
                    className: 'surname',
                    targets: 0
                }, {
                    data: "name",
                    name: "name",
                    className: 'name',
                    targets: 1
                }, {
                    data: "tax_code",
                    name: "tax_code",
                    className: 'tax_code',
                    targets: 2
                }, {
                    data: "business_function",
                    name: "business_function",
                    className: 'func_id',
                    targets: 3
                }, {
                    data: "email",
                    name: "email",
                    className: 'email',
                    targets: 4
                }, {
                    data: "user_id",
                    name: "licenceIDUser",
                    className: 'user_id',
                    targets: 5//,
//                    render: function(data, type, row, meta) {
//                        return '<span class="licenceIDUser">' + data + "</span>";
//                    }
                }, {
                    name: "accreditation_code",
                    targets: 6,
                    orderable: false,
                    render: function(data, type, row, meta) {
                        return '<input type="text" class="accreditation_code form-control input-sm" name="accreditation_code" value="">';
                    }
                }],
                select: {
                    style: 'multi',
                    items: 'row',
                    info: true
                },
                pageLength: 10,
                lengthMenu: [[100, 200, 300, -1], [100, 200, 300, "All"]]
            });
        }
    };
}();
