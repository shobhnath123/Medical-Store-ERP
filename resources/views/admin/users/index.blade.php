@extends('layouts.app')
@section('content')
<h1>User Management</h1>
<div class="card"><h2>Create User</h2><form method="post" action="{{route('admin.users.store')}}">@csrf<div class="grid"><div><label>Name</label><input name="name" required value="{{old('name')}}"></div><div><label>Email</label><input type="email" name="email" required value="{{old('email')}}"></div><div><label>Password (12+ chars)</label><input type="password" name="password" required minlength="12"></div><div><label>Role</label><select name="role" required><option>cashier</option><option>pharmacist</option><option>manager</option><option>admin</option></select></div></div><button type="submit">Create User</button></form></div>
<div class="card"><h2>Users</h2><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr></thead><tbody>@foreach($users as $u)<tr><td>{{$u->name}}</td><td>{{$u->email}}</td><td>{{$u->role}}</td><td>{{$u->is_active ? 'Yes' : 'No'}}</td></tr>@endforeach</tbody></table></div>
@endsection
