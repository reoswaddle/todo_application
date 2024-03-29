@extends('layouts.app')
@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">Todo List</div>
            <div class="card-body">
                <button type="button" id="add_entry" class="btn btn-primary add mb-3 position-absolute" style="z-index: 2">
                    Add Task
                </button>
                <table id="todo_table" class="table" style="width: 1068px;">
                    <thead>
                    <tr>
                        <th scope="col">Task</th>
                        <th scope="col">Description</th>
                        <th scope="col">Complete By</th>
                        <th scope="col" style="width:50px">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="entry_modal" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Task</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="todo_form">
                        <div class="form-group">
                            <label for="task">Task</label>
                            <input type="text" class="form-control" name="task" id="task">
                        </div>
                        <div class="form-group">
                            <label for="description">Further Details</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="complete_by">Complete By</label>
                            <input type="date" class="form-control" id="complete_by" name="complete_by" value="2018-07-22" min="2018-01-01">
                        </div>
                    </form>
                    <div id="validation_errors" class="alert alert-danger" role="alert" style="display: none">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="store_todo" class="btn btn-primary submit-button" style="display: none">Submit</button>
                    <button type="button" id="delete_todo" class="btn btn-danger submit-button" style="display: none">Delete</button>
                    <button type="button" id="update_todo" class="btn btn-info text-white submit-button" style="display: none">Update</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('javascript')
    <script>
        const today = moment().format('Y-MM-DD');

        //data table config
        let todoTable = $('#todo_table').DataTable({
            paginate: false,
            "order": [[3, "asc"], [2, "asc"]],
            columnDefs: [
                {
                    targets: [0, 1],
                    className: "text-truncate",
                    //strike through tasks that are mark as complete
                    render: function (data, type, row, meta) {
                        return (row.status == '1') ? '<del>' + data + '</del>' : data;
                    }
                },
                {
                    targets: 2,
                    className: "text-center",
                    //strike through tasks that are mark as complete & render date as readable but keep it sortable
                    render: function (data, type, row, meta) {
                        let date = moment(data, 'Y-MM-DD').format('DD/MM/Y');
                        if(type === 'display'){
                            return (row.status == '1') ? '<del>' + date + '</del>' : date;
                        }else{
                            return  data;
                        }
                    }
                },
                {
                    targets: 3,
                    className: "text-center",
                    "searchable": false,
                    render: function (data, type, row, meta) {
                        let html, checked = '';
                        //complete tasks are marked as checked
                        if (data == '1') {
                            checked = 'checked="checked"';
                        }
                        html = '<input id="task_' + row.id + '" type="checkbox" class="task-status" data-id="' + row.id + '" ' + checked + '>';

                        return (type === 'display') ? html : data;
                    }
                },
                {
                    targets: 4,
                    className: "text-right",
                    "searchable": false,
                    sortable: false,
                    render: function (data, type, row, meta) {
                        let html = '';
                        html += '<button type="button" class="btn btn-info text-white view mr-2" data-id="' + data + '" title="View Entry"><i class="fa fa-eye"></i></button>';
                        html += '<button type="button" class="btn btn-secondary text-white edit mr-2" data-id="' + data + '" title="Edit Entry"><i class="fa fa-edit"></i></button>';
                        html += '<button type="button" class="btn btn-danger text-white delete mr-2" data-id="' + data + '" title="Delete Entry"><i class="fa fa-trash"></i></button>';

                        return (type === 'display') ? html : data;
                    }
                }
            ],
            "ajax": {
                "url": "/api/tasks",
                "dataSrc": ""
            },
            "columns": [
                {"data": "task"},
                {"data": "description"},
                {"data": "complete_by"},
                {"data": "status"},
                {"data": "id"},
            ]
        });

        $(function (e) {

            //show add entry form
            $('#add_entry').on('click', function (e) {
                $('#entry_modal').modal('show')
                $('#entry_modal h5').text('Add Task')
                $('#store_todo').show();
            });

            //show update, view & delete forms
            $('#todo_table').on('click', '.view', function (e) {
                $('#entry_modal .form-control').attr('disabled', 'disabled');
                let id = $(this).attr('data-id');
                $('#entry_modal').modal('show')
                populateForm(id);
                $('#entry_modal h5').text('Viewing Task')
            }).on('click', '.edit', function (e) {
                let id = $(this).attr('data-id');
                $('#entry_modal').modal('show')
                populateForm(id);
                $('#update_todo').show().attr('data-id', id);
                $('#entry_modal h5').text('Edit Task')
            }).on('click', '.delete', function (e) {
                let id = $(this).attr('data-id');
                $('#entry_modal').modal('show')
                populateForm(id);
                $('#delete_todo').show().attr('data-id', id);
                $('#entry_modal .form-control').attr('disabled', 'disabled');
                $('#entry_modal h5').text('Are you sure you would like to delete this task?')
            })


            //submit todo add
            $('#store_todo').on('click', function (e) {
                e.preventDefault();
                let formData = new FormData($('#todo_form')[0]);
                $.ajax({
                    url: "/api/task",
                    type: 'POST',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false
                }).done(function (data) {
                    reloadTodoTable();
                    $('#entry_modal').modal('hide')
                    toastr.success('Task successfully added')
                }).fail(function (data) {
                    displayValidationErrors(data.responseJSON.errors);
                });
            });

            //submit todo update
            $('#update_todo').on('click', function (e) {
                e.preventDefault()
                let formData = new FormData($('#todo_form')[0]);
                formData.append('_method', 'PATCH');
                let id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/api/task/" + id,
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false
                }).done(function (data) {
                    reloadTodoTable();
                    $('#entry_modal').modal('hide')
                    toastr.success('Task successfully updated')
                }).fail(function (data) {
                    displayValidationErrors(data.responseJSON.errors);
                });
            });

            //submit todo delete
            $('#delete_todo').on('click', function (e) {
                e.preventDefault();
                let id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/api/task/" + id,
                    data: {_method: "DELETE"}
                }).done(function (data) {
                    reloadTodoTable();
                    $('#entry_modal').modal('hide')
                    toastr.success('Task successfully removed')
                }).fail(function (data) {
                    toastr.error('Something went wrong!')
                });
            });

            //check off todo task
            $('#todo_table').on('change', '.task-status', function (e) {
                let status = 0;
                if ($(this).prop('checked')) {
                    status = 1;
                }
                e.preventDefault();
                let id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/api/task-status/" + id,
                    data: {
                        status: status,
                        _method: "PATCH"
                    }
                }).done(function (data) {
                    reloadTodoTable();
                    $('#entry_modal').modal('hide')
                    toastr.success('Task successfully updated')
                }).fail(function (data) {
                    toastr.error('Something went wrong!')
                });
            });

            //default the modal on close
            $('#entry_modal').on('hide.bs.modal', function () {
                $('#entry_modal .form-control').val('').removeAttr('disabled');
                $('#complete_by').val(today);
                hideValidationErrors();
                $('.submit-button').hide();
            })

            //complete by pre-populate and validation
            $('#complete_by').val(today).attr('min', today);

            //remove validation error on input on focus
            $('.form-control').on('focus', function () {
                $(this).removeClass('is-invalid');
            });
        })

        function hideValidationErrors() {
            $('#validation_errors').hide().html("");
            $('.form-control').removeClass('is-invalid');
        }

        function displayValidationErrors(errors) {
            hideValidationErrors();
            $('#validation_errors').show();
            $.each(errors, function (key, value) {
                $('#validation_errors').append('<p class="card-text">' + value[0] + '</p>')
                $('#' + key).addClass('is-invalid');
            });
        }

        function reloadTodoTable() {
            todoTable.ajax.url("/api/tasks").load();
        }

        function populateForm(id) {
            $.get('/api/task/' + id, function (data) {
                $.each(data, function (key, value) {
                    $('#' + key).val(value);
                });
            });
        }

    </script>
@endsection
