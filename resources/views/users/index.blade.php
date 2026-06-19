@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center">
    <h3>Users</h3>

    @can('users.create')
    <button class="btn btn-primary" onclick="openCreateModal()">
        + Add User
    </button>
    @endcan
</div>

<table class="table table-bordered mt-3">
    <thead>
        <tr>
            <th>No</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th width="200">Action</th>
        </tr>
    </thead>

    <tbody id="userTable">
        @foreach ($users as $user)
        <tr id="row-{{ $user->id }}">
            <td>{{ $loop->iteration }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->getRoleNames()->first() ?? '-' }}</td>
            <td>

                @can('users.update')
                <button class="btn btn-sm btn-warning"
                        onclick="editUser({{ $user }})">
                    Edit
                </button>
                @endcan

                @can('users.delete')
                <button class="btn btn-sm btn-danger"
                        onclick="deleteUser({{ $user->id }})">
                    Delete
                </button>
                @endcan

            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- MODAL -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="modalTitle">User Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="user_id">

                <div class="mb-2">
                    <label>Name</label>
                    <input class="form-control" id="name">
                </div>

                <div class="mb-2">
                    <label>Email</label>
                    <input class="form-control" id="email">
                </div>

                <div class="mb-2">
                    <label>Password</label>
                    <input type="password" class="form-control" id="password">
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="saveUser()">Save</button>
            </div>

        </div>
    </div>
</div>

@endsection


@section('scripts')

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>

let userModal = new bootstrap.Modal(document.getElementById('userModal'));

// ================= OPEN CREATE =================
function openCreateModal() {
    $('#user_id').val('');
    $('#name').val('');
    $('#email').val('');
    $('#password').val('');

    $('#modalTitle').text('Create User');

    userModal.show();
}

// ================= SAVE (CREATE / UPDATE) =================
function saveUser() {

    let id = $('#user_id').val();

    let url = id ? '/users/' + id : '/users';
    let type = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: type,
        data: {
            _token: '{{ csrf_token() }}',
            name: $('#name').val(),
            email: $('#email').val(),
            password: $('#password').val()
        },
        success: function(res) {

            userModal.hide();
            location.reload();
        },
        error: function(err) {
            alert('Error saving user');
        }
    });
}

// ================= DELETE =================
function deleteUser(id) {

    if (!confirm('Delete this user?')) return;

    $.ajax({
        url: '/users/' + id,
        type: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function() {
            $('#row-' + id).remove();
        }
    });
}

// ================= EDIT =================
function editUser(user) {

    $('#user_id').val(user.id);
    $('#name').val(user.name);
    $('#email').val(user.email);
    $('#password').val('');

    $('#modalTitle').text('Edit User');

    userModal.show();
}

</script>

@endsection
