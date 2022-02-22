<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Html\Builder;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     * @throws Exception
     */
    public function index(Request $request)
    {
        $this->can('admin.roles.read');

        //datatables
        if ($request->ajax()) {
            return $this->dataTableQuery();
        }

        $html = $this->dataTable();
        return view('admin.users.index', compact('html'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        abort(404);
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return Response
     */
    public function show(User $user)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param User $user
     * @return View
     */
    public function edit(User $user): View
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user','roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'sometimes|string|min:4|max:30',
            'email' => 'sometimes|string|email',
            'credits' => 'sometimes|numeric|between:0,99999999999.999999',
            'server_limit' => 'sometimes|numeric|max:2147483647|min:0',
            'roles' => 'nullable|array',
        ]);

        //update roles
        if ($request->has('roles')) {
            $roles = Role::query()->findMany($request->roles);
            $user->syncRoles($roles);
        }

        //update password
        if ($request->has('password')) {
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'credits' => $request->credits,
            'server_limit' => $request->server_limit,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('User saved'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return Response
     */
    public function destroy(User $user)
    {
        dd($user);
    }

    /**
     * @description create table
     *
     * @return Builder
     */
    public function dataTable(): Builder
    {
        return $this->htmlBuilder
            ->addColumn(['data' => 'name', 'name' => 'name', 'title' => __('Name')])
            ->addColumn(['data' => 'email', 'name' => 'email', 'title' => __('Email')])
            ->addColumn(['data' => 'roles', 'name' => 'roles', 'title' => __('Roles'),  'searchable' => false])
            ->addColumn(['data' => 'credits', 'name' => 'credits', 'title' => __('Credits')])
            ->addAction(['data' => 'actions', 'name' => 'actions', 'title' => __('Actions'), 'searchable' => false, 'orderable' => false])
            ->parameters($this->dataTableDefaultParameters());
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function dataTableQuery(): mixed
    {
        $query = User::query()->with('roles');

        return datatables($query)
            ->addColumn('actions', function (User $user) {
                return Blade::render('
                        @can("admin.roles.write")
                            <a title="{{\'Edit\'}}" href="{{route("admin.users.edit", $user)}}" class="btn btn-sm btn-info"><i
                                    class="fa fas fa-edit"></i></a>
                            <form class="d-inline" method="post" action="{{route("admin.users.destroy", $user)}}">
                                @csrf
                                @method("DELETE")
                                <button title="{{\'Delete\'}}" type="submit" class="btn btn-sm btn-danger confirm"><i
                                        class="fa fas fa-trash"></i></button>
                            </form>
                        @endcan'
                    , compact('user'));
            })
            ->addColumn('roles', function (User $user) {
                $html = '';

                foreach ($user->roles as $role) {
                    $html .= "<span style='background-color: $role->color' class='badge'>$role->name</span>";
                }

                return $html;
            })
            ->editColumn('updated_at', function ($model) {
                return $model->updated_at ? $model->updated_at->diffForHumans() : '';
            })
            ->rawColumns(['actions', 'roles'])
            ->make(true);
    }
}
